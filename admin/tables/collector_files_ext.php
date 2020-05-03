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
 * Collector_files_ext table
 * @package		Collector
 */
class TableCollector_files_ext extends JTable
{
	/** @var int Primary key */
	var $id	= null;
	
	/** @var string */
	var $ext = null;
	
	/** @var int */
	var $type = null;
	
	/** @var text */
	var $ico = '';
	
	/** @var int */
	var $state = 1;
	
	/**
	 * @param database A database connector object
	 */
	function __construct( &$db )
	{
		parent::__construct( '#__collector_files_ext', 'id', $db );
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
		$this->ext = str_replace(".", "", trim($this->ext));
		
		// check for valid extension
		if( $this->ext == '' ) {
			$this->setError(JText::_( 'COM_COLLECTOR_EXT' ));
			return false;
		}
		
		// check for valid type
		if( trim($this->type) == '0' ) {
			$this->setError(JText::_( 'COM_COLLECTOR_TYPE' ));
			return false;
		}
		
		// check for valid ico
		if( trim($this->ico) == '' ) {
			$this->ico = 'page_white.png';
		}
		
		return true;
	}
}
