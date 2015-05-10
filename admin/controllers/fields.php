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
 * Fields Controller
 *
 * @package  	Collector
 */
class CollectorControllerFields extends JControllerAdmin
{
	/**
	 * Proxy for getModel.
	 * @since	1.6
	 */
	public function getModel($name = 'field', $prefix = 'CollectorModel', $config = array('ignore_request' => true))
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
	 * Method to allow frontend edition
	 *
	 * @access	public
	 */
	public function open()
	{
		$app = JFactory::getApplication();
		
		$collection = $app->input->getCmd( 'collection' );
		
		$model = $this->getModel();
		$msg = $model->edit(1);
		
		$this->setRedirect( 'index.php?option='.$this->option.'&view='.$this->view_list.'&collection='.$collection, $msg );
	}
	
	/**
	 * Method to forbid frontend edition
	 *
	 * @access	public
	 */
	public function lock()
	{
		$app = JFactory::getApplication();
		
		$collection = $app->input->getCmd( 'collection' );
		
		$model = $this->getModel();
		$msg = $model->edit(0);
		
		$this->setRedirect( 'index.php?option='.$this->option.'&view='.$this->view_list.'&collection='.$collection, $msg );
	}
	
	/**
	 * Method to hide from listing
	 *
	 * @access	public
	 */
	public function hide()
	{
		$app = JFactory::getApplication();
		
		$collection = $app->input->getCmd( 'collection' );
		
		$model = $this->getModel();
		$msg = $model->listing(0);
		
		$this->setRedirect( 'index.php?option='.$this->option.'&view='.$this->view_list.'&collection='.$collection, $msg );
	}
	
	/**
	 * Method to show in listing
	 *
	 * @access	public
	 */
	public function nohide()
	{
		$app = JFactory::getApplication();
		
		$collection = $app->input->getCmd( 'collection' );
		
		$model = $this->getModel();
		$msg = $model->listing(1);
		
		$this->setRedirect( 'index.php?option='.$this->option.'&view='.$this->view_list.'&collection='.$collection, $msg );
	}
	
	/**
	 * Method to allow sorting
	 *
	 * @access	public
	 */
	public function sort()
	{
		$app = JFactory::getApplication();
		
		$collection = $app->input->getCmd( 'collection' );
		
		$model = $this->getModel();
		$msg = $model->sort(1);
		
		$this->setRedirect( 'index.php?option='.$this->option.'&view='.$this->view_list.'&collection='.$collection, $msg );
	}
	
	/**
	 * Method to forbid sorting
	 *
	 * @access	public
	 */
	public function nosort()
	{
		$app = JFactory::getApplication();
		
		$collection = $app->input->getCmd( 'collection' );
		
		$model = $this->getModel();
		$msg = $model->sort(0);
		
		$this->setRedirect( 'index.php?option='.$this->option.'&view='.$this->view_list.'&collection='.$collection, $msg );
	}
	
	/**
	 * Method to activate field required
	 *
	 * @access	public
	 */
	public function required()
	{
		$app = JFactory::getApplication();
		
		$collection = $app->input->getCmd( 'collection' );
		
		$model = $this->getModel();
		$msg = $model->required(1);
		
		$this->setRedirect( 'index.php?option='.$this->option.'&view='.$this->view_list.'&collection='.$collection, $msg );
	}
	
	/**
	 * Method to desactivate field required
	 *
	 * @access	public
	 */
	public function norequired()
	{
		$app = JFactory::getApplication();
		
		$collection = $app->input->getCmd( 'collection' );
		
		$model = $this->getModel();
		$msg = $model->required(0);
		
		$this->setRedirect( 'index.php?option='.$this->option.'&view='.$this->view_list.'&collection='.$collection, $msg );
	}
	
	/**
	 * Method to activate uniqueness
	 *
	 * @access	public
	 */
	public function unik()
	{
		$app = JFactory::getApplication();
		
		$collection = $app->input->getCmd( 'collection' );
		
		$model = $this->getModel();
		$msg = $model->unik(1);
		
		$this->setRedirect( 'index.php?option='.$this->option.'&view='.$this->view_list.'&collection='.$collection, $msg );
	}
	
	/**
	 * Method to desactivate uniqueness
	 *
	 * @access	public
	 */
	public function nounik()
	{
		$app = JFactory::getApplication();
		
		$collection = $app->input->getCmd( 'collection' );
		
		$model = $this->getModel();
		$msg = $model->unik(0);
		
		$this->setRedirect( 'index.php?option='.$this->option.'&view='.$this->view_list.'&collection='.$collection, $msg );
	}
	
	/**
	 * Method to allow filter
	 *
	 * @access	public
	 */
	public function deny_filter()
	{
		$app = JFactory::getApplication();
		
		$collection = $app->input->getCmd( 'collection' );
		
		$model = $this->getModel();
		$msg = $model->filter(0);
		
		$this->setRedirect( 'index.php?option='.$this->option.'&view='.$this->view_list.'&collection='.$collection, $msg );
	}
	
	/**
	 * Method to forbid filter
	 *
	 * @access	public
	 */
	public function allow_filter()
	{
		$app = JFactory::getApplication();
		
		$collection = $app->input->getCmd( 'collection' );
		
		$model = $this->getModel();
		$msg = $model->filter(1);
		
		$this->setRedirect( 'index.php?option='.$this->option.'&view='.$this->view_list.'&collection='.$collection, $msg );
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
	 * Method to set field as default
	 *
	 * @access	public
	 */
	public function home()
	{
		$app = JFactory::getApplication();
		
		// Check for request forgeries
		JSession::checkToken() or die(JText::_('JINVALID_TOKEN'));
		
		// Get some variables from the request
		$cid	= $app->input->getVar( 'cid', array(), '', 'array' );
		JArrayHelper::toInteger($cid);
		$collection = $app->input->getVar( 'collection' );
		
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
				$this->setMessage(JText::_('COM_COLLECTOR_DEFAULT_FIELD_SET'));
			}
		}
		
		$this->setRedirect( 'index.php?option='.$this->option.'&view='.$this->view_list.'&collection='.$collection, $msg );
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