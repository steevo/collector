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
 * HTML Validuseritem View class for the Collector component
 * @package		Joomla
 * @subpackage	Collector
 * @license		GNU/GPL
 */
class CollectorViewValiduseritem extends JViewLegacy
{
	protected $item;
	protected $params;
	protected $form;
	protected $state;
	protected $user;
	
	function display($tpl = null)
	{
		$app		= JFactory::getApplication();
		$params		= $app->getParams();

		// Get some data from the models
		$state		= $this->get('State');
		$form		= $this->get('Form');
		$item		= $this->get('Item');
		$list		= $this->get('List');
		
		$this->form = $form;
		$this->item = $item;
		$this->list = $list;
		$this->params = $params;
		$this->state = $state;
		
		parent::display($tpl);
	}
}