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
	function getVersions()
    {
        //get versions informations
        ob_start();
        $url = 'http://joomlacode.org/svn/collector/branches/collector_version.xml';
	
		if (function_exists('curl_init')) {
			//curl is the preferred function
			$crl = curl_init();
			$timeout = 5;
			curl_setopt($crl, CURLOPT_URL, $url);
			curl_setopt($crl, CURLOPT_USERPWD, 'anonymous:');
			curl_setopt($crl, CURLOPT_RETURNTRANSFER, 1);
			curl_setopt($crl, CURLOPT_CONNECTTIMEOUT, $timeout);
			$CollectorVersionRaw = curl_exec($crl);
			curl_close($crl);
		} else {
			//get the file directly if curl is disabled
			$context = stream_context_create(array(
				'http' => array(
					'header'  => "Authorization: Basic " . base64_encode("anonymous:"),
					'timeout' => 10
				)
			));
			if ( ($CollectorVersionRaw = file_get_contents($url, false, $context)) == false )
			{
				//error to load file
				$CollectorVersionRaw = "<?xml version='1.0' standalone='yes'?>
					<document>
						<component>" . JText::_('COM_COLLECTOR_UNKNOWN') . "</component>
					</document>";
			}
			
			if (!strpos($CollectorVersionRaw, '<document>')) {
				//file_get_content is often blocked by hosts, return an error message
				echo JText::_('COM_COLLECTOR_CURL_DISABLED');
				return;
			}
		}
		
		if ($xml = JFactory::getXML($CollectorVersionRaw, false)) {
			if (isset($xml->component)) {
				$CollectorVersion = (string)$xml->component;
			} else {
				echo JText::_('COM_COLLECTOR_CURL_DISABLED');
				return;
			}
		}
		
        ob_end_clean();
        return $CollectorVersion;
    }
}