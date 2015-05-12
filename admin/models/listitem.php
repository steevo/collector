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

jimport( 'joomla.application.component.modeladmin' );

/**
 * Definedcontentedit model
 * @package	Collector
 */
class CollectorModelListitem extends JModelAdmin
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
	public function getTable($type = 'Collector_defined_content', $prefix = 'Table', $config = array()) 
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
		$form = $this->loadForm('com_collector.listitem', 'listitem', array('control' => 'jform', 'load_data' => $loadData));
		if (empty($form)) 
		{
			return false;
		}
		
		// Determine correct permissions to check.
		if ($id = (int) $this->getState('listitem.id')) {
			// Existing record. Can only edit in selected categories.
			$form->setFieldAttribute('collection', 'action', 'core.edit');
			// Existing record. Can only edit own articles in selected categories.
			$form->setFieldAttribute('collection', 'action', 'core.edit.own');
		}
		else {
			// New record. Can only create in selected categories.
			$form->setFieldAttribute('collection', 'action', 'core.create');
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
		$data = JFactory::getApplication()->getUserState('com_collector.edit.list.data', array());
		if (empty($data)) 
		{
			$data = $this->getItem();
		}
		return $data;
	}
	
	/**
	* Method to get a single record.
	*
	* @param    integer    $pk    The id of the primary key.
	*
	* @return    mixed    Object on success, false on failure.
	*/
	public function getItem($pk = null)
	{
		$item = parent::getItem($pk);
		
		$app = JFactory::getApplication('');
		$item->defined = $app->input->getInt('defined');
		
		$item->listordering = $item->id;
		
		return $item;
	}
	
	/**
	 * A protected method to get the where clause for the reorder
	 * This ensures that the row will be moved relative to a row with the same menutype
	 *
	 * @param   TableCollector_defined_content $table instance
	 *
	 * @return  array  An array of conditions to add to add to ordering queries.
	 * @since   1.6
	 */
	protected function getReorderConditions($table)
	{
		return 'defined = ' . $this->_db->quote($table->defined);
	}

	/**
	 * Method to save the form data.
	 *
	 * @param   array  $data    The form data.
	 *
	 * @return  boolean  True on success.
	 * @since   1.6
	 */
	public function save($data)
	{
		$pk = (!empty($data['id'])) ? $data['id'] : 0;
		$isNew = true;
		$table = $this->getTable();

		// Load the row if saving an existing item.
		if ($pk > 0)
		{
			$table->load($pk);
			$isNew = false;
		}
		if (!$isNew)
		{
			if ($table->parent_id == $data['parent_id'])
			{
				// If first is chosen make the item the first child of the selected parent.
				if ($data['listordering'] == -1)
				{
					$table->setLocation($data['parent_id'], 'first-child');
				}
				// If last is chosen make it the last child of the selected parent.
				elseif ($data['listordering'] == -2)
				{
					$table->setLocation($data['parent_id'], 'last-child');
				}
				// Don't try to put an item after itself. All other ones put after the selected item.
				// $data['id'] is empty means it's a save as copy
				elseif ($data['listordering'] && $table->id != $data['listordering'] || empty($data['id']))
				{
					$table->setLocation($data['listordering'], 'after');
				}
				// Just leave it where it is if no change is made.
				elseif ($data['listordering'] && $table->id == $data['listordering'])
				{
					unset($data['listordering']);
				}
			}
			// Set the new parent id if parent id not matched and put in last position
			else
			{
				$table->setLocation($data['parent_id'], 'last-child');
			}
		}
		// We have a new item, so it is not a change.
		else
		{
			$table->setLocation($data['parent_id'], 'last-child');
		}

		// Bind the data.
		if (!$table->bind($data))
		{
			$this->setError($table->getError());
			return false;
		}

		// Check the data.
		if (!$table->check())
		{
			$this->setError($table->getError());
			return false;
		}

		// Store the data.
		if (!$table->store())
		{
			$this->setError($table->getError());
			return false;
		}

		// Rebuild the tree path.
		if (!$table->rebuildPath($table->id))
		{
			$this->setError($table->getError());
			return false;
		}

		$this->setState('listitem.id', $table->id);

		// Clean the cache
		$this->cleanCache();

		return true;
	}
	
	/**
	 * Method to save the reordered nested set tree.
	 * First we save the new order values in the lft values of the changed ids.
	 * Then we invoke the table rebuild to implement the new ordering.
	 *
	 * @param   array  $idArray      id's of rows to be reordered
	 * @param   array  $lft_array    lft values of rows to be reordered
	 *
	 * @return  boolean false on failuer or error, true otherwise
	 * @since   1.6
	 */
	public function saveorder($idArray = null, $lft_array = null)
	{
		// Get an instance of the table object.
		$table = $this->getTable();

		if (!$table->saveorder($idArray, $lft_array))
		{
			$this->setError($table->getError());
			return false;
		}

		// Clean the cache
		$this->cleanCache();

		return true;
	}

	/**
	 * Custom clean cache method
	 *
	 * @since   1.6
	 */
	protected function cleanCache($group = null, $client_id = 0)
	{
		parent::cleanCache('com_collector');
	}
}
