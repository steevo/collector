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

jimport('joomla.application.component.view');

/**
 * HTML Update View class for the Collector component
 *
 * @package	Collector
 */
class CollectorViewCopycollection extends JViewLegacy
{
	/**
	 * Display function
	 */
	function display($tpl = null)
	{
		// Initialise variables.
		$app = JFactory::getApplication();

		$cid = $app->input->getVar('cid');
		$this->collection = $cid[0];

		$this->copy_mode = $app->input->getVar('copy_mode');
		$this->assetgroup_id = $app->input->getVar('assetgroup_id');

		CollectorHelper::addSubmenu('collections');

		// Set the toolbar
		$this->addToolBar();
		$this->sidebar = JHtmlSidebar::render();
 
		// Display the template
		parent::display($tpl);
	}
 
	/**
	 * Setting the toolbar
	 */
	protected function addToolBar() 
	{
		JFactory::getApplication()->input->set('hidemainmenu', true);
		
		JToolBarHelper::title(JText::_('COM_COLLECTOR_BATCH_COLLECTION'));

		JToolBarHelper::custom('collections.back','arrow-left-2','','JTOOLBAR_BACK',false);
	}
}
?>