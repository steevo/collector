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

namespace Steevo\Component\Collector\Administrator\View\Filemanager;

defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\Helper\ContentHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\View\GenericDataException;
use Joomla\CMS\MVC\View\HtmlView as BaseHtmlView;
use Joomla\CMS\Toolbar\Toolbar;
use Joomla\CMS\Toolbar\ToolbarHelper;
use Joomla\CMS\Component\ComponentHelper;
use Steevo\Component\Collector\Administrator\Helper\CollectorHelper;

/**
 * HTML Filemanager View class for the Collector component
 *
 * @package	Collector
 */
class HtmlView extends BaseHtmlView
{
	/**
	 * Display function
	 */
	function display($tpl = null)
	{
		$lang = Factory::getApplication()->getLanguage();
		$extension = 'com_media';
		$base_dir = JPATH_ADMINISTRATOR;
		$lang->load($extension, $base_dir);

		$user = Factory::getApplication()->getIdentity();
		$session	= Factory::getApplication()->getSession();
		$config = ComponentHelper::getParams('com_media');
		
		$document = Factory::getApplication()->getDocument();
		
		$model = $this->getModel('filemanager');
		
		// What Access Permissions does this user have? What can (s)he do?
		$this->canDo	= CollectorHelper::getActions();
		
		CollectorHelper::addSubmenu('filemanager');
		
		// Set the toolbar
		$this->addToolbar();
		
		if (!$user->authorise('com_collector', 'filemanage')) {
			//Get a handle to the Joomla! application object
			$app = Factory::getApplication(); 
			//add a message to the message queue
			$app->enqueueMessage( Text::_( 'COM_COLLECTOR_SUPERADMIN_ONLY' ), 'error' );
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
		$bar = ToolBar::getInstance('toolbar');
		$user = Factory::getApplication()->getIdentity();

		// Set the titlebar text
		ToolBarHelper::title( Text::_( 'COM_COLLECTOR_FILE_MANAGEMENT' ), 'mediamanager' );

		// Add a upload button
		if ($user->authorise('core.create', 'com_collector'))
		{
			$title = Text::_('ToolBar_UPLOAD');
			$dhtml = "<button data-toggle=\"collapse\" data-target=\"#collapseUpload\" class=\"btn btn-small btn-success\">
						<i class=\"icon-plus icon-white\" title=\"$title\"></i>
						$title</button>";
			$bar->appendButton('Custom', $dhtml, 'upload');
		}

		// Add a delete button
		if ($this->canDo->get('core.delete'))
		{
			ToolBarHelper::deleteList('', 'filemanager.remove', 'ToolBar_DELETE');
		}
		// Add a preferences button
		if ($this->canDo->get('core.admin'))
		{
			$bar->appendButton( 'Popup', 'options', Text::_('COM_COLLECTOR_EXT_MANAGE'), 'index.php?option=com_collector&tmpl=component&view=filesconfig', 600, 450 );
		}
	}
}