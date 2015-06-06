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
 * HTML Filemanager View class for the Collector component
 *
 * @package	Collector
 */
class CollectorViewFilemanager extends JViewLegacy
{
	/**
	 * Display function
	 */
	function display($tpl = null)
	{
		$lang = JFactory::getLanguage();
		$extension = 'com_media';
		$base_dir = JPATH_ADMINISTRATOR;
		$lang->load($extension, $base_dir);

		$user = JFactory::getUser();
		$session	= JFactory::getSession();
		$config = JComponentHelper::getParams('com_media');
		
		$document = JFactory::getDocument();
		
		$model = $this->getModel('filemanager');
		
		// What Access Permissions does this user have? What can (s)he do?
		$this->canDo	= CollectorHelper::getActions();
		
		CollectorHelper::addSubmenu('filemanager');
		
		// Set the toolbar
		$this->addToolbar();
		$this->sidebar = JHtmlSidebar::render();
		
		if (!$user->authorise('com_collector', 'filemanage')) {
			//Get a handle to the Joomla! application object
			$app = JFactory::getApplication(); 
			//add a message to the message queue
			$app->enqueueMessage( JText::_( 'COM_COLLECTOR_SUPERADMIN_ONLY' ), 'error' );
		} else {
			// Get data from the model
			$lists = $model->getList();
			$navigation = $model->get_path_navigation();
			$folder = $model->getFolder();
			
			$this->folder = $folder;
			$this->lists = $lists;
			$this->navigation = $navigation;
			$this->session = $session;
			$this->config = &$config;
			
			parent::display($tpl);
		}
	}
	
	/**
	 * Add the page title and toolbar.
	 *
	 * @since	1.6
	 */
	protected function addToolbar()
	{
		// Get the toolbar object instance
		$bar = JToolBar::getInstance('toolbar');
		$user = JFactory::getUser();

		// Set the titlebar text
		JToolBarHelper::title( JText::_( 'COM_COLLECTOR_FILE_MANAGEMENT' ), 'mediamanager' );

		// Add a upload button
		if ($user->authorise('core.create', 'com_collector'))
		{
			$title = JText::_('JTOOLBAR_UPLOAD');
			$dhtml = "<button data-toggle=\"collapse\" data-target=\"#collapseUpload\" class=\"btn btn-small btn-success\">
						<i class=\"icon-plus icon-white\" title=\"$title\"></i>
						$title</button>";
			$bar->appendButton('Custom', $dhtml, 'upload');
		}

		// Add a delete button
		if ($this->canDo->get('core.delete'))
		{
			JToolBarHelper::deleteList('', 'filemanager.remove', 'JTOOLBAR_DELETE');
		}
		// Add a preferences button
		if ($this->canDo->get('core.admin'))
		{
			$bar->appendButton( 'Popup', 'options', JText::_('COM_COLLECTOR_EXT_MANAGE'), 'index.php?option=com_collector&tmpl=component&view=filesconfig', 600, 450 );
		}
	}
}