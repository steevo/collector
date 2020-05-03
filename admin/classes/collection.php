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

// Require field class
require_once(JPATH_ROOT.'/administrator/components/com_collector/classes/field.php');

/**
 * Collection class
 *
 * @package	Collector
 */
class CollectorCollection
{
	/**
	 * Collection Id
	 *
	 * @var int
	 */
	var $_id;
	
	/**
	 * Collection data
	 * 
	 * @var array object
	 */
	var $_collection;
	
	/**
	 * List of items
	 * 
	 * @var array
	 */
	var $_list;
	
	/**
	 * User object
	 * 
	 * @var object JUser
	 */
	var $_user;
	
	/**
	 * Object constructor to set collection
	 *
	 * Can be overloaded/supplemented by the child class
	 *
	 * @access	protected
	 * @param	int						$collection	Collection Id
	 */
	function __construct( $collection )
	{
		// Initialisation
		$this->_id = $collection;
		$this->_user = JFactory::getUser();
		
		$this->_db = JFactory::getDBO();
		
		// Recuperation de la liste complete
		$this->_loadCollection();
	}
	
	/**
	 * Returns a reference to the a collection, always creating it
	 *
	 * @param	int							$collection	Collection Id
	 * @return	object CollectorCollection				Reference to a collection class
	 */
	function &getInstance( $collection )
	{
		$instance = new CollectorCollection($collection);

		return $instance;
	}
	
	/**
	 * Method to load collection data
	 * Put data in <var>_collection</var>
	 *
	 * @access	private
	 * @return	boolean	True on success
	 */
	function _loadCollection()
	{
		if($this->_id == '0')
		{
			return false;
		}
		
		// Load the collection if it doesn't already exist
		if (empty($this->_collection))
		{
			// Get the page/component configuration
			$query = 'SELECT c.*, u.name AS author,'.
					' CASE WHEN CHAR_LENGTH(c.alias) THEN CONCAT_WS(\':\', c.id, c.alias) ELSE c.id END as slug'.
					' FROM #__collector AS c'.
					' LEFT JOIN #__users AS u ON u.id = c.created_by' .
					' WHERE c.id = '. (int) $this->_id;
			$this->_db->setQuery($query);
			$this->_collection = $this->_db->loadObject();
			
			if ( ! $this->_collection ) {
				return false;
			}
			if($this->_collection->publish_down == $this->_db->getNullDate()) {
				//$this->_collection->publish_down = JText::_('COM_COLLECTOR_NEVER');
			}
		}
		
		return true;
	}
	
	/**
	 * Method to load fields informations
	 *
	 * @access	private
	 * @param	string	$display	Type of display ('list','detail')
	 * @param	boolean	$access		True if access check
	 * @return	mixed				Array of fields objects. False if no fields loaded.
	 */
	function _loadCollectionFields( $display='detail', $access=false )
	{
		if ( $this->_id == '0' )
		{
			return false;
		}
		
		$aid		= (int) $this->_user->get('aid', 0);
		
		$jnow		= JFactory::getDate();
		$now		= $jnow->toSql();
		$nullDate	= $this->_db->getNullDate();
		
		$query = 'SELECT c.*, u.name AS author, u.usertype';
		$query .= ' FROM #__collector_fields AS c';
		$query .= ' LEFT JOIN #__users AS u ON u.id = c.created_by';
		$query .= ' WHERE collection = ' . $this->_id;
		if ( $display == 'list' )
		{
			$query .= ' AND c.listing = 1';
		}
		
		if ( $access == true )
		{
			$query .= ' AND c.access <= '. (int) $aid;
			$query .= ' AND ( ';
			$query .= ' ( c.created_by = ' . (int) $this->_user->id . ' ) ';
			$query .= '   OR ';
			$query .= ' ( c.state = 1' .
					' AND ( c.publish_up = '.$this->_db->Quote($nullDate).' OR c.publish_up <= '.$this->_db->Quote($now).' )' .
					' AND ( c.publish_down = '.$this->_db->Quote($nullDate).' OR c.publish_down >= '.$this->_db->Quote($now).' )';
			$query .= '   ) ';
			$query .= ' ) ';
		}
		
		$query .= ' ORDER BY ordering';
		
		$this->_db->setQuery($query);
		$fields = $this->_db->loadObjectList();
		
		if ( ! $fields ) {
			return false;
		}
		
		return $fields;
	}
	
	/**
	 * Method to load items
	 *
	 * Can be overloaded/supplemented by the child class
	 *
	 * @access	private
	 */
	function _loadList()
	{
		$query = 'SELECT 
	}
}