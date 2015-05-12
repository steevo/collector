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

jimport('joomla.application.component.modellist');

/**
 * Defined model
 * @package	Collector
 */
class CollectorModelLists extends JModelList
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
				'name', 'c.name',
				'alias', 'c.alias',
				'checked_out', 'c.checked_out',
				'checked_out_time', 'c.checked_out_time',
				'created', 'c.created',
				'created_by', 'c.created_by',
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
		if ($layout = $app->input->getVar('layout')) {
			$this->context .= '.'.$layout;
		}

		$search = $this->getUserStateFromRequest($this->context.'.filter.search', 'filter_search');
		$this->setState('filter.search', $search);

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
				'c.id, c.name, c.alias, c.created, c.created_by, c.created_by_alias, c.checked_out, c.checked_out_time' .
				', c.modified, c.modified_by, (SELECT COUNT(id) FROM #__collector_defined_content AS d WHERE d.defined = c.id) AS itemsnb'
			)
		);
		$query->from('#__collector_defined AS c');
		
		// Join over the users for the checked out user.
		$query->select('uc.name AS editor');
		$query->join('LEFT', '#__users AS uc ON uc.id = c.checked_out');
		
		// Join over the users for the author.
		$query->select('ua.name AS author_name');
		$query->join('LEFT', '#__users AS ua ON ua.id = c.created_by');
		
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
		$orderCol	= $this->state->get('list.ordering');
		$orderDirn	= $this->state->get('list.direction');
		$query->order($db->escape($orderCol.' '.$orderDirn));
		
		return $query;
	}

	/**
	 * Method to delete defined lists
	 *
	 * @access	public
	 * @return	string	Message to display
	 */
	function delete()
	{
		$app = JFactory::getApplication();
		$cid = $app->input->getVar( 'cid', array(0), 'post', 'array');
		$row = $this->getTable('collector_defined');
		
		foreach ($cid as $id)
		{
			if (!($row->delete($id)))
			{
				JError::raiseError( 500, $row->getError() );
				return false;
			}
			else
			{
				// mise à jour des champs qui avaient ce type prédéfini
				$query = 'UPDATE #__collector_fields SET `defined` = \'-1\' WHERE `defined` = ' . $id ;
				
				$this->_db->setQuery( $query );
				$this->_db->execute();
				
				// suppression de tous les éléments de ce champ prédéfini
				$query = 'DELETE FROM `#__collector_defined_content` WHERE `defined` = ' . $id ;
				
				$this->_db->setQuery( $query );
				$this->_db->execute();
			}
		}
		
		$msg = count($cid) . ' ' . JText::_( 'COM_COLLECTOR_FIELD_DELETED' );
		
		return $msg;
	}
}