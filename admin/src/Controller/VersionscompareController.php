<?php
/**
 * @package    Collector
 * @copyright  Copyright (C) 2010 - 2020 Philippe Ousset. All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace Steevo\Component\Collector\Administrator\Controller;

defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\Controller\AdminController;
use Joomla\CMS\Session\Session;

/**
 * Contrôleur pour la comparaison et la validation de versions d'un item.
 */
class VersionscompareController extends AdminController
{
	public function getModel(
		$name   = 'Versionscompare',
		$prefix = 'Administrator',
		$config = ['ignore_request' => true]
	) {
		return parent::getModel($name, $prefix, $config);
	}

	/**
	 * Valide la fusion de deux versions champ par champ.
	 * Appelé en AJAX (fetch) depuis la popup de comparaison.
	 * Retourne du JSON : { success, message, redirectUrl? }
	 */
	public function validate()
	{
		header('Content-Type: application/json; charset=utf-8');

		if (!Session::checkToken('post')) {
			echo json_encode(['success' => false, 'message' => Text::_('JINVALID_TOKEN')]);
			exit();
		}

		$app        = Factory::getApplication();
		$input      = $app->input;
		$collection = $input->getInt('collection');
		$item       = $input->getInt('item');
		$v2         = $input->getInt('v2');
		$refuseNote  = $input->getString('refuse_note', '');

		// Nombre de modifications acceptées (Oui) — transmis par le JS du template
		$acceptedCount = $input->getInt('accepted_count', 0);

		// Valeurs brutes choisies champ par champ (v1_raw ou v2_raw selon le toggle)
		$fieldValues = $input->get('fields', [], 'RAW');
		$fieldValues['refuse_note'] = $refuseNote;

		/** @var \Steevo\Component\Collector\Administrator\Model\VersionscompareModel $model */
		$model = $this->getModel();

		if ($model->validateVersion($collection, $item, $v2, (array) $fieldValues, $acceptedCount)) {
			
			// Message différencié selon le cas
			$messageKey = ($acceptedCount > 0)
				? 'COM_COLLECTOR_ITEM_HISTORY_COMPARE_VALIDATED'
				: 'COM_COLLECTOR_ITEM_HISTORY_COMPARE_ARCHIVED_NO_CHANGE';

			// Persiste le message en session pour que la page items puisse l'afficher
			// après son rechargement, sans passer par sessionStorage côté client.
			$app->enqueueMessage(Text::_($messageKey), 'success');
			$app->getSession()->set('application.queue', $app->getMessageQueue());
 
			// La popup redirige window.opener (= fenêtre itemversions) vers la
			// liste items. C'est une navigation de page normale : Joomla affichera
			// le message enqueued automatiquement.
			$redirectUrl = 'index.php?option=com_collector&view=itemversions&collection=' . $collection . '&item=' . $item;
			
			echo json_encode([
				'success'     => true,
				'message'     => Text::_($messageKey),
				'redirectUrl' => $redirectUrl,
			]);
			
		} else {
			echo json_encode([
				'success' => false,
				'message' => $model->getError() ?: Text::_('JERROR_AN_ERROR_HAS_OCCURRED'),
			]);
		}

		exit();
	}
}