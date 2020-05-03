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

jimport( 'joomla.application.component.model' );

jimport('joomla.application.component.modellist');

/**
 * Filesconfig model
 * @package	Collector
 */
class CollectorModelFilesconfig extends JModelList
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
				'ext', 'e.ext',
				'type', 'e.type',
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
		// List state information.
		parent::populateState('e.type', 'asc');
	}
	
	/**
	 * Method to build an SQL query to load the list data.
	 *
	 * @return	string	An SQL query
	 */
	protected function getListQuery()
	{
		// Create a new query object.		
		$db = $this->getDbo();
		$query = $db->getQuery(true);
		
		// Select the required fields from the table.
		$query->select(
			$this->getState('list.select',
				'e.id, e.ext, e.ico, e.state'
			)
		);
		$query->from('#__collector_files_ext AS e');
		
		// Join over the files type.
		$query->select('t.text AS type');
		$query->join('LEFT', '#__collector_files_type AS t ON e.type = t.id');
		
		// Add the list ordering clause.
		$orderCol	= $this->state->get('list.ordering');
		$orderDirn	= $this->state->get('list.direction');
		
		$query->order($db->escape($orderCol.' '.$orderDirn));
		
		return $query;
	}
	
	/**
	 * Method to build an SQL query to load the list data.
	 *
	 * @return	string	An SQL query
	 */
	public function getRow()
	{
		$app = JFactory::getApplication();
		$id = $app->input->get( 'id', 'int', 'get', '0');
		
		$row = JTable::getInstance('collector_files_ext','Table');
		
		if ( $id != 0 )
		{
			$row->load($id);
		}
		
		return $row;
	}
	
	/**
	 * Build a list of extensions
	 *
	 * @return	JDatabaseQuery
	 */
	public function getTypes()
	{
		$init[] = array('value' => 0, 'text' => '');
		
		// Get list of files types
		$db = JFactory::getDBO(); 
		$query = 'SELECT id AS value, text ';
		$query .= ' FROM #__collector_files_type';
		$db->setQuery( $query );
		
		// Load the content if it doesn't already exist
		$results = $db->loadObjectList();
		
		$select = $init;
		foreach ( $results as $key => $value )
		{
			$value->text=JText::_('COM_COLLECTOR_'.$value->text);
			$select[$key+1]=$value;
		}
		return $select;
	}
	
	/**
	 * Method to delete extensions
	 *
	 * @access	public
	 * @return	boolean	True on success
	 */
	function remove()
	{
		$app = JFactory::getApplication();
		$id = $app->input->get( 'id', 'int', 'get', '0');
		$row = $this->getTable('collector_files_ext');
		
		$row->load($id);
		
		if (!($row->delete($id)))
		{
			JFactory::getApplication()->enqueueMessage($row->getError(),'error');
			return false;
		}
		
		return true;
	}
	
	/**
	 * Method to save extensions
	 *
	 * @access	public
	 * @return	boolean	True on success
	 */
	function save()
	{
		$app = JFactory::getApplication();
		$row = $this->getTable('collector_files_ext');
		echo '<pre>';
		print_r($row);
		if (!$row->bind($app->input->post)) {
			return false;
		}
		print_r($row);
		die();
		// make sure the collector record is valid
		if ( !$row->check() )
		{
			return false;
		}
		
		// store the record in the database
		if ( !$row->store() )
		{
			return false;
		}
		
		return true;
	}
	
	/**
	 * Method to set state parameter
	 *
	 * @access	public
	 * @param	int		$state	Parameter state
	 * @return	boolean			True on success
	 */
	public function state($state = 0)
	{
		$app = JFactory::getApplication();
		// on initialise les variables
		$id = $app->input->get( 'id', 'int', 'get', '0');
		$row = $this->getTable('collector_files_ext');
		
		$row->load($id);
		$row->state = $state;
		
		// on s'assure que l'objet est valide
		if (!$row->check())
		{
			JFactory::getApplication()->enqueueMessage($row->getError(),'error');
			return false;
		}
		
		// on enregistre le tout
		if (!$row->store())
		{
			JFactory::getApplication()->enqueueMessage($row->getError(),'error');
			return false;
		}
		
		return true;
	}
}