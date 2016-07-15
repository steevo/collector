<?php
/**
 * Joomla! 3.0 component Collector
 *
 * @package 	Collector
 * @copyright   Copyright (C) 2010 - 2015 Philippe Ousset. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 *
 * Collector is a Multi Purpose Listing Tool.
 * Originaly developped to list Collections
 * it can be used for several purpose.
 */

// Check to ensure this file is included in Joomla!
defined('_JEXEC') or die( 'Restricted access' );
 
/**
 * Collection Controller
 */
class CollectorControllerCollection extends JControllerForm
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
		$user	= JFactory::getUser();
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
		$user		= JFactory::getUser();
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
		JSession::checkToken() or jexit(JText::_('JINVALID_TOKEN'));

		// Set the model
		$model	= $this->getModel('Collection', '', array());

		// Preset the redirect
		$this->setRedirect(JRoute::_('index.php?option=com_collector&view=collections' . $this->getRedirectToListAppend(), false));

		// return parent::batch($model);
		$vars = $this->input->post->get('batch', array(), 'array');
		$cid  = $this->input->post->get('cid', array(), 'array');

		// Build an array of item contexts to check
		$contexts = array();

		foreach ($cid as $id)
		{
			// If we're coming from com_categories, we need to use extension vs. option
			if (isset($this->extension))
			{
				$option = $this->extension;
			}
			else
			{
				$option = $this->option;
			}

			$contexts[$id] = $option . '.' . $this->context . '.' . $id;
		}

		// Attempt to run the batch operation.
		$return_batch = $model->batch($vars, $cid, $contexts);
		if ($return_batch===true)
		{
			$this->setMessage(JText::_('JLIB_APPLICATION_SUCCESS_BATCH'));
			// Preset the redirect
			$this->setRedirect(JRoute::_('index.php?option=com_collector&view=collections' . $this->getRedirectToListAppend(), false));

			return true;
		}
		else if ($return_batch===false)
		{
			$this->setMessage(JText::sprintf('JLIB_APPLICATION_ERROR_BATCH_FAILED', $model->getError()), 'warning');
			// Preset the redirect
			$this->setRedirect(JRoute::_('index.php?option=com_collector&view=collections' . $this->getRedirectToListAppend(), false));

			return false;
		}
		else
		{
			$variables = '';
			foreach ($vars as $key => $value) {
				$variables .= '&'.$key.'='.$value;
			}
			$ids = '';
			foreach ($cid as $key => $value) {
				$ids .= '&cid[]='.$value;
			}
			$this->setRedirect(JRoute::_('index.php?option=com_collector&view=copycollection' . $variables . $ids . $this->getRedirectToListAppend(), false));
		}
	}
}