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

jimport( 'joomla.mail.helper' );
jimport( 'joomla.filesystem.file' );


/**
 * HTML Removeuseritem View class for the Collector component
 * @package		Joomla
 * @subpackage	Collector
 * @license		GNU/GPL
 */
class CollectorViewRemoveuseritem extends JViewLegacy
{
	protected $state;
	protected $items;
	protected $pagination;
	
	function display($tpl = null)
	{
		$app		= JFactory::getApplication();
		$params		= $app->getParams();

		// Get some data from the models
		$state		= $this->get('State');
		$items		= $this->get('Items');
		$this->items = $items;
		
		$this->params = $params;
		$this->state = $state;
		
		parent::display($tpl);
	}
}