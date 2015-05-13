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
 * Items model
 * @package	Collector
 */
class CollectorModelItems extends JModelList
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
				'created_by', 'i.created_by', 'author_id',
				'checked_out', 'i.checked_out',
				'checked_out_time', 'i.checked_out_time',
				'publish_up', 'i.publish_up',
				'publish_down', 'i.publish_down',
				'published', 'f.published',
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
	 * @return	void
	 * @since	1.6
	 */
	protected function populateState($ordering = null, $direction = null)
	{
		// Initialise variables.
		$app = JFactory::getApplication();

		// Adjust the context to support modal layouts.
		if ($layout = $app->input->getVar('layout'))
		{
			$this->context .= '.' . $layout;
		}

		$collection = $this->getCollection();
		
		if ($collection->id != $app->getUserState('com_collector.items.collection'))
		{
			$app->setUserState($this->context.'.collection', $collection->id);
			$app->input->set('limitstart', 0);
		}
		
		$this->setState('filter.collection', $collection->id);

		$search = $this->getUserStateFromRequest($this->context.'.filter.search', 'filter_search');
		$this->setState('filter.search', $search);

		$access = $this->getUserStateFromRequest($this->context.'.filter.access', 'filter_access', 0, 'int');
		$this->setState('filter.access', $access);

		$authorId = $app->getUserStateFromRequest($this->context . '.filter.author_id', 'filter_author_id');
		$this->setState('filter.author_id', $authorId);

		$published = $this->getUserStateFromRequest($this->context.'.filter.published', 'filter_published', '');
		$this->setState('filter.published', $published);

		// List state information.
		parent::populateState('i.ordering', 'asc');
	}
	
	/**
	 * Build an SQL query to load the list data.
	 *
	 * @return	JDatabaseQuery
	 */
	protected function getListQuery()
	{
		// Get collection
		$collection = $this->getState('filter.collection');
		$fields = $this->getFields();
		
		// Create a new query object.
		$db = $this->getDbo();
		$query = $db->getQuery(true);
		$user = JFactory::getUser();
		
		// Select the required fields from the table.
		$query->select(
			$this->getState(
				'list.select',
				'i.id, i.alias, i.fulltitle, i.collection, i.ordering, i.state, i.hits, i.created, i.created_by, i.created_by_alias, i.modified, i.modified_by' .
				', i.access, i.publish_up, i.publish_down, i.checked_out, i.checked_out_time'
			)
		);
		$query->from('#__collector_items AS i');
		
		// Join over the values.
		$query->join('LEFT', '#__collector_items_history_' . $collection . ' AS h ON h.item = i.id');
		foreach($fields as $field)
		{
			$field->setQuery($query);
		}
		
		// Join over the users for the checked out user.
		$query->select('uc.name AS editor');
		$query->join('LEFT', '#__users AS uc ON uc.id = i.checked_out');
		
		// Join over the asset groups.
		$query->select('ag.title AS access_level');
		$query->join('LEFT', '#__viewlevels AS ag ON ag.id = i.access');
		
		// Join over the users for the author.
		$query->select('ua.name AS author_name');
		$query->join('LEFT', '#__users AS ua ON ua.id = i.created_by');
		
		// Filter by collection
		$query->where('i.collection = ' . $collection);
		
		// Filter by history
		$query->where('h.state = 1');
		
		// Filter by access level.
		if ($access = $this->getState('filter.access')) {
			$query->where('i.access = ' . (int) $access);
		}
		
		// Implement View Level Access
		if (!$user->authorise('core.admin'))
		{
			$groups = implode(',', $user->getAuthorisedViewLevels());
			$query->where('i.access IN (' . $groups . ')');
		}
		
		// Filter by published state
		$published = $this->getState('filter.published');
		if (is_numeric($published)) {
			$query->where('i.state = ' . (int) $published);
		}
		else if ($published === '') {
			$query->where('(i.state = 0 OR i.state = 1)');
		}
		
		// Filter by author
		$authorId = $this->getState('filter.author_id');
		if (is_numeric($authorId))
		{
			$type = $this->getState('filter.author_id.include', true) ? '= ' : '<>';
			$query->where('i.created_by ' . $type . (int) $authorId);
		}
		
		// Filter by search in title.
		$search = $this->getState('filter.search');
		if (!empty($search)) {
			if (stripos($search, 'id:') === 0) {
				$query->where('i.id = '.(int) substr($search, 3));
			}
			else if (stripos($search, 'author:') === 0) {
				$search = $db->Quote('%'.$db->escape(substr($search, 7), true).'%');
				$query->where('(ua.name LIKE '.$search.' OR ua.username LIKE '.$search.')');
			}
			else {
				$whereClause = array();
				foreach($fields as $field)
				{
					$whereClause[] = $field->getSearchWhereClause($query,$search);
				}
				$search = $db->Quote('%'.$db->escape($search, true).'%');
				$whereClause[] = 'i.alias LIKE '.$search;
				$query->where('('.implode(' OR ',$whereClause).')');
			}
		}
		
		// Add the list ordering clause.
		$orderCol	= $this->state->get('list.ordering');
		$orderDirn	= $this->state->get('list.direction');
		if ($orderCol != 'i.alias') {
			$query->order($db->escape($orderCol.' '.$orderDirn));
		} else {
			$orderTitle = implode(explode('/',$this->_collection->orderTitle),' '.$orderDirn.', ');
			$query->order($db->escape($orderTitle.' '.$orderDirn));
		}
		
		return $query;
	}
	
	/**
	 * Build a list of authors
	 *
	 * @return  JDatabaseQuery
	 * @since   1.6
	 */
	public function getAuthors()
	{
		// Create a new query object.
		$db = $this->getDbo();
		$query = $db->getQuery(true);

		// Construct the query
		$query->select('u.id AS value, u.name AS text')
			->from('#__users AS u')
			->join('INNER', '#__collector_fields AS c ON c.created_by = u.id')
			->group('u.id, u.name')
			->order('u.name');

		// Setup the query
		$db->setQuery($query);

		// Return the result
		return $db->loadObjectList();
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
			$app = JFactory::getApplication();
			$collection = $app->input->getVar( 'collection', '', '', 'int');
			
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
			$query->select('id, name, custom');
			$query->from('#__collector');
			
			// Add the filder on ID
			$query->where('id = '.$collection);
			
			$db->setQuery( $query );
			
			$this->_collection = $db->loadObject();
		}
		
		return $this->_collection;
	}

	/**
	 * Method to load fields of collection
	 *
	 * @access	public
	 */
	public function getFields()
	{
		if ( empty($this->_fields) )
		{
			$collection = $this->getCollection();
			
			// Create a new query object.
			$db = JFactory::getDBO();
			$query = $db->getQuery(true);
			
			// Select the required fields from the table.
			$query->select('f.id, f.field, f.tablecolumn, f.attribs');
			$query->from('#__collector_fields as f');
			
			// Join over the type.
			$query->select('t.type AS type');
			$query->join('LEFT', '#__collector_fields_type AS t ON t.id = f.type');
			
			// Filter by collection
			$query->where('f.collection = '.$collection->id);
			
			// Filter by title
			if ( $collection->custom != '0' )
			{
				$custom = explode('/',$collection->custom);
				$query->where('f.id IN("'.implode('","',$custom).'")');
			}
			else
			{
				$queryHome = 'SELECT id';
				$queryHome .= ' FROM `#__collector_fields`';
				$queryHome .= ' WHERE collection = '.$collection->id.' AND home = 1';
				
				$row = $this->_getList( $queryHome );
				
				if ( !$row )
				{
					/**
					 * tag à defaut d'une collection
					 */
					$queryHome = 'UPDATE `#__collector_fields` SET home = 1  WHERE collection = '.$collection->id.' LIMIT 1;';
					$this->_db->setQuery( $queryHome );
					$this->_db->execute();
					
				}
				$query->where('home = 1');
			}
			
			$db->setQuery( $query );
			
			$fields = $db->loadObjectList();
			
			// Reorder
			$fieldsReordered = $fields;
			if ( $collection->custom != '0' )
			{
				$custom = array_flip($custom);
				foreach( $fields as $field )
				{
					$fieldsReordered[$custom[$field->id]] = $field;
				}
			}
			
			foreach ($fieldsReordered as $field)
			{
				$registry = new JRegistry;
				$registry->loadString($field->attribs);
				$field->attribs = $registry->toArray();
				$this->_fields[] = CollectorField::getInstance( $this->_collection->id, $field );
			}
		}
		
		$title = '';
		$orderTitle = '';
		
		foreach($this->_fields as $field)
		{
			if ( $title != '' )
			{
				$title .= ' ';
			}
			$title .= $field->getFieldName();
			
			if ( $orderTitle != '' )
			{
				$orderTitle .= '/';
			}
			$orderTitle .= $field->getOrderBy();
		}
		$this->_collection->title = $title;
		$this->_collection->orderTitle = $orderTitle;
		$this->filter_fields['title'] = $orderTitle;
		
		return $this->_fields;
	}
}