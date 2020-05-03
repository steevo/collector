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
 * Collectors model
 * @package	Collector
 */
class CollectorModelCollectors extends JModelLegacy
{
	/**
	* Array of objects containing the collections
	* 
	* @var array
	*/
	var $_data;
	
	/**
	 * Collectors model constructor
	 *
	 * @access	public
	 */
	function __construct()
	{
		parent::__construct();
	}
	
	/**
	 * Method to create the query to select collections
	 *
	 * @access	private
	 * @return	string			Query to select collections
	 */
	function _buildQuery()
	{
		$query = ' SELECT id, name, description'
			. ' FROM `#__collector`'
		;
		return $query;
	}
	
	/**
	 * Retrieves the collections
	 *
	 * @access	public
	 * @return	array	Array of objects containing the collections from the database
	 */
	function &getData()
	{
		// Lets load the data if it doesn't already exist
		if ( empty( $this->_data ))
		{
			$query = $this->_buildQuery();
			$this->_data = $this->_getList( $query );
		}
		
		return $this->_data;
	}
	
	/**
	 * Method to check last release
	 *
	 * @access	public
	 * @return	string	Last release
	 */
	function getLastVersion()
    {
        //get versions informations
		$url = 'http://www.steevo.fr/update/com_collector.xml';
	
		$xml = '';
		$CollectorVersion = JText::_('COM_COLLECTOR_UNKNOWN');
		
		$xml = simplexml_load_file($url);
		
		if ($xml != '') {
			$CollectorVersion = $xml->update[($xml->count())-1]->version;
		}
		
		return $CollectorVersion;
    }
}