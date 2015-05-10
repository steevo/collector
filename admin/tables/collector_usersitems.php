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

/**
 * Collector_usersitems table
 * @package		Collector
 */
class TableCollector_usersitems extends JTable
{
	/**
	 * @param database A database connector object
	 */
	function TableCollector_usersitems( &$db )
	{
		parent::__construct( '#__collector_usersitems', 'id', $db );
	}
	
	/**
	 * Overloaded load function
	 *
	 * @param   mixed    $keys   An optional primary key value to load the row by, or an array of fields to match.  If not
	 *                           set the instance property value is used.
	 * @param   boolean  $reset  True to reset the default values before loading the new row.
	 *
	 * @return  boolean  True if successful. False if row not found or on error (internal error state set in that case).
	 */
	public function load($keys = null, $reset = true)
	{
		$return = parent::load($keys, $reset);
		
		return $return;
	}
}