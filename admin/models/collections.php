<?php
/**
 * Joomla! 3.0 component Collector
 *
 * @package 	Collector
 * @copyright   Copyright (C) 2010 - 2015 Open Source Matters, Inc. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 *
 * Collector is a Multi Purpose Listing Tool.
 * Originaly developped to list Collections
 * it can be used for several purpose.
 */

// Check to ensure this file is included in Joomla!
defined('_JEXEC') or die( 'Restricted access' );

jimport('joomla.application.component.modellist');

/**
 * Collections model
 * @package	Collector
 */
class CollectorModelCollections extends JModelList
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
				'name', 'c.name', 'c.title',
				'alias', 'c.alias',
				'checked_out', 'c.checked_out',
				'checked_out_time', 'c.checked_out_time',
				'state', 'c.state',
				'access', 'c.access', 'access_level',
				'created', 'c.created',
				'created_by', 'c.created_by',
				'ordering', 'c.ordering',
				'publish_up', 'c.publish_up',
				'publish_down', 'c.publish_down',
				'published', 'c.published',
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
		if ($layout = $app->input->getVar('layout')) {
			$this->context .= '.'.$layout;
		}

		$search = $this->getUserStateFromRequest($this->context.'.filter.search', 'filter_search');
		$this->setState('filter.search', $search);

		$access = $this->getUserStateFromRequest($this->context.'.filter.access', 'filter_access', 0, 'int');
		$this->setState('filter.access', $access);

		$published = $this->getUserStateFromRequest($this->context.'.filter.published', 'filter_published', '');
		$this->setState('filter.published', $published);

		// List state information.
		parent::populateState('c.name', 'asc');
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
				'c.id, c.name, c.alias, c.ordering, c.state, c.created, c.created_by, c.created_by_alias, c.checked_out, c.checked_out_time' .
				', c.modified, c.modified_by, c.access, c.publish_up, c.publish_down, c.home'
			)
		);
		$query->from('#__collector AS c');
		
		// Join over the users for the checked out user.
		$query->select('uc.name AS editor');
		$query->join('LEFT', '#__users AS uc ON uc.id = c.checked_out');
		
		// Join over the asset groups.
		$query->select('ag.title AS access_level');
		$query->join('LEFT', '#__viewlevels AS ag ON ag.id = c.access');
		
		// Join over the users for the author.
		$query->select('ua.name AS author_name');
		$query->join('LEFT', '#__users AS ua ON ua.id = c.created_by');
		
		// Filter by access level.
		if ($access = $this->getState('filter.access')) {
			$query->where('c.access = ' . (int) $access);
		}
		
		// Filter by published state
		$published = $this->getState('filter.published');
		if (is_numeric($published)) {
			$query->where('c.state = ' . (int) $published);
		}
		else if ($published === '') {
			$query->where('(c.state = 0 OR c.state = 1)');
		}
		
		// Filter by search in title.
		$search = $this->getState('filter.search');
		if (!empty($search)) {
			if (stripos($search, 'id:') === 0) {
				$query->where('c.id = '.(int) substr($search, 3));
			}
			else if (stripos($search, 'author:') === 0) {
				$search = $db->Quote('%'.$db->escape(substr($search, 7), true).'%');
				$query->where('(ua.name LIKE '.$search.' OR ua.username LIKE '.$search.')');
			}
			else {
				$search = $db->Quote('%'.$db->escape($search, true).'%');
				$query->where('(c.name LIKE '.$search.' OR c.alias LIKE '.$search.')');
			}
		}
		
		// Add the list ordering clause.
		$orderCol	= $this->state->get('list.ordering','c.name');
		$orderDirn	= $this->state->get('list.direction','asc');
		$query->order($db->escape($orderCol.' '.$orderDirn));
		
		return $query;
	}
	
	/**
	 * Method to get a list of collections.
	 * Overridden to add a check for access levels.
	 *
	 * @return	mixed	An array of data items on success, false on failure.
	 */
	/*public function getItems()
	{
		$items	= parent::getItems();
		$app	= JFactory::getApplication();
		if ($app->isSite()) {
			$user	= JFactory::getUser();
			$groups	= $user->getAuthorisedViewLevels();

			for ($x = 0, $count = count($items); $x < $count; $x++) {
				//Check the access level. Remove collections the user shouldn't see
				if (!in_array($items[$x]->access, $groups)) {
					unset($items[$x]);
				}
			}
		}
		return $items;
	}*/
}