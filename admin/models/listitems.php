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

/**
 * Definedcontent model
 * @package	Collector
 */
class CollectorModelListitems extends JModelList
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
				'defined', 'c.defined',
				'level', 'c.level',
				'path', 'c.path',
				'content', 'c.content',
				'lft', 'c.lft',
				'rgt', 'c.rgt',
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

		$path = $this->getUserStateFromRequest($this->context . '.filter.path', 'filter_path');
		$this->setState('filter.path', $path);
		
		$search = $this->getUserStateFromRequest($this->context.'.filter.search', 'filter_search');
		$this->setState('filter.search', $search);

		$level = $this->getUserStateFromRequest($this->context . '.filter.level', 'filter_level', 0, 'int');
		$this->setState('filter.level', $level);
		
		$defined = $app->input->getString('defined', null);
		
		if ($defined)
		{
			if ($defined != $app->getUserState($this->context.'.defined'))
			{
				$app->setUserState($this->context.'.defined', $defined);
				$app->input->set('limitstart', 0);
			}
		}
		else
		{
			$app->redirect('index.php?option=com_collector&view=lists');
		}
		
		$this->setState('filter.defined', $defined);

		// List state information.
		parent::populateState('c.lft', 'asc');
	}
	
	/**
	 * Build an SQL query to load the list data.
	 *
	 * @return	JDatabaseQuery
	 */
	protected function getListQuery()
	{
		$list = $this->getState('filter.defined');
		
		// Create a new query object.
		$db = $this->getDbo();
		$query = $db->getQuery(true);
		
		// Select the required fields from the table.
		$query->select(
			$this->getState(
				'list.select',
				'c.id, c.defined, c.path, c.parent_id, c.level, c.content, c.image, c.lft, c.rgt'
			)
		);
		$query->from('#__collector_defined_content AS c');
		
		$query->where('c.defined = '.$list);
		
		// Filter by search in title.
		$search = $this->getState('filter.search');
		if (!empty($search)) {
			if (stripos($search, 'id:') === 0) {
				$query->where('c.id = '.(int) substr($search, 3));
			}
			else {
				$search = $db->Quote('%'.$db->escape($search, true).'%');
				$query->where('c.content LIKE '.$search);
			}
		}
		
		// Filter on the level maximum.
		if ($level = $this->getState('filter.level'))
		{
			$query->where('c.level <= ' . (int) $level);
		}
		
		// Filter on the path.
		if ($path = $this->getState('filter.path'))
		{
			if ( $this->checkLevelPath($path,$level) )
			{
				$query->where('c.path != ' . $db->Quote($db->escape($path, true)));
				$path = $db->Quote('%'.$db->escape($path, true).'%');
				$query->where('c.path LIKE ' . $path);
			}	
		}
		
		// Add the list ordering clause.
		$query->order($db->escape($this->getState('list.ordering', 'c.lft')) . ' ' . $db->escape($this->getState('list.direction', 'ASC')));
		
		return $query;
	}
	
	public function checkLevelPath($path,$level)
	{
		if ( $level == '' )
		{
			return true;
		}
		
		// Create a new query object.
		$db = JFactory::getDBO();
		$query = $db->getQuery(true);
		
		// Select the required fields from the table.
		$query->select('level');
		$query->from('#__collector_defined_content');
		$query->where('path = '.$db->Quote($db->escape($path, true)));
		
		$db->setQuery( $query );
		
		$levelPath = $db->loadResult();
		
		if ( $level > $levelPath )
		{
			return true;
		}
		return false;
	}
	
	/**
	 * Method to delete defined items
	 *
	 * @access	public
	 * @return	string	Message to display
	 */
	function delete($cid)
	{
		$row = $this->getTable('collector_defined_content');
		
		foreach ($cid as $id)
		{
			if (!($row->delete($id)))
			{
				JError::raiseError( 500, $row->getError() );
				return false;
			}
		}
		
		return true;
	}
}
