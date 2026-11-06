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

namespace Steevo\Component\Collector\Administrator\View\Folderselect;

defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\Helper\ContentHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\View\GenericDataException;
use Joomla\CMS\MVC\View\HtmlView as BaseHtmlView;
use Joomla\CMS\Toolbar\Toolbar;
use Joomla\CMS\Toolbar\ToolbarHelper;

/**
 * HTML Folderselect View class for the Collector component
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
		HTMLHelper::script(Juri::base() . 'components/com_collector/assets/js/popup-filemanager.js', true);
		
		$user = Factory::getApplication()->getIdentity();
		if (!$user->authorise('com_collector', 'filemanage'))
		{
			//Get a handle to the Joomla! application object
			$app = Factory::getApplication(); 
			//add a message to the message queue
			$app->enqueueMessage( Text::_( 'COM_COLLECTOR_SUPERADMIN_ONLY' ), 'error' );
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