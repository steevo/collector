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
 * Collector_items_history_ table
 * @package		Collector
 */
class TableCollector_items_history_ extends JTable
{
	/**
	 * @param database A database connector object
	 */
	function TableCollector_items_history_( &$db , $collection )
	{
		$this->tableExist($collection);
		parent::__construct( '#__collector_items_history_'.$collection , 'id', $db );
	}
	
	public static function getInstance($type, $prefix = 'JTable', $config = array())
	{
		// If a database object was passed in the configuration array use it, otherwise get the global one from JFactory.
		$db = isset($config['dbo']) ? $config['dbo'] : JFactory::getDbo();
		
		$collection = $config['collection'];
		
		// Instantiate a new table class and return it.
		return new TableCollector_items_history_($db,$collection);
    }
	
	/**
	 * function to create a new table for a new collection
	 *
	 * @access	public
	 * @param	int		$collection	Collection Id
	 */
	private function tableExist($collection)
	{
		$query = 'CREATE TABLE IF NOT EXISTS `#__collector_items_history_'.$collection.'` (';
		$query .= '`id` int(11) NOT NULL auto_increment,';
		$query .= '`item` int(11) NOT NULL,';
		$query .= '`state` tinyint(3) NOT NULL default "0",';
		$query .= '`modified` datetime NOT NULL default "0000-00-00 00:00:00",';
		$query .= '`modified_by` int(11) unsigned NOT NULL default "0",';
		$query .= '`metakey` text NOT NULL,';
		$query .= '`metadesc` text NOT NULL,';
		$query .= '`metadata` text NOT NULL,';
		$query .= '`modification` text NOT NULL default "",';
		$query .= 'PRIMARY KEY (`id`),';
		$query .= 'KEY `idx_item` (`item`),';
		$query .= 'KEY `idx_modifiedby` (`modified_by`)';
		$query .= ') ENGINE=MyISAM;';
		
		$db = JFactory::getDbo();
		$db->setQuery( $query );
		$db->execute();
		
		return true;
	}
	
	/**
	 * function to load a version of an item
	 *
	 * @access	public
	 * @param	int		$collection	Collection Id
	 */
	function initVersion($collection)
	{
		// mise a jour du nom de la table
		$this->_collection = $collection;
		$this->_tbl = '#__collector_items_history_'.$collection;
		
		// recuperation des champs de la collection
		$query = 'SELECT tablecolumn FROM `#__collector_fields`';
		$query .= ' WHERE collection = ' . $collection;
		$query .= ' ORDER BY ordering';
		
		$this->_db->setQuery($query);
		
		$rows = $this->_db->loadResultArray();
		
		for ($i=0, $n=count($rows);$i<$n;$i++)
		{
			$field = $rows[$i];
			
			$this->$field = null;
		}
	}
	
	/**
	 * function to load a version of an item
	 *
	 * @access	public
	 * @param	int		$collection	Collection Id
	 * @param	int		$id			Item Id
	 * @param	int		$version	Version Id
	 */
	function loadVersion( $collection, $id, $copy = false, $version = null )
	{
		// Si pas de version, alors version courante
		if ( $version == null )
		{
			$query = 'SELECT id';
			$query .= ' FROM `'.$this->_tbl.'`';
			$query .= ' WHERE item = \'' . $id . '\'';
			$query .= ' AND state = \'1\'';
			
			$this->_db->setQuery($query);
			
			$version = $this->_db->loadResult();
		}
		
		// recuperation des donnees de la version
		$this->load($version);
		
		if ($copy == true)
		{
			$this->id = null;
		}
	}
	
	/**
	 * Overloaded store function
	 *
	 * @access	public
	 * @return	null|string	null if successful otherwise returns and error message
	 * @see		JTable::store
	 */
	function store($updateNulls = false)
	{
		$date	= JFactory::getDate();
		$user	= JFactory::getUser();
		
		$query = 'UPDATE `'.$this->_tbl.'`';
		$query .= ' SET state = "2"';
		$query .= ' WHERE item = ' . $this->item;
		
		$this->_db->setQuery($query);
		$this->_db->execute();
		
		$this->state = '1';
		
		$this->modified		= $date->toSql();
		$this->modified_by	= $user->get('id');
		
		return parent::store($updateNulls);
	}
	
	/**
	 * Overloaded delete function
	 *
	 * @access	public
	 * @param	int		$pk		Version Id
	 * @return	boolean		True if the version is deleted
	 * @see		JTable::delete
	 */
	function delete($pk = null)
	{
		return parent::delete($pk);
	}
	
	/**
	 * Overloaded check function
	 *
	 * @access	public
	 * @return	boolean	True if the object is ok
	 * @see		JTable::check
	 */
	function check()
	{
		// check for valid name
		// if( trim($this->name) == '' ) {
			// $this->setError(JText::_( 'COM_COLLECTOR_COL_NAME' ));
			// return false;
		// }
		// check for existing username
		
		// cast item Id
		$this->item = (int)($this->item);
		
		return true;
	}
}