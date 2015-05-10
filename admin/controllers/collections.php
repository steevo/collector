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
 * Collections list controller class
 *
 * @package  	Collector
 */
class CollectorControllerCollections extends JControllerAdmin
{
	/**
	 * Proxy for getModel.
	 * @since	1.6
	 */
	public function getModel($name = 'Collection', $prefix = 'CollectorModel', $config = array('ignore_request' => true)) 
	{
		$model = parent::getModel($name, $prefix, $config);
		return $model;
	}
	
	/**
	 * Method to set the home property for a list of items
	 *
	 * @since	1.6
	 */
	function home()
	{
		$app = JFactory::getApplication();
		
		// Check for request forgeries
		JSession::checkToken() or die(JText::_('JINVALID_TOKEN'));

		// Get items to publish from the request.
		$cid	= $app->input->getVar('cid', array(), '', 'array');
		
		if (empty($cid)) {
			JError::raiseWarning(500, JText::_('COM_COLLECTOR_NO_ITEMS_SELECTED'));
		} else {
			// Get the model.
			$model = $this->getModel();

			// Make sure the item ids are integers
			$id = $cid[0];

			// Publish the items.
			if (!$model->setHome($id)) {
				JError::raiseWarning(500, $model->getError());
			} else {
				$this->setMessage(JText::_('COM_COLLECTOR_DEFAULT_COLLECTION_SET'));
			}
		}

		$this->setRedirect(JRoute::_('index.php?option='.$this->option.'&view='.$this->view_list, false));
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
		$pks = $this->input->post->get('cid', array(), 'array');
		$order = $this->input->post->get('order', array(), 'array');

		// Sanitize the input
		JArrayHelper::toInteger($pks);
		JArrayHelper::toInteger($order);

		// Get the model
		$model = $this->getModel();

		// Save the ordering
		$return = $model->saveorder($pks, $order);

		if ($return)
		{
			echo "1";
		}

		// Close the application
		JFactory::getApplication()->close();
	}
}