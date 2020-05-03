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
 * Fields model
 * @package	Collector
 */
class CollectorModelFields extends JModelList
{
	/**
	 * Collection Id
	 * @var int
	 */
	var $_collection = null;

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
				'id', 'f.id',
				'collection', 'f.collection',
				'field', 'f.field',
				'type', 'f.type',
				'ordering', 'f.ordering',
				'state', 'f.state',
				'created', 'f.created',
				'created_by', 'f.created_by', 'author_id',
				'publish_up', 'f.publish_up',
				'publish_down', 'f.publish_down',
				'published', 'f.published',
				'checked_out', 'f.checked_out',
				'checked_out_time', 'f.checked_out_time',
				'required', 'f.required',
				'access', 'f.access', 'access_level',
				'home', 'f.home',
				'unik', 'f.unik',
				'edit', 'f.edit',
				'listing', 'f.listing',
				'filter', 'f.filter',
				'sort', 'f.sort',
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
		$session = JFactory::getSession();

		// Adjust the context to support modal layouts.
		if ($layout = $app->input->get('layout')) {
			$this->context .= '.'.$layout;
		}		
		
		$collection = $this->getCollection();
		
		if ($collection != $app->getUserState($this->context.'.collection'))
		{
			$app->setUserState($this->context.'.collection', $collection);
			$app->input->set('limitstart', 0);
		}
		
		$this->setState('filter.collection', $collection);

		$search = $this->getUserStateFromRequest($this->context.'.filter.search', 'filter_search');
		$this->setState('filter.search', $search);

		$type = $this->getUserStateFromRequest($this->context.'.filter.type', 'filter_type', 0, 'int');
		$this->setState('filter.type', $type);
		
		$access = $this->getUserStateFromRequest($this->context.'.filter.access', 'filter_access', 0, 'int');
		$this->setState('filter.access', $access);
		
		$authorId = $app->getUserStateFromRequest($this->context.'.filter.author_id', 'filter_author_id');
		$this->setState('filter.author_id', $authorId);

		$published = $this->getUserStateFromRequest($this->context.'.filter.published', 'filter_published', '');
		$this->setState('filter.published', $published);

		// List state information.
		parent::populateState('f.ordering', 'asc');
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
		// $collection = $this->getCollection();
		
		// Create a new query object.
		$db = $this->getDbo();
		$query = $db->getQuery(true);
		$user = JFactory::getUser();
		
		// Select the required fields from the table.
		$query->select(
			$this->getState(
				'list.select',
				'f.id, f.collection, f.field, f.tablecolumn, f.ordering, f.state, f.created, f.created_by, f.created_by_alias, f.modified, f.modified_by' .
				', f.access, f.required, f.home, f.edit, f.unik, f.listing, f.filter, f.sort, f.publish_up, f.publish_down, f.checked_out, f.checked_out_time'
			)
		);
		$query->from('#__collector_fields AS f');
		
		// Join over the users for the checked out user.
		$query->select('uc.name AS editor');
		$query->join('LEFT', '#__users AS uc ON uc.id = f.checked_out');
		
		// Join over the asset groups.
		$query->select('ag.title AS access_level');
		$query->join('LEFT', '#__viewlevels AS ag ON ag.id = f.access');
		
		// Join over the users for the author.
		$query->select('ua.name AS author_name');
		$query->join('LEFT', '#__users AS ua ON ua.id = f.created_by');
		
		// Join over the field type.
		$query->select('t.type, t.unikable, t.sortable, t.searchable, t.filterable, t.intitle');
		$query->join('LEFT', '#__collector_fields_type AS t ON t.id = f.type');
		
		// Filter by collection
		$query->where('f.collection = ' . $collection);
		
		// Filter by type.
		if ($type = $this->getState('filter.type'))
		{
			$query->where('f.type = ' . (int) $type);
		}
		
		// Filter by access level.
		if ($access = $this->getState('filter.access'))
		{
			$query->where('f.access = ' . (int) $access);
		}
		
		// Implement View Level Access
		if (!$user->authorise('core.admin'))
		{
			$groups = implode(',', $user->getAuthorisedViewLevels());
			$query->where('f.access IN (' . $groups . ')');
		}
		
		// Filter by published state
		$published = $this->getState('filter.published');
		if (is_numeric($published))
		{
			$query->where('f.state = ' . (int) $published);
		}
		else if ($published === '') {
			$query->where('(f.state = 0 OR f.state = 1)');
		}
		
		// Filter by author
		$authorId = $this->getState('filter.author_id');
		if (is_numeric($authorId))
		{
			$type = $this->getState('filter.author_id.include', true) ? '= ' : '<>';
			$query->where('f.created_by ' . $type . (int) $authorId);
		}
		
		// Filter by search in title.
		$search = $this->getState('filter.search');
		if (!empty($search)) {
			if (stripos($search, 'id:') === 0)
			{
				$query->where('f.id = '.(int) substr($search, 3));
			}
			else if (stripos($search, 'author:') === 0)
			{
				$search = $db->quote('%'.$db->escape(substr($search, 7), true).'%');
				$query->where('(ua.name LIKE '.$search.' OR ua.username LIKE '.$search.')');
			}
			else
			{
				$search = $db->quote('%'.$db->escape($search, true).'%');
				$query->where('(f.field LIKE '.$search.')');
			}
		}
		
		// Add the list ordering clause.
		$orderCol	= $this->state->get('list.ordering', 'f.ordering');
		$orderDirn	= $this->state->get('list.direction', 'asc');
		$query->order($db->escape($orderCol.' '.$orderDirn));
		
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
	 * Finds the default menu type.
	 *
	 * In the absence of better information, this is the first menu ordered by title.
	 *
	 * @return  string    The default menu type
	 *
	 * @since   1.6
	 */
	protected function getDefaultCollection()
	{
		// Create a new query object.
		$db = $this->getDbo();
		$query = $db->getQuery(true)
			->select('id')
			->from('#__collector')
			->where('home = 1');
		$db->setQuery($query);
		$collection = $db->loadResult();

		return $collection;
	}
	
	/**
	 * Method to load default collection Id in <var>_collection</var>
	 *
	 * @access	private
	 */
	public function getCollection()
	{
		$app = JFactory::getApplication();
		if ($this->_collection == '')
		{
			$this->_collection = $app->input->getString('collection', null);
			
			if ($this->_collection == '')
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
					$this->_collection = $row[0]->id;
				}
			}
		}
		
		return $this->_collection;
	}
	
	/**
	 * Method to delete fields
	 *
	 * @access	public
	 * @return	string	Message to display
	 */
	function remove()
	{
		$app = JFactory::getApplication();
		$cid = $app->input->get( 'cid', array(0), 'post', 'array');
		$row = $this->getTable('collector_fields');
		
		foreach ($cid as $id)
		{
			if (!($row->delete($id)))
			{
				JFactory::getApplication()->enqueueMessage($row->getError(),'error');
				return false;
			}
		}
		
		$row->reorder('collection='.$this->_collection);
		
		$msg = count($cid) . ' ' . JText::_( 'COM_COLLECTOR_FIELD_DELETED' );
		
		return $msg;
	}
}
