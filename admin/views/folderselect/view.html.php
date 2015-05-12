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
 * HTML Folderselect View class for the Collector component
 *
 * @package	Collector
 */
class CollectorViewFolderselect extends JViewLegacy
{
	/**
	 * Display function
	 */
	function display($tpl = null)
	{
		JHtml::script(Juri::base() . 'components/com_collector/assets/js/popup-filemanager.js', true);
		
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
			$this->lists = $this->get('List');
			$this->task = $this->get('Task');
			$this->folder = $this->get('Folder');
			$this->folderList = $this->get('FolderList');
			$this->elements = $this->get('Elements');
			
			parent::display($tpl);
		}
	}
}