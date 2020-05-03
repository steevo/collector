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

// import Joomla modelform library
jimport('joomla.application.component.modeladmin');
 
/**
 * Collection model
 * @package	Collector
 */
class CollectorModelCollection extends JModelAdmin
{
	/**
	 * Returns a reference to the a Table object, always creating it.
	 *
	 * @param	type	The table type to instantiate
	 * @param	string	A prefix for the table class name. Optional.
	 * @param	array	Configuration array for model. Optional.
	 * @return	JTable	A database object
	 * @since	1.6
	 */
	public function getTable($type = 'Collector', $prefix = 'Table', $config = array()) 
	{
		
		return JTable::getInstance($type, $prefix, $config);
	}
	
	/**
	 * Method to get a single record.
	 *
	 * @param	integer	The id of the primary key.
	 *
	 * @return	mixed	Object on success, false on failure.
	 */
	public function getItem($pk = null)
	{
		if ($item = parent::getItem($pk)) {
			// Convert the params field to an array.
			$registry = new JRegistry;
			$registry->loadString($item->metadata);
			$item->metadata = $registry->toArray();
		}

		return $item;
	}
	
	/**
	 * Method to test whether a record can have its state edited.
	 *
	 * @param	object	$record	A record object.
	 *
	 * @return	boolean	True if allowed to change the state of the record. Defaults to the permission set in the component.
	 */
	protected function canEditState($record)
	{
		$user = JFactory::getUser();

		// Check for existing collection.
		if (!empty($record->id)) {
			return $user->authorise('core.edit.state', 'com_collector.collection.'.(int) $record->id);
		}
		// Default to component settings if no collection known.
		else {
			return $user->authorise('core.edit.state', 'com_collector');
		}
	}
	
	/**
	 * Method to check if you can save a record.
	 *
	 * @param	array	$data	An array of input data.
	 * @param	string	$key	The name of the key for the primary key.
	 *
	 * @return	boolean
	 */
	protected function canSave($data = array(), $key = 'id')
	{
		return JFactory::getUser()->authorise('core.edit', 'com_collector');
	}
	
	/**
	 * Method to get the record form.
	 *
	 * @param	array	$data		Data for the form.
	 * @param	boolean	$loadData	True if the form is to load its own data (default case), false if not.
	 * @return	mixed	A JForm object on success, false on failure
	 * @since	1.6
	 */
	public function getForm($data = array(), $loadData = true) 
	{
		// Get the form.
		$form = $this->loadForm('com_collector.collection', 'collection', array('control' => 'jform', 'load_data' => $loadData));
		if (empty($form)) 
		{
			return false;
		}
		
		// Determine correct permissions to check.
		if ($id = (int) $this->getState('collection.id')) {
			// Existing record. Can only edit in selected categories.
			$form->setFieldAttribute('collection', 'action', 'core.edit');
			// Existing record. Can only edit own articles in selected categories.
			$form->setFieldAttribute('collection', 'action', 'core.edit.own');
		}
		else {
			// New record. Can only create in selected categories.
			$form->setFieldAttribute('collection', 'action', 'core.create');
		}

		// Modify the form based on Edit State access controls.
		if (!$this->canEditState((object) $data)) {
			// Disable fields for display.
			$form->setFieldAttribute('ordering', 'disabled', 'true');
			$form->setFieldAttribute('publish_up', 'disabled', 'true');
			$form->setFieldAttribute('publish_down', 'disabled', 'true');
			$form->setFieldAttribute('state', 'disabled', 'true');

			// Disable fields while saving.
			// The controller has already verified this is an article you can edit.
			$form->setFieldAttribute('ordering', 'filter', 'unset');
			$form->setFieldAttribute('publish_up', 'filter', 'unset');
			$form->setFieldAttribute('publish_down', 'filter', 'unset');
			$form->setFieldAttribute('state', 'filter', 'unset');
		}
		
		return $form;
	}
	
	/**
	 * Method to get the data that should be injected in the form.
	 *
	 * @return	mixed	The data for the form.
	 * @since	1.6
	 */
	protected function loadFormData() 
	{
		// Check the session for previously entered form data.
		$data = JFactory::getApplication()->getUserState('com_collector.edit.collection.data', array());
		if (empty($data)) 
		{
			$data = $this->getItem();
		}
		return $data;
	}
	
	/**
	 * Method to set a collection as default
	 *
	 * @access	public
	 * @param	int		$item	Collection Id
	 * @return	boolean			True on success
	 */
	function setHome( $item )
	{
		// Initialise variables.
		$table		= $this->getTable();
		$user		= JFactory::getUser();
		$db			= $this->getDbo();
		
		if ($table->load($item)) {
			if ($table->home == 1) {
				JFactory::getApplication()->enqueueMessage(JText::_('COM_COLLECTOR_ERROR_ALREADY_HOME'),'notice');
			}
			else {
				$table->home = 1;
				if (!$this->canSave($table)) {
					// Prune items that you can't change.
					JFactory::getApplication()->enqueueMessage(JText::_('JLIB_APPLICATION_ERROR_SAVE_NOT_PERMITTED'),'warning');
				}
				else if (!$table->check()) {
					// Prune the items that failed pre-save checks.
					JFactory::getApplication()->enqueueMessage($table->getError(),'warning');
				}
				else if (!$table->store()) {
					// Prune the items that could not be stored.
					JFactory::getApplication()->enqueueMessage($table->getError(),'warning');
				}
				// Clear home field for all other items
				$query = 'UPDATE #__collector' .
						' SET home = 0' .
						' WHERE id<>'.$table->id;
				$db->setQuery( $query );
				if ( !$db->execute() ) {
					JFactory::getApplication()->enqueueMessage($table->getError(),'warning');
				}
			}
		}
		
		return true;
	}
	
	/**
	 * Method to perform batch operations on an item or a set of items.
	 *
	 * @param   array   $commands   An array of commands to perform.
	 * @param   array   $pks        An array of item ids.
	 * @param   array   $contexts   An array of item contexts.
	 *
	 * @return	boolean	 Returns true on success, false on failure.
	 *
	 * @since	2.5
	 */
	public function batch($commands, $pks, $contexts)
	{
		// Sanitize user ids.
		$pks = array_unique($pks);
		JArrayHelper::toInteger($pks);

		// Remove any values of zero.
		if (array_search(0, $pks, true))
		{
			unset($pks[array_search(0, $pks, true)]);
		}

		if (empty($pks))
		{
			$this->setError(JText::_('JGLOBAL_NO_ITEM_SELECTED'));
			return false;
		}

		$done = false;

		if (!empty($commands['copy_mode']))
		{
			if ( $commands['copy_mode'] == 0 )
			{
				if (!empty($commands['assetgroup_id']))
		        {
		            if (!$this->batchAccess($commands['assetgroup_id'], $pks, $contexts))
		            {
		                return false;
		            }
		 
		            $done = true;
		        }
		    } else {
		    	return "copy";
		    }
	    }

		if (!$done)
		{
			$this->setError(JText::_('JLIB_APPLICATION_ERROR_INSUFFICIENT_BATCH_INFORMATION'));
			return false;
		}

		// Clear the cache
		$this->cleanCache();

		return true;
	}
	
}