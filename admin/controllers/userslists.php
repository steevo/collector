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
 * Userslists Controller
 *
 * @package  	Collector
 */
class CollectorControllerUserslists extends JControllerAdmin
{
	/**
	 * Proxy for getModel.
	 * @since	1.6
	 */
	public function getModel($name = 'userslist', $prefix = 'CollectorModel', $config = array('ignore_request' => true))
	{
		$model = parent::getModel($name, $prefix, $config);
		return $model;
	}
	
	/**
	 * Method to delete a field
	 *
	 * @access	public
	 */
	public function delete()
	{
		$app = JFactory::getApplication();
		
		parent::delete();
		
		$collection = $app->input->getCmd( 'collection' );
		
		$this->setRedirect( 'index.php?option='.$this->option.'&view='.$this->view_list .'&collection='.$collection );
	}
	
	/**
     * Method to save the submitted ordering values for records.
     */
    public function saveorder()
    {
        $app = JFactory::getApplication();
		
		// Check for request forgeries.
        JSession::checkToken() or jexit(JText::_('JINVALID_TOKEN'));
 
        // Get the input
        $pks		= $app->input->getVar('cid',    null,    'post',    'array');
        $order		= $app->input->getVar('order',    null,    'post',    'array');
		$collection = $app->input->getCmd( 'collection' );
 
        // Sanitize the input
        JArrayHelper::toInteger($pks);
        JArrayHelper::toInteger($order);
 
        // Get the model
        $model = $this->getModel();
 
        // Save the ordering
        $return = $model->saveorder($pks, $order);
 
        if ($return === false)
        {
            // Reorder failed
            $message = JText::sprintf('JLIB_APPLICATION_ERROR_REORDER_FAILED', $model->getError());
            $this->setRedirect(JRoute::_('index.php?option='.$this->option.'&view='.$this->view_list.'&collection='.$collection, false), $message, 'error');
            return false;
        } else
        {
            // Reorder succeeded.
            $this->setMessage(JText::_('JLIB_APPLICATION_SUCCESS_ORDERING_SAVED'));
            $this->setRedirect(JRoute::_('index.php?option='.$this->option.'&view='.$this->view_list.'&collection='.$collection, false));
            return true;
        }
    }
	
	/**
     * Changes the order of one or more records.
     */
	public function reorder()
    {
        $app = JFactory::getApplication();
		
		// Check for request forgeries.
        JSession::checkToken() or jexit(JText::_('JINVALID_TOKEN'));
 
        // Initialise variables.
        $ids    	= $app->input->getVar('cid', null, 'post', 'array');
        $inc    	= ($this->getTask() == 'orderup') ? -1 : +1;
		$collection = $app->input->getCmd( 'collection' );
 
        $model = $this->getModel();
        $return = $model->reorder($ids, $inc);
        if ($return === false) {
            // Reorder failed.
            $message = JText::sprintf('JLIB_APPLICATION_ERROR_REORDER_FAILED', $model->getError());
            $this->setRedirect(JRoute::_('index.php?option='.$this->option.'&view='.$this->view_list.'&collection='.$collection, false), $message, 'error');
            return false;
        } else {
            // Reorder succeeded.
            $message = JText::_('JLIB_APPLICATION_SUCCESS_ITEM_REORDERED');
            $this->setRedirect(JRoute::_('index.php?option='.$this->option.'&view='.$this->view_list.'&collection='.$collection, false), $message);
            return true;
        }
    }
	
	/**
	 * Method to publish field
	 *
	 * @access	public
	 */
	public function publish()
	{
		$app = JFactory::getApplication();
		
		parent::publish();
		
		// Get some variables from the request
		$collection = $app->input->getVar( 'collection' );
		
		$this->setRedirect( 'index.php?option='.$this->option.'&view='.$this->view_list.'&collection='.$collection );
	}
	
	/**
	 * Method to unpublish field
	 *
	 * @access	public
	 */
	public function unpublish()
	{
		$app = JFactory::getApplication();
		
		parent::unpublish();
		
		// Get some variables from the request
		$collection = $app->input->getVar( 'collection' );
		
		$this->setRedirect( 'index.php?option='.$this->option.'&view='.$this->view_list.'&collection='.$collection );
	}
	
	/**
	 * Method to trash field
	 *
	 * @access	public
	 */
	public function trash()
	{
		$app = JFactory::getApplication();
		
		parent::trash();
		
		// Get some variables from the request
		$collection = $app->input->getVar( 'collection' );
		
		$this->setRedirect( 'index.php?option='.$this->option.'&view='.$this->view_list.'&collection='.$collection );
	}
	
	/**
     * Check in of one or more records.
     */
    public function checkin()
    {
        $app = JFactory::getApplication();
		
		// Check for request forgeries.
        JSession::checkToken() or jexit(JText::_('JINVALID_TOKEN'));
 
        // Initialise variables.
        $user		= JFactory::getUser();
        $ids		= $app->input->getVar('cid', null, 'post', 'array');
		$collection = $app->input->getVar( 'collection' );
 
        $model = $this->getModel();
        $return = $model->checkin($ids);
        if ($return === false) {
            // Checkin failed.
            $message = JText::sprintf('JLIB_APPLICATION_ERROR_CHECKIN_FAILED', $model->getError());
            $this->setRedirect(JRoute::_('index.php?option='.$this->option.'&view='.$this->view_list.'&collection='.$collection, false), $message, 'error');
            return false;
        } else {
            // Checkin succeeded.
            $message =  JText::plural($this->text_prefix.'_N_ITEMS_CHECKED_IN', count($ids));
            $this->setRedirect(JRoute::_('index.php?option='.$this->option.'&view='.$this->view_list.'&collection='.$collection, false), $message);
            return true;
        }
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