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
 * Removeuseritem model
 * @package	Collector
 */
class CollectorModelRemoveuseritem extends JModelList
{
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
				'id', 'c.id',
				'name', 'c.title',
				'description', 'c.description',
				'alias', 'c.alias',
				'checked_out', 'c.checked_out',
				'checked_out_time', 'c.checked_out_time',
				'state', 'c.state',
				'access', 'c.access', 'access_level',
				'created', 'c.created',
				'created_by', 'c.created_by',
				'publish_up', 'c.publish_up',
				'publish_down', 'c.publish_down',
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
		
		$collection = $app->input->getInt('collection');
		$this->setState('collection.id', $collection);
		
		$item = $app->input->getInt('item', null);
		$this->setState('item.id', $item);
		
		$userlist = $app->input->getInt('userlist', null);
		$this->setState('userlist.id', $userlist);

		$this->setState('layout', $app->input->getString('layout'));

		// List state information.
		parent::populateState('c.name', 'asc');
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
		$userlist = (int) $this->getState('userlist.id');
		
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
		
		$table->userlist = $userlist;
		
		return $table;
	}
	
	/**
	 * Build an SQL query to load the list data.
	 *
	 * @return	JDatabaseQuery
	 */
	protected function getListQuery()
	{
		// Create a new query object.
		$db = $this->getDbo();
		$query = $db->getQuery(true);
		
		// Select the required fields from the table.
		$query->select(
			$this->getState(
				'list.select',
				'ui.id, ui.comment'
			)
		);
		$query->from('#__collector_usersitems AS ui');
		
		// Join over the userlist.
		$query->join('LEFT', '#__collector_userlist AS ul ON ul.id = ui.userlist');
		
		// Join over the userslists for the list name.
		$query->select('uls.name AS list');
		$query->join('LEFT', '#__collector_userslists AS uls ON uls.id = ul.userslist');
		
		// Join over the items for the item name.
		$query->select('i.fulltitle AS name');
		$query->join('LEFT', '#__collector_items AS i ON i.id = ui.itemid');
		
		// Filter by user.
		$user	= JFactory::getUser();
		$query->where('ul.user = '.$user->id);
		
		// Filter by userlist
		$userlist = $this->getState('userlist.id');
		$query->where('ul.userslist = ' . (int) $userlist);
		
		// Filter by item
		$item = $this->getState('item.id');
		$query->where('ui.itemid = ' . (int) $item);
		
		return $query;
	}
}