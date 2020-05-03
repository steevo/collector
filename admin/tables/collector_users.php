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
 * Collector_users table
 * @package		Collector
 */
class TableCollector_users extends JTable
{
	/** @var int Primary key */
	var $id	= null;
	
	/** @var int */
	var $user = null;
	
	/** @var int */
	var $collection = null;
	
	/** @var string */
	var $own = null;
	
	/** @var int */
	var $own_access = null;

	/** @var string */
	var $manco = null;
	
	/** @var int */
	var $manco_access = null;

	/** @var string */
	var $dispo = null;
	
	/** @var int */
	var $dispo_access = null;

	/**
	 * @param database A database connector object
	 */
	function __construct( &$db )
	{
		$db = JFactory::getDBO();
		parent::__construct( '#__collector_users', 'id', $db );
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
		return true;
	}
	
	/**
	 * function to load a user collections datas
	 *
	 * @access	public
	 * @param	int		$oid			User Id
	 * @param	int		$collection		Collection Id
	 * @return	boolean
	 */
	function loadUserCollection($oid=null,$collection=null)
	{
		$k = $this->_tbl_key;
 
        if ($oid !== null) {
            $this->$k = $oid;
        }
 
        $oid = $this->$k;
 
        if ($oid === null) {
            return false;
        }
        if ($collection === null) {
            return false;
        }
        $this->reset();
 
        $db = $this->getDBO();
 
        $query = 'SELECT *'
        . ' FROM '.$this->_tbl
        . ' WHERE user = '.$db->Quote($oid)
		. ' AND collection = '.$db->Quote($collection);
        $db->setQuery( $query );
 
        if ($result = $db->loadAssoc( )) {
            return $this->bind($result);
        }
        else
        {
            $this->setError( $db->getErrorMsg() );
            return false;
        }
	}
}
