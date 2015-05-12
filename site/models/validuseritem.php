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
 * Validuseritem model
 * @package	Collector
 */
class CollectorModelValiduseritem extends JModelAdmin
{
	/**
	 * Method to auto-populate the model state.
	 *
	 * Note. Calling getState in this method will result in recursion.
	 *
	 * @since	1.6
	 */
	protected function populateState()
	{
		$app = JFactory::getApplication('site');

		// Load state from the request.
		$collection = $app->input->getInt('collection');
		$item = $app->input->getInt('item');
		$userlist = $app->input->getInt('userlist');
		
		$this->setState('collection.id', $collection);
		$this->setState('item.id', $item);
		$this->setState('userlist.id', $userlist);
	}

	/**
	 * Method to load list informations
	 *
	 * @access	private
	 * @return	mixed			Array of fields objects. False if no fields loaded.
	 */
	function getList()
	{
		if ( empty($this->_list) )
		{
			$userlist = (int) $this->getState('userlist.id');
			
			$db		= $this->getDbo();
			$query	= $db->getQuery(true);
			
			$user		= JFactory::getUser();
			$aid		= (int) $user->get('aid', 0);
			
			$jnow		= JFactory::getDate();
			$now		= $jnow->toSql();
			$nullDate	= $db->getNullDate();
			
			$query->select('l.*');
			$query->from('#__collector_userslists AS l');
			$query->where('id = ' . $userlist);
			
			$db->setQuery($query);
			$list = $db->loadObject();
			
			if ( ! $list ) {
				return false;
			}
			
			$this->_list = $list;
		}
		return $this->_list;
	}
	
	/**
	 * Returns a reference to the a Table object, always creating it.
	 *
	 * @param	type	The table type to instantiate
	 * @param	string	A prefix for the table class name. Optional.
	 * @param	array	Configuration array for model. Optional.
	 * @return	JTable	A database object
	 * @since	1.6
	 */
	public function getTable($type = 'Collector_usersitems', $prefix = 'Table', $config = array()) 
	{
		return JTable::getInstance($type, $prefix, $config);
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
		$form = $this->loadForm('com_collector.useritem', 'useritem', array('control' => 'jform', 'load_data' => $loadData));
		if (empty($form)) 
		{
			return false;
		}		
		
		// Determine correct permissions to check.
		if ($id = (int) $this->getState('usersitem.id')) {
			// Existing record. Can only edit in selected categories.
			$form->setFieldAttribute('usersitem', 'action', 'core.edit');
			// Existing record. Can only edit own articles in selected categories.
			$form->setFieldAttribute('usersitem', 'action', 'core.edit.own');
		}
		else {
			// New record. Can only create in selected categories.
			$form->setFieldAttribute('usersitem', 'action', 'core.create');
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
	 * Method to get a single record.
	 *
	 * @param   integer  $pk  The id of the primary key.
	 *
	 * @return  mixed    Object on success, false on failure.
	 * @since   11.1
	 */
	public function getItem($pk = null)
	{
		$item = (int) $this->getState('item.id');
		
		// Get a row instance.
		$table	= $this->getTable('Collector_items');
		$collection = (int) $this->getState('collection.id');
		
		// Attempt to load the row.
		$return = $table->loadVersion($collection,$item);

		// Check for a table object error.
		if ($return === false && $table->getError())
		{
			$this->setError($table->getError());
			
			return false;
		}
		
		return $table;
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
		$data = JFactory::getApplication()->getUserState('com_collector.edit.usersitem.data', array());
		if (empty($data)) 
		{
			$data = $this->getItem();
		}
		return $data;
	}
}