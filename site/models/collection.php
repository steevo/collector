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
 * Collection model
 * @package	Collector
 */
class CollectorModelCollection extends JModelList
{
	/**
	 * Collection Id
	 * @var int
	 */
	var $_collection;
	
	/**
	 * Fields
	 * @var int
	 */
	var $_fields = array();
	
	/**
	 * Userslists
	 * @var int
	 */
	var $_userslists = array();
	
	/**
	 * Usersitems
	 * @var int
	 */
	var $_usersitems = array();
	
	/**
	 * To know if there is a research
	 * @var boolean
	 */
	var $_search = 0;
	
	/**
	 * Constructor.
	 *
	 * @param	array	An optional associative array of configuration settings.
	 * @see		JController
	 * @since	1.6
	 */
	public function __construct($config = array())
	{
		if (empty($config['filter_fields'])) {
			$config['filter_fields'] = array(
				'id', 'i.id',
				'alias', 'i.alias',
				'collection', 'i.collection',
				'state', 'i.state',
				'ordering', 'i.ordering',
				'hits', 'i.hits',
				'created', 'i.created',
				'created_by', 'i.created_by',
				'checked_out', 'i.checked_out',
				'checked_out_time', 'i.checked_out_time',
				'publish_up', 'i.publish_up',
				'publish_down', 'i.publish_down',
				'access', 'i.access', 'access_level',
			);
		}

		parent::__construct($config);
	}
	
