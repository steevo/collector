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

jimport( 'joomla.application.component.modellist' );

/**
 * Itemversions model
 * @package	Collector
 */
class CollectorModelItemversions extends JModelList
{
	/**
	 * Collection Id
	 * @var int
	 */
	var $_collection;

	/**
	 * Item Id
	 * @var int
	 */
	var $_item;

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
				'id', 'h.id',
				'item', 'h.alias',
				'state', 'h.state',
				'modified', 'h.modified',
				'modified_by', 'h.modified_by', 'author_id',
				'modification', 'h.modification',
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

		$collection = $this->getCollection();
		
		if ($collection != $app->getUserState('com_collector.itemversions.filter.collection'))
		{
			$app->setUserState($this->context.'.collection', $collection);
			$app->input->set('limitstart', 0);
		}

		$this->setState('filter.collection', $collection);
		
		$item = $this->getItem();
		
		if ($item != $app->getUserState('com_collector.itemversions.filter.item'))
		{
			$app->setUserState($this->context.'.item', $item);
			$app->input->set('limitstart', 0);
		}
		
		$this->setState('filter.item', $item);
		
		$search = $this->getUserStateFromRequest($this->context.'.filter.search', 'filter_search');
		$this->setState('filter.search', $search);

		// List state information.
		parent::populateState('h.modified', 'desc');
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
		$item = $this->getState('filter.item');
		
		// Create a new query object.
		$db = $this->getDbo();
		$query = $db->getQuery(true);
		
		// Select the required fields from the table.
		$query->select(
			$this->getState(
				'list.select',
				'h.id, h.item, h.state, h.modified, h.modified_by' .
				', h.modification'
			)
		);
		$query->from('#__collector_items_history_'.$collection.' AS h');
		
		// Join over the users for the author.
		$query->select('ua.name AS author_name');
		$query->join('LEFT', '#__users AS ua ON ua.id = h.modified_by');
		
		// Filter by item
		$query->where('h.item = '.$item);
		
		// Filter by search in title.
		$search = $this->getState('filter.search');
		if (!empty($search)) {
			if (stripos($search, 'id:') === 0) {
				$query->where('h.id = '.(int) substr($search, 3));
			}
			else if (stripos($search, 'author:') === 0) {
				$search = $db->Quote('%'.$db->escape(substr($search, 7), true).'%');
				$query->where('(ua.name LIKE '.$search.' OR ua.username LIKE '.$search.')');
			}
			else {
				$search = $db->Quote('%'.$db->escape($search, true).'%');
				$query->where('(h.modification LIKE '.$search.')');
			}
		}
		
		// Add the list ordering clause.
		$orderCol	= $this->state->get('list.ordering');
		$orderDirn	= $this->state->get('list.direction');
		$query->order($db->escape($orderCol.' '.$orderDirn));
		
		return $query;
	}
	
	/**
	 * Method to load default collection Id in <var>_collection</var>
	 *
	 * @access	private
	 */
	public function getCollection()
	{
		$app = JFactory::getApplication();
		if ( $this->_collection == '' )
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
	 * Method to load default collection Id in <var>_collection</var>
	 *
	 * @access	private
	 */
	public function getItem()
	{
		$app = JFactory::getApplication();
		if ( $this->_item == '' )
		{
			$this->_item = $app->input->getString('item', null);
		}
		
		return $this->_item;
	}
	
	/**
	 * Method to delete defined items
	 *
	 * @access	public
	 * @return	string	Message to display
	 */
	function delete($item,$cid)
	{
		$row = $this->getTable('collector_items');
		
		$row->load($item);

		foreach ($cid as $id)
		{
			if (!($row->deleteVersion($id)))
			{
				JFactory::getApplication()->enqueueMessage($row->getError(),'error');
				return false;
			}
		}
		
		return true;
	}
}
