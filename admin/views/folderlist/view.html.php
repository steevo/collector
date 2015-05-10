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
 * HTML Folderlist View class for the Collector component
 *
 * @package	Collector
 */
class CollectorViewFolderlist extends JViewLegacy
{
	/**
	 * Display function
	 */
	function display($tpl = null)
	{
		$user = JFactory::getUser();
		if (!$user->authorise('com_collector', 'filemanage'))
		{
			//Get a handle to the Joomla! application object
			$app = JFactory::getApplication(); 
			//add a message to the message queue
			$app->enqueueMessage( JText::_( 'COM_COLLECTOR_SUPERADMIN_ONLY' ), 'error' );
		}
		else
		{	
			// Get data from the model
			$this->folders = $this->get('Folders');
			
			parent::display($tpl);
		}
	}

	function setFolder($index = 0)
	{
		if (isset($this->folders[$index]))
		{
			$this->_tmp_folder = &$this->folders[$index];
		}
		else
		{
			$this->_tmp_folder = new JObject;
		}
	}
}