	/**
	 * Method to auto-populate the model state.
	 *
	 * Note. Calling getState in this method will result in recursion.
	 *
	 * return	void
	 * @since	1.6
	 */
	protected function populateState($ordering = 'i.ordering', $direction = 'ASC')
	{
		// Initiliase variables.
		$app	= JFactory::getApplication();
		$pk		= $app->input->getInt('id');
		
		$this->setState('collection.id', $pk);

		// Load the parameters. Merge Global and Menu Item params into new object
		$params = $app->getParams();
		$menuParams = new JRegistry;

		if ($menu = $app->getMenu()->getActive()) {
			$menuParams->loadString($menu->params);
		}

		$mergedParams = clone $menuParams;
		$mergedParams->merge($params);

		$this->setState('params', $mergedParams);
		$user		= JFactory::getUser();
		
		// Create a new query object.
		$db		= $this->getDbo();
		$query	= $db->getQuery(true);
		
		if ((!$user->authorise('core.edit.state', 'com_collector.collection.'.(int) $pk)) &&  (!$user->authorise('core.edit', 'com_collector.collection.'.(int) $pk)))
		{
			// limit to published for people who can't edit or edit.state.
			$this->setState('filter.published', 1);
		}

		// process show_noauth parameter
		if (!$params->get('show_noauth')) {
			$this->setState('filter.access', true);
		}
		else {
			$this->setState('filter.access', false);
		}

		// filter
		$itemid = $app->input->getInt('id', 0) . ':' . $app->input->getInt('Itemid', 0);
		$reset = $app->input->getInt('reset', 0);
		if ( $reset == 1 ) {
			$app->setUserState('com_collector.collection.' . $itemid . '.filter_search_all', '');
			$search_all_value = '';
		} else {
			$search_all_value = $app->getUserStateFromRequest('com_collector.collection.'.$itemid.'.filter_search_all', 'filter_search_all', '');
		}
		$this->setState('filter.filter_search_all', $search_all_value);
		
		$orderCol = $app->getUserStateFromRequest('com_collector.collection.' . $itemid . '.filter_order', 'filter_order', 'i.ordering', 'string');
		$this->setState('list.ordering', $orderCol);

		$listOrder = $app->getUserStateFromRequest('com_collector.collection.' . $itemid . '.filter_order_Dir',	'filter_order_Dir', '', 'cmd');
		if (!in_array(strtoupper($listOrder), array('ASC', 'DESC', ''))) {
			$listOrder = 'ASC';
		}
		$this->setState('list.direction', $listOrder);

		$start = $app->getUserStateFromRequest('com_collector.collection.' . $itemid . '.limitstart', 'limitstart', 0);
		$this->setState('list.start', $start);

		$limit = $app->getUserStateFromRequest('com_collector.collection.' . $itemid . '.limit', 'limit', $params->get('display_num')?$params->get('display_num'):$app->getCfg('list_limit'));
		$this->setState('list.limit', $limit);

		$this->setState('layout', $app->input->getCmd('layout'));
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
			
			$registry = new JRegistry;
			$registry->loadString($this->_collection->metadata);
			$this->_collection->metadata = $registry;
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
			}
		}
		return $this->_fields;
	}
	
	/**
	 * Method to load userslists informations
	 *
	 * @access	private
	 * @return	mixed			Array of userslists objects. False if no userslists loaded.
	 */
	function getUserslists()
	{
		$user = JFactory::getUser();
		if ($user->guest)
		{
			return false;
		}
		
		if ( empty($this->_userslists) )
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
			
			
			$jnow		= JFactory::getDate();
			$now		= $jnow->toSql();
			$nullDate	= $db->getNullDate();
			
			$query->select('ul.*');
			$query->from('#__collector_userslists AS ul');
			
			$query->where('( ul.created_by = ' . (int) $user->id . ' OR ( ul.state = 1 AND ( ul.publish_up = '.$db->Quote($nullDate).' OR ul.publish_up <= '.$db->Quote($now).' ) AND ( ul.publish_down = '.$db->Quote($nullDate).' OR ul.publish_down >= '.$db->Quote($now).' ) ) )');
			$query->where('collection = ' . $collection);
			
			// Filter by access level.
			if ($access = $this->getState('filter.access')) {
				$groups	= implode(',', $user->getAuthorisedViewLevels());
				$query->where('ul.access IN ('.$groups.')');
			}
			$query->order('ordering');
			
			$db->setQuery($query);
			$userslists = $db->loadObjectList('id');
			
			if ( ! $userslists ) {
				return false;
			}
			
			$this->_userslists = $userslists;
		}
		
		return $this->_userslists;
	}
	
	/**
	 * Method to load usersitems informations
	 *
	 * @access	private
	 * @return	mixed			Array of usersitems objects. False if no usersitems loaded.
	 */
	function getUsersitems()
	{
		if ( empty($this->_usersitems) )
		{
			if ( empty($this->_userslists) )
			{
				return false;
			}
			
			$this->_usersitems = array();
			
			$user	= JFactory::getUser();
			
			foreach ($this->_userslists as $userslist)
			{
				$db		= $this->getDbo();
				$query	= $db->getQuery(true);
				
				$user		= JFactory::getUser();
				$aid		= (int) $user->get('aid', 0);
				
				$jnow		= JFactory::getDate();
				$now		= $jnow->toSql();
				$nullDate	= $db->getNullDate();
				
				$query->select('ui.*');
				$query->from('#__collector_usersitems AS ui');
				
				// Join over the userlist.
				$query->join('LEFT', '#__collector_userlist AS ul ON ul.id = ui.userlist');
				$query->where('ul.userslist = '.$userslist->id);
				
				$query->where('ul.user = '.$user->id);
				
				$db->setQuery($query);
				$usersitems = $db->loadObjectList('itemid');
				
				$this->_usersitems[$userslist->id] = $usersitems;
			}
		}
		
		return $this->_usersitems;
	}
	
	/**
	 * Build an SQL query to load the list data.
	 *
	 * @return	JDatabaseQuery
	 */
	protected function getListQuery()
	{
		// Get collection
		$app		= JFactory::getApplication();
		$collection	= $this->getCollection();
		$fields		= $this->getFields();
		$params		= $app->getParams();
		
		$reset = $app->input->getInt('reset', 0);
		
		$user	= JFactory::getUser();
		
		// Create a new query object.
		$db			= $this->getDbo();
		$query		= $db->getQuery(true);
		
		// Select the required fields from the table.
		$query->select(
			$this->getState(
				'list.select',
				'i.id, i.alias, i.collection, i.ordering, i.state, i.hits, i.created, i.created_by, i.created_by_alias, i.modified, i.modified_by, ' .
				'i.access, i.publish_up, i.publish_down, i.checked_out, i.checked_out_time, ' .
				// use created if modified is 0
				'CASE WHEN i.modified = 0 THEN i.created ELSE i.modified END as modified, ' .
					'i.modified_by, uam.name as modified_by_name'
			)
		);
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
		$groups	= implode(',', $user->getAuthorisedViewLevels());
		$query->where('i.access IN ('.$groups.')');
		
		// Filter by start and end dates.
		if ((!$user->authorise('core.edit.state', 'com_collector.collection.'.(int) $collection->id)) &&  (!$user->authorise('core.edit', 'com_collector.collection.'.(int) $collection->id))){
			$nullDate	= $db->quote($db->getNullDate());
			$nowDate	= $db->quote(JFactory::getDate()->toSql());

			$query->where('(i.publish_up = '.$nullDate.' OR i.publish_up <= '.$nowDate.')')
				->where('(i.publish_down = '.$nullDate.' OR i.publish_down >= '.$nowDate.')');
		}
		
		$whereSearch = array();
		
		$itemid = $app->input->getInt('id', 0) . ':' . $app->input->getInt('Itemid', 0);
		
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

				if ( $reset == 1 ) {
					$app->setUserState('com_collector.collection.' . $itemid . '.'.$nameFilterCollection, $valueFilterMenu);
					$filtervalue = $valueFilterMenu;
				} else {
					$filtervalue = $app->getUserStateFromRequest('com_collector.collection.' . $itemid . '.'.$nameFilterCollection, $nameFilterCollection, $valueFilterMenu);
				}
				$this->setState('filter.'.$nameFilterCollection, $filtervalue);
				
				if ($field->setFilterWhereClause($query,$filtervalue,$params))
				{
					$this->_search = 1;
				}
			}
			
			$search_all_value = $this->getState('filter.filter_search_all');
			if ( $search_all_value != '' )
			{
				$this->_search = 1;
				$whereSearch[] = $field->getSearchWhereClause($query,$search_all_value);
			}
		}
		
		if (count($whereSearch) > 0 ) {
			$query->where('('.implode(' OR ', $whereSearch).')');
		}
		
		// Add the list ordering clause.
		$query->order($this->_buildCollectionOrderBy());
		
		return $query;
	}
	
	/**
	 * Method to build the ORDER BY clause of the query to select items
	 *
	 * @access	private
	 * @return	string	ORDER BY clause
	 */
	function _buildCollectionOrderBy()
	{
		$app = JFactory::getApplication();
		
		$itemid = $app->input->getInt('id', 0) . ':' . $app->input->getInt('Itemid', 0);
		$reset = $app->input->getInt('reset', 0);
		
		$fields_order = array();
		
		$order = '';
		
		$collection	= $this->getCollection();
		$fields		= $this->getFields();
		$params		= $app->getParams();
		
		$special_order		= $params->get('order');
		if ( $special_order == 'ordering' ) {
			$order_default = 'i.ordering';
			$order_dir_default = '';
		} else if ( $special_order == 'created' ) {
			$order_default = 'i.created';
			$order_dir_default = '';
		} else if ( $special_order == 'rcreated' ) {
			$order_default = 'i.created';
			$order_dir_default = 'desc';
		} else if ( $special_order == 'modified' ) {
			$order_default = 'h.modified';
			$order_dir_default = 'desc';
		} else if ( $special_order == 'rmodified' ) {
			$order_default = 'h.modified';
			$order_dir_default = '';
		} else if ( $special_order == 'default' ) {
			foreach ($fields as $field)
			{
				$fields_order[$field->_field->id] = $field->getOrderBy();
			}
			foreach ( $collection->custom as $field )
			{
				if ( $order != '' )
				{
					$order .= ' , ';
				}
				$order .= $fields_order[$field];
			}
			$order_default = $order;
			$order_dir_default = '';
		} else {
			$order_default = 'i.ordering';
			$order_dir_default = '';
		}
		
		$this->setState('list.default.ordering', $order_default);
		$this->setState('list.default.direction', $order_dir_default);
		
		if ( $reset ) {
			$app->setUserState('com_collector.collection.' . $itemid . '.filter_order', $order_default);
			$this->setState('list.ordering', $order_default);
			$app->setUserState('com_collector.collection.' . $itemid . '.filter_order_Dir', $order_dir_default);
			$this->setState('list.direction', $order_dir_default);
		}
		
		$orderby = $this->getState('list.ordering').' '.$this->getState('list.direction');
		
		return $orderby;
	}
	
	/**
	 * Tests if collection is searched
	 *
	 * @access	public
	 * @return	boolean	True if searched
	 */
	function getSearched()
	{
		if ( $this->_search == 1 )
		{
			return true;
		}
		
		return false;
	}
}