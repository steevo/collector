<?php
/**
 * Joomla! 3.0 component Collector
 *
 * @package 	Collector
 * @copyright   Copyright (C) 2010 - 2020 Philippe Ousset. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 *
 * Collector is a Multi Purpose Listing Tool.
 * Originaly developped to list Collections
 * it can be used for several purpose.
 */

namespace Steevo\Component\Collector\Administrator\Controller;

defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\Controller\FormController;
use Joomla\CMS\Response\JsonResponse;
use Joomla\CMS\Router\Route;
use Joomla\CMS\Session\Session;
use Joomla\Database\DatabaseInterface;
use Steevo\Component\Collector\Administrator\Table\CollectorTable;
use Joomla\CMS\Versioning\VersionableControllerTrait;
use Joomla\Utilities\ArrayHelper;
 
/**
 * Collection Controller
 */
class CollectionController extends FormController
{
	/**
	 * Method override to check if you can add a new record.
	 *
	 * @param	array	An array of input data.
	 *
	 * @return	boolean
	 * @since	1.6
	 */
	protected function allowAdd($data = array())
	{
		// Initialise variables.
		$user	= Factory::getApplication()->getIdentity();
		$allow	= $user->authorise('core.create', 'com_collector');

		if ($allow === null) {
			// In the absense of better information, revert to the component permissions.
			return parent::allowAdd();
		}
		else {
			return $allow;
		}
	}
	
	/**
	 * Method override to check if you can edit an existing record.
	 *
	 * @param	array	$data	An array of input data.
	 * @param	string	$key	The name of the key for the primary key.
	 *
	 * @return	boolean
	 */
	protected function allowEdit($data = array(), $key = 'id')
	{
		// Initialise variables.
		$recordId	= (int) isset($data[$key]) ? $data[$key] : 0;
		$user		= Factory::getApplication()->getIdentity();
		$userId		= $user->get('id');

		// Check general edit permission first.
		if ($user->authorise('core.edit', 'com_collector.collection.'.$recordId)) {
			return true;
		}

		// Fallback on edit.own.
		// First test if the permission is available.
		if ($user->authorise('core.edit.own', 'com_collector')) {
			// Now test the owner is the user.
			$ownerId	= (int) isset($data['created_by']) ? $data['created_by'] : 0;
			if (empty($ownerId) && $recordId) {
				// Need to do a lookup from the model.
				$record		= $this->getModel()->getItem($recordId);

				if (empty($record)) {
					return false;
				}

				$ownerId = $record->created_by;
			}

			// If the owner matches 'me' then do the test.
			if ($ownerId == $userId) {
				return true;
			}
		}

		// Since there is no asset tracking, revert to the component permissions.
		return parent::allowEdit($data, $key);
	}

	/**
	 * Method to run batch operations.
	 *
	 * @param   string  $model  The model
	 *
	 * @return	boolean  True on success.
	 *
	 * @since	2.5
	 */
	public function batch($model = null)
	{
		$this->checkToken();

		// Set the model
		$model	= $this->getModel('Collection', 'Administrator', array());

		// Preset the redirect
		$this->setRedirect(Route::_('index.php?option=com_collector&view=collections' . $this->getRedirectToListAppend(), false));

		// Duplication : la copie se fait par lots successifs depuis la vue copycollection
		$vars     = $this->input->post->get('batch', [], 'array');
		$copyMode = (int) ($vars['copy_mode'] ?? 0);

		if ($copyMode > 0)
		{
			$cid = array_values(array_filter((array) $this->input->post->get('cid', [], 'int')));

			if (empty($cid))
			{
				$this->setMessage(Text::_('JGLOBAL_NO_ITEM_SELECTED'), 'warning');
				return false;
			}

			if (!$this->app->getIdentity()->authorise('core.create', $this->option))
			{
				$this->setMessage(Text::_('JLIB_APPLICATION_ERROR_CREATE_RECORD_NOT_PERMITTED'), 'error');
				return false;
			}

			if (count($cid) > 1)
			{
				$this->app->enqueueMessage(Text::_('COM_COLLECTOR_COLLECTION_COPY_ONLY_FIRST'), 'warning');
			}

			$this->setRedirect(Route::_('index.php?option=com_collector&view=copycollection'
				. '&id=' . $cid[0]
				. '&copy_mode=' . min($copyMode, 3)
				. '&assetgroup_id=' . (int) ($vars['assetgroup_id'] ?? 0), false));

			return true;
		}

		return parent::batch($model);
	}

	/**
	 * Copie d'une collection, par lots (appel AJAX répété depuis la vue copycollection).
	 *
	 * Paramètres POST : id (collection source), mode (1 = champs seuls,
	 * 2 = champs et objets sans historique, 3 = champs et objets avec historique),
	 * assetgroup_id (niveau d'accès de la copie, 0 = inchangé),
	 * new_col (collection de destination, 0 au premier appel).
	 *
	 * Répond en JSON (JsonResponse) : data = updated_fields, remaining_fields,
	 * updated_items, remaining_items, new_col.
	 *
	 * @return  void
	 */
	public function copy(): void
	{
		$this->app->setHeader('Content-Type', 'application/json; charset=utf-8', true);

		try
		{
			if (!Session::checkToken('post'))
			{
				throw new \RuntimeException(Text::_('JINVALID_TOKEN'), 403);
			}

			if (!$this->app->getIdentity()->authorise('core.create', $this->option))
			{
				throw new \RuntimeException(Text::_('JLIB_APPLICATION_ERROR_CREATE_RECORD_NOT_PERMITTED'), 403);
			}

			$id     = $this->input->post->getInt('id', 0);
			$mode   = $this->input->post->getInt('mode', 0);
			$access = $this->input->post->getInt('assetgroup_id', 0);
			$newCol = $this->input->post->getInt('new_col', 0);

			if ($id <= 0 || $mode < 1 || $mode > 3)
			{
				throw new \RuntimeException(Text::_('COM_COLLECTOR_COLLECTION_COPY_SELECT_A_COLLECTION'), 400);
			}

			$table    = new CollectorTable(Factory::getContainer()->get(DatabaseInterface::class));
			$response = $table->copy($id, $mode, $access, $newCol);

			echo new JsonResponse($response);
		}
		catch (\Throwable $e)
		{
			echo new JsonResponse(null, $e->getMessage(), true);
		}

		$this->app->close();
	}
}