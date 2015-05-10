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

/**
 * Definedcontent Controller
 *
 * @package  	Collector
 */
class CollectorControllerListitems extends JControllerAdmin
{
	/**
	 * Proxy for getModel.
	 * @since	1.6
	 */
	public function getModel($name = 'Listitems', $prefix = 'CollectorModel') 
	{
		$model = parent::getModel($name, $prefix, array('ignore_request' => true));
		return $model;
	}
	
	/**
	 * Removes an item.
	 *
	 * @return  void
	 */
	function delete()
	{
		$app = JFactory::getApplication();
		
		$list = $app->input->getVar('defined');
		
		// Check for request forgeries
		JSession::checkToken() or die(JText::_('JINVALID_TOKEN'));
		
		// Get items to remove from the request.
		$cid    = $app->input->getVar('cid', array(), '', 'array');
		
		if (!is_array($cid) || count($cid) < 1) {
			JError::raiseWarning(500, JText::_($this->text_prefix.'_NO_ITEM_SELECTED'));
		} else {
			// Get the model.
			$model = $this->getModel();
			
			// Make sure the item ids are integers
			jimport('joomla.utilities.arrayhelper');
			JArrayHelper::toInteger($cid);
			
			// Remove the items.
			if ($model->delete($cid)) {
				$this->setMessage(JText::plural($this->text_prefix.'_N_ITEMS_DELETED', count($cid)));
			} else {
				$this->setMessage($model->getError());
			}
		}
		
		$this->setRedirect(JRoute::_('index.php?option=com_collector&view=listitems&defined='.$list, false));
	}

	/**
	 * Method to save the submitted ordering values for records via AJAX.
	 *
	 * @return  void
	 *
	 * @since   3.0
	 */
	public function saveOrderAjax()
	{
		JSession::checkToken() or jexit(JText::_('JINVALID_TOKEN'));

		// Get the arrays from the Request
		$pks   = $this->input->post->get('cid', null, 'array');
		$order = $this->input->post->get('order', null, 'array');

		// Get the model
		$model = $this->getModel('Listitem');
		// Save the ordering
		$return = $model->saveorder($pks, $order);
		if ($return)
		{
			echo "1";
		}
		
		// Close the application
		JFactory::getApplication()->close();
	}

	/**
	 * Method to return to lists view.
	 *
	 * @return  void
	 *
	 * @since   3.0
	 */
	public function back()
	{
		// Redirect to the list screen.
		$this->setRedirect(JRoute::_('index.php?option=com_collector&view=lists', false));
	}
}