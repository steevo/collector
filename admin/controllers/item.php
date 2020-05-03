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

// Check to ensure this file is included in Joomla!
defined('_JEXEC') or die( 'Restricted access' );
 
/**
 * Item Controller
 */
class CollectorControllerItem extends JControllerForm
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
		$collectionId = JArrayHelper::getValue($data, 'collection', $this->input->getInt('collection'), 'int');
		$allow	= $user->authorise('core.create', 'com_collector.collection.' . $collectionId);

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
		if ($user->authorise('core.edit', 'com_collector.item.'.$recordId)) {
			return true;
		}

		// Fallback on edit.own.
		// First test if the permission is available.
		if ($user->authorise('core.edit.own', 'com_collector.item.'.$recordId)) {
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
	 * Gets the URL arguments to append to an item redirect.
	 *
	 * @param    int        $recordId    The primary key id for the item.
	 * @param    string    $urlVar        The name of the URL variable for the id.
	 *
	 * @return    string    The arguments to append to the redirect URL.
	 */
	protected function getRedirectToItemAppend($recordId = null, $urlVar = 'id')
	{
		$app = JFactory::getApplication();
		
		$append = parent::getRedirectToItemAppend($recordId, $urlVar);
		
		$collection = $app->input->get('collection');
		
		if (empty($collection))
		{
			$form = $app->input->get('jform');
			
			$collection = $form['collection'];
		}
		
		$append .= '&collection='.$collection;
		
		return $append;
    }
	
	/**
	* Gets the URL arguments to append to a list redirect.
	*
	* @return    string    The arguments to append to the redirect URL.
	*/
	protected function getRedirectToListAppend()
	{
		$app = JFactory::getApplication();
		
		$append = parent::getRedirectToListAppend();
		
		$form = $app->input->get('jform');
		
		$append .= '&collection='.$form['collection'];
		
		return $append;
	}
}