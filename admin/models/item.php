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

require_once(JPATH_ROOT.'/administrator/components/com_collector/classes/field.php');

/**
 * Item model
 * @package	Collector
 */
class CollectorModelItem extends JModelAdmin
{
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
			return $user->authorise('core.edit.state', 'com_collector.item.'.(int) $record->id);
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
	 * Prepare and sanitise the table data prior to saving.
	 *
	 * @param	JTable	A JTable object.
	 *
	 * @return	void
	 * @since	1.6
	 */
	protected function prepareTable($table)
	{
		// Set the publish date to now
		if($table->state == 1 && intval($table->publish_up) == 0)
		{
			$table->publish_up = JFactory::getDate()->toSql();
		}

		// Reorder the articles within the category so the new article is first
		if (empty($table->id))
		{
			$table->reorder('collection = ' . (int) $table->collection . ' AND state >= 0');
		}
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
	public function getTable($type = 'Collector_items', $prefix = 'Table', $config = array()) 
	{
		return JTable::getInstance($type, $prefix, $config);
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
		// Initialise variables.
		$pk = (!empty($pk)) ? $pk : (int) $this->getState($this->getName().'.id');
		$table	= $this->getTable();
		$input = JFactory::getApplication()->input;
		$post = $input->post;
		$form = $post->get('jform',null,'ARRAY');
		$collection = $input->get( 'collection', $form['collection'], 'int');
		
		if ($pk > 0) {
			// Attempt to load the row.
			$return = $table->loadVersion($collection,$pk);

			// Check for a table object error.
			if ($return === false && $table->getError()) {
				$this->setError($table->getError());
				return false;
			}
		}
		else
		{
			$return = $table->initVersion($collection);
			unset($table->history);
		}

		// Convert to the JObject before adding other data.
		$properties = $table->getProperties(1);
		$item = JArrayHelper::toObject($properties, 'JObject');

		if (property_exists($item, 'params')) {
			$registry = new JRegistry;
			$registry->loadString($item->params);
			$item->params = $registry->toArray();
		}

		// Convert the params field to an array.
		$registry = new JRegistry;
		$registry->loadString($item->metadata);
		$item->metadata = $registry->toArray();
		
		return $item;
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
		$fields = $this->getFieldsCol();
		
		$path = JPATH_ROOT.'/administrator/components/com_collector/models/forms/item.xml';
		$formXML = JFactory::getXML($path);
		
		foreach ( $fields as $field )
		{
			$child = $formXML->addChild('field');
			$attributes = $field->getFieldAttributes();
			foreach ($attributes as $key => $value) {
				$child->addAttribute($key,$value);
			}
		}
		
		// Get the form.
		$form = $this->loadForm('com_collector.item', $formXML->asXML(), array('control' => 'jform', 'load_data' => $loadData));
		if (empty($form)) 
		{
			return false;
		}
		
		// Determine correct permissions to check.
		if ($id = (int) $this->getState('item.id')) {
			// Existing record. Can only edit in selected categories.
			$form->setFieldAttribute('item', 'action', 'core.edit');
			// Existing record. Can only edit own articles in selected categories.
			$form->setFieldAttribute('item', 'action', 'core.edit.own');
		}
		else {
			// New record. Can only create in selected categories.
			$form->setFieldAttribute('item', 'action', 'core.create');
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
		
		$item = $this->getItem();
		
		if ($item->modification != '')
		{
			$form->setFieldAttribute('modification', 'readonly', 'true');
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
		$data = JFactory::getApplication()->getUserState('com_collector.edit.item.data', array());
		if (empty($data)) 
		{
			$data = $this->getItem();
		}
		
		return $data;
	}
	
	/**
	 * Method to load default collection Id in <var>_collection</var>
	 *
	 * @access	private
	 */
	public function getCollection()
	{
		if ( empty($this->_collection) )
		{
			$input = JFactory::getApplication()->input;
			$post = $input->post;
			$form = $post->get('jform',null,'ARRAY');
			$collection = $input->get( 'collection', $form['collection'], 'int');
			
			if ($collection == '')
			{
				$query = 'SELECT id';
				$query .= ' FROM `#__collector`';
				$query .= ' WHERE home = 1';
				
				$row = $this->_getList( $query );
				
				if ( !$row )
				{
					/**
					 * tag à defaut d'une collection
					 */
					$query = 'UPDATE `#__collector` SET home = 1 LIMIT 1;';
					$this->_db->setQuery( $query );
					$this->_db->execute();
					
					$query = 'SELECT id';
					$query .= ' FROM `#__collector`';
					$query .= ' WHERE home = 1';
					
					$row = $this->_getList( $query );
				}
				$collection = $row[0]->id;
			}
			
			// Create a new query object.
			$db = JFactory::getDBO();
			$query = $db->getQuery(true);
			
			// Select the required fields from the table.
			$query->select('id, alias, name, custom');
			$query->from('#__collector');
			
			// Add the filder on ID
			$query->where('id = '.$collection);
			
			$db->setQuery( $query );
			
			$this->_collection = $db->loadObject();
			
			$this->_collection->folder = 'images/collector/collection/'.$this->_collection->alias;
		}
		
		return $this->_collection;
	}

	/**
	 * Method to load listdrop of collections
	 *
	 * @access	public
	 */
	public function getFieldsCol()
	{
		$item = $this->loadFormData();
		
		if ( empty($this->_fields) )
		{
			$collection = $this->getCollection();
			
			// Create a new query object.
			$db = JFactory::getDBO();
			$query = $db->getQuery(true);
			
			// Select the required fields from the table.
			$query->select('f.id, f.field, f.description, f.tablecolumn, f.required, f.attribs');
			$query->from('#__collector_fields as f');
			
			// Join over the type.
			$query->select('t.type AS type');
			$query->join('LEFT', '#__collector_fields_type AS t ON t.id = f.type');
			
			// Filter by collection
			$query->where('f.collection = '.$collection->id);
			
			// Add the list ordering clause.
			$query->order($db->escape('f.ordering ASC'));
			
			$db->setQuery( $query );
			
			$fields = $db->loadObjectList();
			
			foreach ($fields as $field)
			{
				$registry = new JRegistry;
				$registry->loadString($field->attribs);
				$field->attribs = $registry->toArray();
				$fieldObjects[] = CollectorField::getInstance( $collection->id, $field, $item->id );
			}
			$this->_fields = $fieldObjects;
		}
		
		return $this->_fields;
	}
	
	/**
	 * A protected method to get a set of ordering conditions.
	 *
	 * @param    object    $table    A JTable object.
	 *
	 * @return    array    An array of conditions to add to ordering queries.
	 */
    protected function getReorderConditions($table)
    {
		$condition = array();
		$condition[] = 'collection = '.(int) $table->collection;
		$condition[] = 'state >= 0';
        return $condition;
    }
	
	/**
	 * Method to rebuild a fulltitle or an array of fulltitles
	 *
	 * @param   mixed  $pks  The ID of the primary key or an array of IDs
	 *
	 * @return  mixed  Boolean false if there is an error, otherwise the count of records checked in.
	 */
	public function rebuild($pks = array())
	{
		$pks = (array) $pks;
		$table = $this->getTable();
		$input = JFactory::getApplication()->input;
		$collection = $input->get('collection');
		$count = 0;

		if (empty($pks))
		{
			// Create a new query object.
			$db = JFactory::getDBO();
			$query = $db->getQuery(true);
			
			// Select the required fields from the table.
			$query->select('id');
			$query->from('#__collector_items');
			
			// Add the filder on ID
			$query->where('collection = '.$collection);
			
			$db->setQuery( $query );
			
			$pks = $db->loadColumn();
		}

		// Check in all items.
		foreach ($pks as $pk)
		{
			if ($table->loadVersion($collection,$pk))
			{
				if ($table->check())
				{
					if (!$table->storeVersion())
					{
						return false;
					}
					$count++;
				}
			}
			else
			{
				$this->setError($table->getError());

				return false;
			}
		}

		return $count;
	}
	
	/**
	* Method to save the form data.
	*
	* @param   array  $data  The form data.
	*
	* @return  boolean  True on success, False on error.
	* @since   11.1
	*/
	public function save($data)
	{
		// Initialise variables;
		$app = JFactory::getApplication();
		$params = JComponentHelper::getParams( 'com_collector' );
		$dispatcher = JEventDispatcher::getInstance();
		$table = $this->getTable();
		$key = $table->getKeyName();
		$pk = (!empty($data[$key])) ? $data[$key] : (int) $this->getState($this->getName() . '.id');
		$isNew = true;
		$collection = $data['collection'];
		
		// Alter the title for save as copy
		if ($app->input->get('task') == 'save2copy')
		{
			$data['hits'] = 0;
			$data['state'] = 0;
		}
		
		// Include the content plugins for the on save events.
		JPluginHelper::importPlugin('content');

		// Allow an exception to be thrown.
		try
		{
			// Load the row if saving an existing record.
			if ($pk > 0)
			{
				$table->loadVersion($collection,$pk);
				$isNew = false;
			} else {
				$table->initVersion($collection);
			}
	
			// Bind the data.
			if (!$table->bind($data))
			{
				$this->setError($table->getError());
				return false;
			}
	
			// Prepare the row for saving
			$this->prepareTable($table);
	
			// Check the data.
			if (!$table->check())
			{
				$this->setError($table->getError());
				return false;
			}
	
			// Trigger the onContentBeforeSave event.
			$result = $dispatcher->trigger($this->event_before_save, array($this->option . '.' . $this->name, &$table, $isNew));
			if (in_array(false, $result, true))
			{
				$this->setError($table->getError());
				return false;
			}
	
			// Store the data.
			if ( $params->get('save_history') )
			{
				$version = null;
			}
			else
			{
				$version = $table->historyId;
			}
			if (!$table->storeVersion($version))
			{
				$this->setError($table->getError());
				return false;
			}
	
			// Clean the cache.
			$this->cleanCache();
	
			// Trigger the onContentAfterSave event.
			$dispatcher->trigger($this->event_after_save, array($this->option . '.' . $this->name, &$table, $isNew));
		}
		catch (Exception $e)
		{
			$this->setError($e->getMessage());
	
			return false;
		}
	
		$pkName = $table->getKeyName();
	
		if (isset($table->$pkName))
		{
			$this->setState($this->getName() . '.id', $table->$pkName);
		}
		$this->setState($this->getName() . '.new', $isNew);
	
		return true;
	}
}