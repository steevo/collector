<?php
/**
 * @package    Collector
 * @copyright  Copyright (C) 2010 - 2020 Philippe Ousset. All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace Steevo\Component\Collector\Site\Controller;

defined('_JEXEC') or die;

use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\Controller\FormController;
use Joomla\CMS\Router\Route;
use Joomla\CMS\Session\Session;

/**
 * Contrôleur frontend pour les items (fiche et édition).
 */
class ItemController extends FormController
{
	/**
	 * Suppression d'un item depuis le frontend.
	 * Nécessite la permission core.delete sur l'item ou la collection.
	 */
	public function delete()
	{
		// Vérification CSRF
		Session::checkToken() or die(Text::_('JINVALID_TOKEN'));

		$app        = Factory::getApplication();
		$id         = $app->input->getInt('id');
		$collection = $app->input->getInt('collection');

		// Contrôle d'accès
		$user = $app->getIdentity();
		$canDelete = $user->authorise('core.delete', 'com_collector.item.' . $id)
		          || $user->authorise('core.delete', 'com_collector.collection.' . $collection)
		          || $user->authorise('core.delete', 'com_collector');

		if (!$canDelete) {
			$app->enqueueMessage(Text::_('JERROR_ALERTNOAUTHOR'), 'error');
			$this->setRedirect(Route::_('index.php?option=com_collector&view=item&collection=' . $collection . '&id=' . $id, false));
			return;
		}

		$model = $this->getModel();

		if ($model->delete($id)) {
			$this->setMessage(Text::_('COM_COLLECTOR_ITEM_DELETED'));
		} else {
			$this->setMessage($model->getError(), 'error');
		}

		$this->setRedirect(Route::_('index.php?option=com_collector&view=collection&id=' . $collection, false));
	}

	public function save($key = null, $urlVar = null)
	{
		$app    = Factory::getApplication();
		$params = ComponentHelper::getParams('com_collector');

		$isNew  = empty($app->input->get('jform', [], 'array')['id']);
		$result = parent::save($key, $urlVar);

		// Si auto_validate=0, remplacer le message de succès
		if ($result && !$params->get('auto_validate', 1)) {
			// Supprimer le message "Élément enregistré" (ou "Objet proposé") ajouté par FormController
			$messages = $app->getMessageQueue();
			$filtered = array_filter($messages, function($msg) {
				return !\in_array($msg['message'], [Text::_('JLIB_APPLICATION_SAVE_SUCCESS'), Text::_('COM_COLLECTOR_SUBMIT_SAVE_SUCCESS')], true);
			});
			// Vider et remettre les messages filtrés
			while ($app->getMessageQueue()) {
				$app->getMessageQueue(true); // vider
			}
			foreach ($filtered as $msg) {
				$app->enqueueMessage($msg['message'], $msg['type']);
			}
			// Ajouter le message personnalisé
			$app->enqueueMessage(
				Text::_($isNew ? 'COM_COLLECTOR_ITEM_PROPOSED_FOR_VALIDATION' : 'COM_COLLECTOR_ITEM_SUBMITTED_FOR_VALIDATION'),
				'info'
			);
		}

		return $result;
	}

	/**
	 * Vérifie si l'utilisateur peut créer un item dans la collection donnée.
	 */
	protected function allowAdd($data = [])
	{
		$user       = $this->app->getIdentity();
		$collection = (int) ($data['collection'] ?? $this->app->input->getInt('collection'));
		$allow      = $user->authorise('core.create', 'com_collector.collection.' . $collection);

		return $allow ?? parent::allowAdd();
	}

	/**
	 * Vérifie si l'utilisateur peut modifier l'item existant.
	 */
	protected function allowEdit($data = [], $key = 'id')
	{
		$recordId   = (int) ($data[$key] ?? 0);
		$collection = (int) ($data['collection'] ?? 0);
		$user       = $this->app->getIdentity();
		$userId     = $user->get('id');

		// Permission core.edit sur l'item
		if ($user->authorise('core.edit', 'com_collector.item.' . $recordId)) {
			return true;
		}
		// Permission core.edit sur la collection
		if ($user->authorise('core.edit', 'com_collector.collection.' . $collection)) {
			return true;
		}
		// Permission core.edit sur le composant
		if ($user->authorise('core.edit', 'com_collector')) {
			return true;
		}

		// Fallback edit.own
		if (!empty($userId)) {
			foreach (['com_collector.item.' . $recordId, 'com_collector.collection.' . $collection, 'com_collector'] as $asset) {
				if ($user->authorise('core.edit.own', $asset)) {
					// Vérifier la propriété
					$ownerId = (int) ($data['created_by'] ?? 0);
					if ($ownerId === 0 && $recordId) {
						$record  = $this->getModel()->getItem($recordId);
						$ownerId = (int) ($record->created_by ?? 0);
					}
					if ($ownerId === $userId) {
						return true;
					}
				}
			}
		}

		return parent::allowEdit($data, $key);
	}

	/**
	 * Arguments ajoutés à l'URL de redirection vers l'item après sauvegarde.
	 *
	 * Corrige le bug original : '$view=item' → '&view=item'
	 */
	protected function getRedirectToItemAppend($recordId = null, $urlVar = 'id')
	{
		$collection = $this->app->input->getInt('collection');

		if (empty($collection)) {
			$form       = $this->app->input->get('jform', [], 'ARRAY');
			$collection = (int) ($form['collection'] ?? 0);
		}

		// Proposition d'un nouvel objet refusée (champ invalide…) : retour au formulaire de proposition,
		// qui reprend les valeurs saisies
		if (empty($recordId) && $this->app->input->get('view') === 'itemform') {
			$append = '&view=itemform&collection=' . $collection;

			if ($itemId = $this->app->input->getInt('Itemid')) {
				$append .= '&Itemid=' . $itemId;
			}

			return $append;
		}

		$append = parent::getRedirectToItemAppend($recordId, $urlVar);

		$id = $this->app->input->getInt('id');

		if (empty($id)) {
			$form = $this->app->input->get('jform', [], 'ARRAY');
			$id   = (int) ($form['id'] ?? 0);
		}

		// Le '&' manquait dans la version originale (était un '$' littéral)
		$append .= '&view=item&collection=' . $collection . '&id=' . $id;

		return $append;
	}

	/**
	 * Arguments ajoutés à l'URL de redirection vers la liste après annulation.
	 */
	protected function getRedirectToListAppend()
	{
		$append = parent::getRedirectToListAppend();

		$collection = $this->app->input->getInt('collection');

		if (empty($collection)) {
			$form       = $this->app->input->get('jform', [], 'ARRAY');
			$collection = (int) ($form['collection'] ?? 0);
		}

		$append .= '&view=collection&id=' . $collection . '&reset=0';

		return $append;
	}
}