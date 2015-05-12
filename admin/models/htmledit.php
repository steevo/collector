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

jimport( 'joomla.application.component.model' );

/**
 * Htmledit model
 * @package	Collector
 */
class CollectorsModelHtmledit extends JModelLegacy
{
	/**
	 * Htmledit model constructor
	 *
	 * @access	public
	 */
	function __construct()
	{
		parent::__construct();
	}
	
	/**
	 * Method to store a record
	 *
	 * @return	boolean	True on success
	 */
	function store()
	{
		$app = JFactory::getApplication();
		
		// Initialize some variables
		$option			= $app->input->getCmd('option');
		$name			= $app->input->getCmd('name');
		$client			= $app->input->getVar('client', '0', '', 'int');
		$filecontent	= $app->input->getVar('filecontent', '', 'post', 'string', JREQUEST_ALLOWRAW);
		
		if ($client == 1)
		{
			$client = 'collection';
		}
		else
		{
			$client = 'item';
		}
		
		$file = JPATH_SITE.'/components/com_collector/views/'.$client.'/tmpl/default_'.$name.'.php';
		
		jimport('joomla.filesystem.file');
		if ( JFile::write($file, $filecontent) )
		{
			return true;
		}
		
		return false;
	}
}
