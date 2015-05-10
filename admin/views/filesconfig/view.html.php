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

jimport('joomla.application.component.view');

/**
 * HTML Filesconfig View class for the Collector component
 *
 * @package	Collector
 */
class CollectorViewFilesconfig extends JViewLegacy
{
	protected $items;
	protected $state;

	/**
	 * Display the view
	 *
	 * @return	void
	 */
	public function display($tpl = null)
	{
		global $option;
		
		$user = JFactory::getUser();
		if (!$user->authorise('com_collector', 'filemanage')) {
			//Get a handle to the Joomla! application object
			$application = JFactory::getApplication(); 
			//add a message to the message queue
			$application->enqueueMessage( JText::_( 'COM_COLLECTOR_SUPERADMIN_ONLY' ), 'error' );
		} else {
			//Get datas from the model
			$this->items		= $this->get('Items');
			$this->types		= $this->get('Types');
			$this->row			= $this->get('Row');
			$this->state		= $this->get('State');
			
			parent::display($tpl);
		}
	}
}