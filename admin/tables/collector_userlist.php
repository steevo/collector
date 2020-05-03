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
 * Collector_userlist table
 * @package		Collector
 */
class TableCollector_userlist extends JTable
{
	/**
	 * @param database A database connector object
	 */
	function __construct( &$db )
	{
		parent::__construct( '#__collector_userlist', 'id', $db );
	}
	
	/**
     * Method to init a user list
     *
     * @param    integer	A primary key value to copy.
     *
     * @return    boolean    True on success.
     */
    public function initList($userslist)
    {
        $db = JFactory::getDBO();
		$user	= JFactory::getUser();
		
		$query = "SELECT id FROM `#__collector_userlist` WHERE userslist = '".$userslist."' AND user = '".$user->id."'";
		$db->setQuery( $query );
		if (!$result = $db->LoadResult())
		{
			$this->user = $user->id;
			$this->userslist = $userslist;
			$this->access = 1;
			
			$this->check();
			$this->store();
			
			$result = $this->id;
		}
		
		return $result;
    }
}