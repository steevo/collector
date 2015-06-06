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
 * Filelist model
 * @package	Collector
 */
class CollectorModelItemform extends JModelAdmin
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
		$pkc = $app->input->getInt('collection');
		$pki = $app->input->getInt('id');
		$this->setState('collection.id', $pkc);
		$this->setState('item.id', $pki);

		// Load the parameters. Merge Global and Menu Item params into new object
		$params = $app->getParams();
		$menuParams = new JRegistry;

		if ($menu = $app->getMenu()->getActive()) {
			$menuParams->loadString($menu->params);
		}

		$mergedParams = clone $menuParams;
		$mergedParams->merge($params);

		$this->setState('params', $mergedParams);

		// Create a new query object.
		$db		= $this->getDbo();
		$query	= $db->getQuery(true);
		
		$itemid = $app->input->getInt('collection', 0) . ':' . $app->input->getInt('Itemid', 0);
		
		$search_all_value = $app->getUserStateFromRequest('com_collector.collection.'.$itemid.'.filter_search_all', 'filter_search_all', '');
		$this->setState('filter.filter_search_all', $search_all_value);
		
		$orderCol = $app->getUserStateFromRequest('com_collector.collection.' . $itemid . '.filter_order', 'filter_order', 'i.ordering', 'string');
		$this->setState('list.ordering', $orderCol);

		$listOrder = $app->getUserStateFromRequest('com_collector.collection.' . $itemid . '.filter_order_Dir',	'filter_order_Dir', '', 'cmd');
		if (!in_array(strtoupper($listOrder), array('ASC', 'DESC', ''))) {
			$listOrder = 'ASC';
		}
		$this->setState('list.direction', $listOrder);
		
		$limit = $app->getUserStateFromRequest('com_collector.collection.' . $itemid . '.limit', 'limit', $params->get('display_num')?$params->get('display_num'):$app->getCfg('list_limit'));
		$this->setState('list.limit', $limit);
	}

	/**
	 * Method to load default collection Id in <var>_collection</var>
	 *
	 * @access	private
	 */
	public function getCollection()
	{
		$user		= JFactory::getUser();
		
		if ( empty($this->_collection) )
		{
			$collection = $this->getState('collection.id');
			
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
				if ( $row == null )
				{
					return false;
				}
				else
				{
					$collection = $row[0]->id;
				}
			}
			
			// Create a new query object.
			$db = JFactory::getDBO();
			$query = $db->getQuery(true);
			
			// Select the required fields from the table.
			$query->select('c.*, u.name AS author');
			$query->from('#__collector AS c');
			$query->join('LEFT', '#__users AS u ON u.id = c.created_by');
			// Filter by start and end dates.
			$nullDate = $db->Quote($db->getNullDate());
			$nowDate = $db->Quote(JFactory::getDate()->toSql());

			$query->where('((c.created_by = '.$user->id.') OR (c.state = 1 AND ( c.publish_up = '.$nullDate.' OR c.publish_up <= '.$nowDate.') AND ( c.publish_down = '.$nullDate.' OR c.publish_down >= '.$nowDate.' )))');
			
			// Add the filder on ID
			$query->where('c.id = '.$collection);
			
			$db->setQuery( $query );
			
			$this->_collection = $db->loadObject();
			
			if ($this->_collection->custom == 0)
			{
				$query = $db->getQuery(true);
				$query->select('id');
				$query->from('#__collector_fields');
				$query->where('collection = '.$collection);
				$query->where('home = 1');
				
				$db->setQuery( $query );
				
				$this->_collection->custom = $db->loadResult();
			}
			
			$this->_collection->custom = explode('/',$this->_collection->custom);
		}
		
		return $this->_collection;
	}
	
	/**
	 * Method to load fields informations
	 *
	 * @access	private
	 * @return	mixed			Array of fields objects. False if no fields loaded.
	 */
	function getFields()
	{
		if ( empty($this->_fields) )
		{
			if ( $this->_collection->id == '0' )
			{
				return false;
			}
			else
			{
				$collection = $this->_collection->id;
			}
			
			$db		= $this->getDbo();
			$query	= $db->getQuery(true);
			
			$user		= JFactory::getUser();
			$aid		= (int) $user->get('aid', 0);
			
			$jnow		= JFactory::getDate();
			$now		= $jnow->toSql();
			$nullDate	= $db->getNullDate();
			
			$query->select('f.*, u.name AS author');
			$query->from('#__collector_fields AS f');
			
			// Join over the type.
			$query->select('t.type AS type');
			$query->join('LEFT', '#__collector_fields_type AS t ON t.id = f.type');
			
			$query->join('LEFT', '#__users AS u ON u.id = f.created_by');
			$query->where('collection = ' . $collection);
			
			$query->where('( f.created_by = ' . (int) $user->id . ' OR ( f.state = 1 AND ( f.publish_up = '.$db->Quote($nullDate).' OR f.publish_up <= '.$db->Quote($now).' ) AND ( f.publish_down = '.$db->Quote($nullDate).' OR f.publish_down >= '.$db->Quote($now).' ) ) )');
			
			// Filter by access level.
			if ($access = $this->getState('filter.access')) {
				$user	= JFactory::getUser();
				$groups	= implode(',', $user->getAuthorisedViewLevels());
				$query->where('f.access IN ('.$groups.')');
			}
			$query->order('ordering');
			
			$db->setQuery($query);
			$fields = $db->loadObjectList();
			
			if ( ! $fields ) {
				return false;
			}
			
			foreach ($fields as $field)
			{
				$registry = new JRegistry;
				$registry->loadString($field->attribs);
				$field->attribs = $registry->toArray();
				$this->_fields[] = CollectorField::getInstance( $this->_collection->id, $field );
				
				// custom title
				if ( ($key = array_search($field->id, $this->_collection->custom))!==false )
				{
					$this->_collection->custom[$key] = $field->tablecolumn;
				}
			}
		}
		return $this->_fields;
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
		$pk		= (!empty($pk)) ? $pk : (int) $this->getState('item.id');
		
		// Get a row instance.
		$table	= $this->getTable();
		$collection = (int) $this->getState('collection.id');
		
		if ($pk > 0) {
			// Attempt to load the row.
			$return = $table->loadVersion($collection,$pk);

			// Check for a table object error.
			if ($return === false && $table->getError())
			{
				$this->setError($table->getError());
				
				return false;
			}
		}
		else
		{
			// Initialize new item.
			$return = $table->initVersion($collection);
			unset($table->history);
		}

		// Convert to the JObject before adding other data.
		$properties = $table->getProperties(1);
		$item = JArrayHelper::toObject($properties, 'JObject');

		$item->params = new JRegistry;

		// Compute selected asset permissions.
		$user	= JFactory::getUser();
		$userId	= $user->get('id');
		$assetItem = 'com_collector.item.' . $item->id;
		$assetCol = 'com_collector.collection.' . $item->collection;
		
		// Check general edit permission first.
		if ($user->authorise('core.edit', $assetItem))
		{
			$item->params->set('access-edit', true);
		}
		elseif ($user->authorise('core.edit', $assetItem) === null)
		{
			if ($user->authorise('core.edit', $assetCol))
			{
				$item->params->set('access-edit', true);
			}
			elseif ($user->authorise('core.edit', $assetCol) === null)
			{
				if ($user->authorise('core.edit', 'com_collector'))
				{
					$item->params->set('access-edit', true);
				}
			}
		}
		
		// Now check if edit.own is available.
		elseif (!empty($userId) && $user->authorise('core.edit.own', $assetItem))
		{
			// Check for a valid user and that they are the owner.
			if ($userId == $item->created_by)
			{
				$item->params->set('access-edit', true);
			}
		}
		elseif (!empty($userId) && ($user->authorise('core.edit.own', $assetItem) === null))
		{
			if ($user->authorise('core.edit.own', $assetCol))
			{
				$item->params->set('access-edit', true);
			}
			elseif ($user->authorise('core.edit.own', $assetCol) === null)
			{
				if ($user->authorise('core.edit.own', 'com_collector'))
				{
					$item->params->set('access-edit', true);
				}
			}
		}
		
		// Check edit state permission.
		if ($pk)
		{
			// Existing item
			if ($user->authorise('core.edit.state', $assetItem))
			{
				$item->params->set('access-change', true);
			}
			elseif ($user->authorise('core.edit.state', $assetItem) === null)
			{
				if ($user->authorise('core.edit.state', $assetCol))
				{
					$item->params->set('access-change', true);
				}
				elseif ($user->authorise('core.edit.state', $assetCol) === null)
				{
					if ($user->authorise('core.edit.state', 'com_collector'))
					{
						$item->params->set('access-change', true);
					}
				}
			}
		}
		else
		{
			// New item.
			$colId = (int) $this->getState('collection.id');

			if ($colId)
			{
				$item->params->set('access-change', $user->authorise('core.edit.state', 'com_collector.collection.' . $colId));
				$item->collection = $colId;
			}
			else
			{
				$item->params->set('access-change', $user->authorise('core.edit.state', 'com_collector'));
			}
		}
		
		if ($pk)
		{
			// Existing item
			// Check delete permission.
			if ($user->authorise('core.delete', $assetItem))
			{
				$item->params->set('access-delete', true);
			}
			elseif ($user->authorise('core.delete', $assetItem) === null)
			{
				if ($user->authorise('core.delete', $assetCol))
				{
					$item->params->set('access-delete', true);
				}
				elseif ($user->authorise('core.delete', $assetCol) === null)
				{
					if ($user->authorise('core.delete', 'com_collector'))
					{
						$item->params->set('access-delete', true);
					}
				}
			}
		}
		else
		{
			// New item.
			// Check create permission.
			if ($user->authorise('core.create', $assetCol))
			{
				$item->params->set('access-create', true);
			}
			elseif ($user->authorise('core.create', $assetCol) === null)
			{
				if ($user->authorise('core.create', 'com_collector'))
				{
					$item->params->set('access-create', true);
				}
			}
		}
		
		// Convert the params field to an array.
		$registry = new JRegistry;
		$registry->loadString($item->metadata);
		$item->metadata = $registry->toArray();
		
		return $item;
	}
	
	/**
	 * Method to test whether a record can have its state edited.
	 *
	 * @param	object	$record	A record object.
	 *
	 * @return	boolean	True if allowed to change the state of the record. Defaults to the permission set in the component.
	 */
	// protected function canEditState($record)
	// {
		// $user = JFactory::getUser();

		// Check for existing collection.
		// if (!empty($record->id))
		// {
			// return $user->authorise('core.edit.state', 'com_collector.item.'.(int) $record->id);
		// }
		// Default to component settings if no collection known.
		// else
		// {
			// return parent::canEditState('com_collector');
		// }
	// }
	
	/**
     * Method to test whether a record can be deleted.
     *
     * @param   object  $record  A record object.
     *
     * @return  boolean  True if allowed to delete the record. Defaults to the permission for the component.
     *
     * @since   11.1
     */
    // protected function canDelete($record)
    // {
        // $user = JFactory::getUser();
		
		// Check for existing collection.
		// if (!empty($record->id)) {
			// return $user->authorise('core.delete', 'com_collector.item.'.(int) $record->id);
		// }
		// Default to component settings if no collection known.
		// else {
			// return $user->authorise('core.delete', 'com_collector');
		// }
    // }
	
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
		$collection = $this->getCollection();
		$fields = $this->getFields();
		
		$path = JPATH_ROOT.'/administrator/components/com_collector/models/forms/item.xml';
		$formXML = JFactory::getXML($path);
		$fieldset = $formXML->xpath('/form/fieldset');
		
		foreach ( $fields as $field )
		{
			$child = $formXML->addChild('field');
			$attributes = $field->getFieldAttributes();
			foreach ($attributes as $key => $value) {
				$child->addAttribute($key,$value);
			}
			if ( $this->getState('item.id') != null ) {
				if ($field->_field->edit == 0)
				{
					$child->addAttribute('disabled', 'true');
				}
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
		$app		= JFactory::getApplication();
		$params		= $app->getParams();
		// $params		= JComponentHelper::getParams( 'com_collector' );
		$dispatcher	= JDispatcher::getInstance();
		$table		= $this->getTable();
		$key		= $table->getKeyName();
		$pk			= (!empty($data[$key])) ? $data[$key] : (int)$this->getState('item.id');
		$isNew		= true;
		$collection = $data['collection'];
		
		// Include the content plugins for the on save events.
		JPluginHelper::importPlugin('content');

		// Allow an exception to be thrown.
		try
		{
			// Load the row if saving an existing record.
			if ($pk > 0) {
				$table->loadVersion($collection,$pk);
				$isNew = false;
			} else {
				$table->initVersion($collection);
			}
	
			// Bind the data.
			if (!$table->bind($data)) {
				$this->setError($table->getError());
				return false;
			}
	
			// Prepare the row for saving
			$this->prepareTable($table);
	
			// Check the data.
			if (!$table->check()) {
				$this->setError($table->getError());
				return false;
			}
	
			// Trigger the onContentBeforeSave event.
			$result = $dispatcher->trigger($this->event_before_save, array($this->option.'.'.$this->name, &$table, $isNew));
			if (in_array(false, $result, true)) {
				$this->setError($table->getError());
				return false;
			}
	
			// Store the data.
			if ( $params->get('save_history') ) {
				$version = null;
			} else {
				$version = $table->historyId;
			}
			if ( $params->get('auto_publish',1) ) {
				$table->state = 1;
			} else {
				$table->state = 0;
			}
			if (!$table->storeVersion($version)) {
				$this->setError($table->getError());
				return false;
			}
	
			// Clean the cache.
			$this->cleanCache();
	
			// Trigger the onContentAfterSave event.
			$dispatcher->trigger($this->event_after_save, array($this->option.'.'.$this->name, &$table, $isNew));
		}
		catch (Exception $e)
		{
			$this->setError($e->getMessage());
	
			return false;
		}
	
		$pkName = $table->getKeyName();
	
		if (isset($table->$pkName)) {
			$this->setState($this->getName().'.id', $table->$pkName);
		}
		$this->setState($this->getName().'.new', $isNew);
	
		return true;
	}

	/**
	 * Method to get navigation
	 * using FOR loop
	 * Put values in <var>_prev</var> and <var>_next</var>
	 *
	 * @access	public
	 * @return	void
	 */
	function getPrevNext()
	{
		$db			= $this->getDbo();
		$nullDate	= $db->getNullDate();
		$jnow		= JFactory::getDate();
		$now		= $jnow->toSql();
		$query		= $db->getQuery(true);
		
		$user	= JFactory::getUser();
		$groups	= implode(',', $user->getAuthorisedViewLevels());
		
		$app		= JFactory::getApplication();
		$collection	= $this->getCollection();
		$fields		= $this->getFields();
		$params		= $app->getParams();
		
		$this->_prev = 0;
		$this->_next = 0;

		$query->select('i.id, i.alias');
		$query->from('#__collector_items AS i');
		
		// Join over the values.
		$query->join('LEFT', '#__collector_items_history_'.$collection->id.' AS h ON h.item = i.id');
		foreach($fields as $field)
		{
			$field->setQuery($query);
		}
		
		// Join over the users for the author and modified_by names.
		$query->select("CASE WHEN i.created_by_alias > ' ' THEN i.created_by_alias ELSE ua.name END AS author");
		$query->join('LEFT', '#__users AS ua ON ua.id = i.created_by');
		$query->join('LEFT', '#__users AS uam ON uam.id = i.modified_by');
		
		// Filter by item state.
		if ($this->getState('filter.published') == 1)
		{
			$query->where('i.state = 1');
		}
		
		// Filter by history state.
		$query->where('h.state = 1');
		
		// Filter by collection.
		$query->where('i.collection = '.$collection->id);
		
		// Filter by access level.
		$query->where('i.access IN ('.$groups.')');
		
		// Filter by start and end dates.
		if ((!$user->authorise('core.edit.state', 'com_collector.collection.'.(int) $collection->id)) &&  (!$user->authorise('core.edit', 'com_collector.collection.'.(int) $collection->id))){
			$nullDate	= $db->quote($db->getNullDate());
			$nowDate	= $db->quote(JFactory::getDate()->toSql());

			$query->where('(i.publish_up = '.$nullDate.' OR i.publish_up <= '.$nowDate.')')
				->where('(i.publish_down = '.$nullDate.' OR i.publish_down >= '.$nowDate.')');
		}
		
		$whereSearch = array();
		
		$itemid = $app->input->getInt('collection', 0) . ':' . $app->input->getInt('Itemid', 0);
		
		$filter = $params->get('filter');
		
		foreach($fields as $field)
		{
			if ( $field->_field->filter == 1 )
			{
				$nameFilterCollection = 'filterfield_'.$field->_field->tablecolumn;
				if (isset($filter[$nameFilterCollection])) {
					$valueFilterMenu = $filter[$nameFilterCollection];
				} else {
					$valueFilterMenu = '';
				}

				$filtervalue = $app->getUserStateFromRequest('com_collector.collection.' . $itemid . '.'.$nameFilterCollection, $nameFilterCollection, $valueFilterMenu);
				
				$field->setFilterWhereClause($query,$filtervalue);
			}
			
			$search_all_value = $this->getState('filter.filter_search_all');
			if ( $search_all_value != '' )
			{
				$whereSearch[] = $field->getSearchWhereClause($query,$search_all_value);
			}
		}
		
		if (count($whereSearch) > 0 ) {
			$query->where('('.implode(' OR ', $whereSearch).')');
		}
		
		$orderby = $this->getState('list.ordering').' '.$this->getState('list.direction');
		$query->order($orderby);
		
		$db->setQuery($query);
		
		$items = $db->loadRowList();

		$total = count($items);

		$id = $this->getState('item.id');
		
		for($i=0;$i<count($items);$i++)
		{
			if ($items[$i][0] == $id)
			{
				$key = $i;
				break;
			}
		}
		
		if ( $key != 0 )
		{
			$this->_prev = $items[$key - 1][0].':'.$items[$key - 1][1];
		}
		if ( $key != $total - 1 )
		{
			$this->_next = $items[$key + 1][0].':'.$items[$key + 1][1];
		}
		
		$limit = $this->getState('list.limit');
		
		$this->_limitstart = $limit ? ((int)($key/$limit))*$limit : 0;
	}
	
	/**
	 * Method to get html navigation
	 * Put data in <var>_navigation</var>
	 *
	 * @access	public
	 * @return	string Navigation html
	 */
	function getNavigation()
	{
		$app = JFactory::getApplication();
		
		// Load the content if it doesn't already exist
		if (empty($this->_navigation))
		{
			$this->_navigation = '';
			
			$this->getPrevNext();
			
			$prev = $this->_prev;
			$next = $this->_next;
			$img_base = './components/com_collector/assets/images/';
			$link_base = 'index.php';
			$link_back = $link_base;

			$option = $app->input->getVar('option', 0, 'get');
			$collection = $app->input->getVar('collection', 0, 'get');
			$item = $app->input->getVar('id', 0, 'get');
			$Itemid = $app->input->getVar('Itemid', 0, 'get');

			$link_base .= '?option='.$option.'&view=item&collection='.$collection.'&Itemid='.$Itemid;
			$link_back .= '?option='.$option.'&view=collection&id='.$collection.'&limitstart='.$this->_limitstart.'&Itemid='.$Itemid;

			if ($prev == 0)
			{
				$this->_navigation .= '<img src="'.$img_base.'empty.png" />';
			}
			else
			{
				$this->_navigation .= '<a href="'.JRoute::_($link_base.'&id='. $prev) .'" ><img src="'.$img_base.'leftarrow.png" /></a>';
			}

			$this->_navigation .= '<a href="'.JRoute::_($link_back).'" ><img src="'.$img_base.'uparrow.png" /></a>';

			if ($next == 0)
			{
				$this->_navigation .= '<img src="'.$img_base.'empty.png" />';
			}
			else
			{
				$this->_navigation .= '<a href="'.JRoute::_($link_base.'&id='. $next) .'" ><img src="'.$img_base.'rightarrow.png" /></a>';
			}
		}
		return $this->_navigation;
	}
}
