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
 * Userslists model
 * @package	Collector
 */
class CollectorModelUserslists extends JModelList
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
				'id', 'cu.id',
				'collection', 'cu.collection',
				'type', 'cu.collection',
				'name', 'cu.name',
				'ordering', 'cu.ordering',
				'state', 'cu.state',
				'created', 'cu.created',
				'created_by', 'cu.created_by', 'author_id',
				'publish_up', 'cu.publish_up',
				'publish_down', 'cu.publish_down',
				'published', 'cu.published',
				'checked_out', 'cu.checked_out',
				'checked_out_time', 'cu.checked_out_time',
				'access', 'cu.access', 'access_level',
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
		
		$access = $this->getUserStateFromRequest($this->context.'.filter.access', 'filter_access', 0, 'int');
		$this->setState('filter.access', $access);
		
		$authorId = $app->getUserStateFromRequest($this->context.'.filter.author_id', 'filter_author_id');
		$this->setState('filter.author_id', $authorId);

		$published = $this->getUserStateFromRequest($this->context.'.filter.published', 'filter_published', '');
		$this->setState('filter.published', $published);

		$type = $this->getUserStateFromRequest($this->context.'.filter.type', 'filter_type', '');
		$this->setState('filter.type', $type);

		// List state information.
		parent::populateState('cu.ordering', 'asc');
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
		
		// Create a new query object.
		$db = $this->getDbo();
		$query = $db->getQuery(true);
		$user = JFactory::getUser();
		
		// Select the required fields from the table.
		$query->select(
			$this->getState(
				'list.select',
				'cu.id, cu.collection, cu.name, cu.ordering, cu.state, cu.created, cu.created_by, cu.created_by_alias, cu.modified, cu.modified_by' .
				', cu.access, cu.publish_up, cu.publish_down, cu.checked_out, cu.checked_out_time'
			)
		);
		$query->from('#__collector_userslists AS cu');
		
		// Join over the users for the checked out user.
		$query->select('uc.name AS editor');
		$query->join('LEFT', '#__users AS uc ON uc.id = cu.checked_out');
		
		// Join over the asset groups.
		$query->select('ag.title AS access_level');
		$query->join('LEFT', '#__viewlevels AS ag ON ag.id = cu.access');
		
		// Join over the users for the author.
		$query->select('ua.name AS author_name');
		$query->join('LEFT', '#__users AS ua ON ua.id = cu.created_by');
		
		// Filter by collection
		$query->where('cu.collection = ' . $collection);
		
		// Filter by access level.
		if ($access = $this->getState('filter.access'))
		{
			$query->where('cu.access = ' . (int) $access);
		}
		
		// Implement View Level Access
		if (!$user->authorise('core.admin'))
		{
			$groups = implode(',', $user->getAuthorisedViewLevels());
			$query->where('cu.access IN (' . $groups . ')');
		}
		
		// Filter by published state
		$published = $this->getState('filter.published');
		if (is_numeric($published))
		{
			$query->where('cu.state = ' . (int) $published);
		}
		else if ($published === '') {
			$query->where('(cu.state = 0 OR cu.state = 1)');
		}
		
		// Filter by author
		$authorId = $this->getState('filter.author_id');
		if (is_numeric($authorId))
		{
			$type = $this->getState('filter.author_id.include', true) ? '= ' : '<>';
			$query->where('cu.created_by ' . $type . (int) $authorId);
		}
		
		// Filter by search in title.
		$search = $this->getState('filter.search');
		if (!empty($search)) {
			if (stripos($search, 'id:') === 0)
			{
				$query->where('cu.id = '.(int) substr($search, 3));
			}
			else if (stripos($search, 'author:') === 0)
			{
				$search = $db->quote('%'.$db->escape(substr($search, 7), true).'%');
				$query->where('(ua.name LIKE '.$search.' OR ua.username LIKE '.$search.')');
			}
			else
			{
				$search = $db->quote('%'.$db->escape($search, true).'%');
				$query->where('(cu.name LIKE '.$search.')');
			}
		}
		
		// Filter by type.
		$type = $this->getState('filter.type');
		if ($type != '')
		{
			$query->where('cu.type = ' . (int) $type);
		}
		
		// Add the list ordering clause.
		$orderCol	= $this->state->get('list.ordering', 'cu.ordering');
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
			->join('INNER', '#__collector_userslists AS c ON c.created_by = u.id')
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
	 * Method to delete userslists
	 *
	 * @access	public
	 * @return	string	Message to display
	 */
	function remove()
	{
		$app = JFactory::getApplication();
		$cid = $app->input->get( 'cid', array(0), 'post', 'array');
		$row = $this->getTable('collector_userslists');
		
		foreach ($cid as $id)
		{
			if (!($row->delete($id)))
			{
				JFactory::getApplication()->enqueueMessage($row->getError(),'error');
				return false;
			}
		}
		
		$row->reorder('collection='.$this->_collection);
		
		$msg = count($cid) . ' ' . JText::_( 'COM_COLLECTOR_USERSLISTS_DELETED' );
		
		return $msg;
	}
}
