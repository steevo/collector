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

namespace Steevo\Component\Collector\Administrator\View\Filesconfig;

defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\Helper\ContentHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\View\GenericDataException;
use Joomla\CMS\MVC\View\HtmlView as BaseHtmlView;
use Joomla\CMS\Toolbar\Toolbar;
use Joomla\CMS\Toolbar\ToolbarHelper;

/**
 * HTML Filesconfig View class for the Collector component
 *
 * @package	Collector
 */
class HtmlView extends BaseHtmlView
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
		
		$user = Factory::getApplication()->getIdentity();
		if (!$user->authorise('com_collector', 'filemanage')) {
			//Get a handle to the Joomla! application object
			$application = Factory::getApplication(); 
			//add a message to the message queue
			$application->enqueueMessage( Text::_( 'COM_COLLECTOR_SUPERADMIN_ONLY' ), 'error' );
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