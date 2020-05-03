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
 * Templates model
 * @package	Collector
 */
class CollectorModelTemplates extends JModelList
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
				'id', 't.id',
				'collection', 't.collection',
				'name', 't.name',
				'client', 't.client',
				'home', 'f.home',
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

		// List state information.
		parent::populateState('t.name', 'asc');
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
				't.id, t.collection, t.name, t.client, t.home, t.column'
			)
		);
		$query->from('#__collector_templates AS t');
		
		// Filter by collection
		$query->where('t.collection = ' . $collection);
		
		// Filter by search in title.
		$search = $this->getState('filter.search');
		if (!empty($search)) {
			if (stripos($search, 'id:') === 0)
			{
				$query->where('t.id = '.(int) substr($search, 3));
			}
			else
			{
				$search = $db->quote('%'.$db->escape($search, true).'%');
				$query->where('(f.name LIKE '.$search.')');
			}
		}
		
		// Add the list ordering clause.
		$orderCol	= $this->state->get('list.ordering', 'f.name');
		$orderDirn	= $this->state->get('list.direction', 'asc');
		$query->order($db->escape($orderCol.' '.$orderDirn));
		
		return $query;
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
}