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
		$isNew  = empty($app->input->get('jform', [], 'array')['id']);
		$result = parent::save($key, $urlVar);

		// Version enregistrée en attente de validation (modification sans validation automatique, ou
		// proposition sans publication automatique, d'après le modèle) : remplacer le message de succès
		if ($result && $app->get('com_collector.version_pending')) {
			// Le message de succès de FormController (« Élément enregistré », « objet ajouté à la collection »)
			// n'est affiché qu'à la redirection : on le remplace par celui de l'attente de validation
			$this->setMessage(
				Text::_($isNew ? 'COM_COLLECTOR_ITEM_PROPOSED_FOR_VALIDATION' : 'COM_COLLECTOR_ITEM_SUBMITTED_FOR_VALIDATION'),
				'info'
			);
		}

		return $result;
	}

	/**
	 * Valide une proposition (objet sans version validée) depuis l'onglet de modification de sa fiche :
	 * enregistre les corrections dans la version en attente, la valide et publie l'objet (voir ItemModel::save),
	 * puis revient à la liste des objets à valider. Réservé aux validateurs de la collection.
	 */
	public function validateproposal()
	{
		$this->checkToken();

		$data       = $this->input->post->get('jform', [], 'array');
		$collection = (int) ($data['collection'] ?? 0);
		$user       = $this->app->getIdentity();

		if (!$user->authorise('core.edit', 'com_collector.collection.' . $collection) && !$user->authorise('core.edit', 'com_collector'))
		{
			$this->setMessage(Text::_('JERROR_ALERTNOAUTHOR'), 'error');
			$this->setRedirect(Route::_('index.php?option=com_collector&view=collection&id=' . $collection, false));

			return false;
		}

		// Le modèle enregistre alors dans la version en attente et la valide (voir ItemModel::save)
		$this->app->set('com_collector.validate_proposal', 1);

		$result = $this->save();

		if ($result)
		{
			$this->setMessage(Text::_('COM_COLLECTOR_PROPOSAL_VALIDATED'));
			$this->setRedirect(Route::_('index.php?option=com_collector&view=itemstovalidate&id=' . $collection, false));
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