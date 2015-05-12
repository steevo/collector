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
 * Templates Controller
 *
 * @package  	Collector
 */
class CollectorControllerTemplates extends CollectorsController
{
	/**
	 * Method to edit template
	 *
	 * @access	public
	 */
	function edit()
	{
		$app = JFactory::getApplication();
		$app->input->set('view', 'templateedit');
		$app->input->set('hidemainmenu',1);
		$app->input->set('edit', 'edit');
		
		parent::display();
	}
	
	/**
	 * Method to copy template
	 *
	 * @access	public
	 */
	function copy()
	{
		$app = JFactory::getApplication();
		$app->input->set('view', 'templateedit');
		$app->input->set('hidemainmenu',1);
		$app->input->set('edit', 'copy');
		
		parent::display();
	}
	
	/**
	 * Method to edit source template
	 *
	 * @access	public
	 */
	function edit_source()
	{
		$app = JFactory::getApplication();
		$app->input->set('view', 'htmledit');
		$app->input->set('hidemainmenu',1);
		
		parent::display();
	}
	
	/**
	 * Method to add template
	 *
	 * @access	public
	 */
	function add()
	{
		$app = JFactory::getApplication();
		$app->input->set('view', 'templateedit');
		$app->input->set('hidemainmenu',1);
		$app->input->set('edit', 'add');
		
		parent::display();
	}
	
	/**
	 * Cancels an edit template operation
	 *
	 * @access	public
	 */
	function cancel()
	{
		$app = JFactory::getApplication();
		
		$option = $app->input->getCmd( 'option' );
		$view = $app->input->getCmd( 'view' );
		$collection = $app->input->getCmd( 'collection' );
		
		$model = $this->getModel('templateedit');
				
		$this->setRedirect( 'index.php?option='.$option.'&view='.$view.'&collection='.$collection, $msg );
	}
	
	/**
	 * Method to save template
	 *
	 * @access	public
	 */
	function save()
	{
		$app = JFactory::getApplication();
		
		$option = $app->input->getCmd( 'option' );
		$view = $app->input->getCmd( 'view' );
		$collection = $app->input->getCmd( 'collection' );
		
		$model = $this->getModel('templateedit');
		
		if ($model->store())
		{
			$msg = JText::_( 'COM_COLLECTOR_TPL_SAVED' );
		}
		else
		{
			$msg = JText::_( 'COM_COLLECTOR_ERROR_SAVE' );
		}
		
		$this->setRedirect( 'index.php?option='.$option.'&view='.$view.'&collection='.$collection, $msg );
	}
	
	/**
	 * Method to save source template
	 *
	 * @access	public
	 */
	function save_source()
	{
		$app = JFactory::getApplication();
		
		$option = $app->input->getCmd( 'option' );
		$view = $app->input->getCmd( 'view' );
		$collection = $app->input->getCmd( 'collection' );
		
		$model = $this->getModel('htmledit');
		
		if ($model->store())
		{
			$msg = JText::_( 'COM_COLLECTOR_TPL_SAVED' );
		}
		else
		{
			$msg = JText::_( 'COM_COLLECTOR_ERROR_SAVE' );
		}
		
		$this->setRedirect( 'index.php?option='.$option.'&view='.$view.'&collection='.$collection, $msg );
	}
	
	/**
	 * Method to save template and reload edition
	 *
	 * @access	public
	 */
	function apply()
	{
		$app = JFactory::getApplication();
		
		$option = $app->input->getCmd( 'option' );
		$id = $app->input->getCmd( 'id' );
		
		$model = $this->getModel('templateedit');
		
		if ($model->store())
		{
			$msg = JText::_( 'COM_COLLECTOR_TPL_SAVED' );
		}
		else
		{
			$msg = JText::_( 'COM_COLLECTOR_ERROR_SAVE' );
		}
		
		$this->setRedirect( 'index.php?option='.$option.'&view=templateedit&action=edit&cid[]='.$id, $msg );
	}
	
	/**
	 * Method to delete template
	 *
	 * @access	public
	 */
	function remove()
	{
		$app = JFactory::getApplication();
		
		$option = $app->input->getCmd( 'option' );
		$view = $app->input->getCmd( 'view' );
		
		$model = $this->getModel('templates');
		
		$msg = $model->remove();
		
		$this->setRedirect( 'index.php?option='.$option.'&view='.$view, $msg );
	}
	
	/**
	 * Method to set template as default
	 *
	 * @access	public
	 */
	function setdefault()
	{
		$app = JFactory::getApplication();
		
		// Check for request forgeries
		JSession::checkToken() or jexit( 'Invalid Token' );
		
		$option = $app->input->getCmd( 'option' );
		$view = $app->input->getCmd( 'view' );
		$collection = $app->input->getCmd( 'collection' );
		
		// Get some variables from the request
		$cid	= $app->input->getVar( 'cid', array(), 'post', 'array' );
		JArrayHelper::toInteger($cid);
		
		if (isset($cid[0]) && $cid[0]) {
			$id = $cid[0];
		} else {
			$this->setRedirect( 'index.php?option='.$option.'&view='.$view.'&collection='.$collection, JText::_('COM_COLLECTOR_NO_ITEMS_SELECTED') );
			return false;
		}
		
		$model = $this->getModel( 'templates' );
		if ($model->setHome($id)) {
			$msg = JText::_( 'COM_COLLECTOR_DEFAULT_TEMPLATE_SET' );
		} else {
			$msg = $model->getError();
		}
		$this->setRedirect( 'index.php?option='.$option.'&view='.$view.'&collection='.$collection, $msg );
	}
	
	/**
	 * Method to display a view
	 *
	 * @access	public
	 */
	public function display($cachable = false, $urlparams = false)
	{
		parent::display($cachable, $urlparams);
	}
}