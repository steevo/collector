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

namespace Steevo\Component\Collector\Administrator\View\Folder;

defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\Helper\ContentHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\View\GenericDataException;
use Joomla\CMS\MVC\View\HtmlView as BaseHtmlView;
use Joomla\CMS\Toolbar\Toolbar;
use Joomla\CMS\Toolbar\ToolbarHelper;
use Joomla\CMS\Object\CMSObject;

/**
 * HTML Folderlist View class for the Collector component
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
			$this->_tmp_folder = new CMSObject;
		}
	}
}