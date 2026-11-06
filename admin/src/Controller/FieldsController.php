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

namespace Steevo\Component\Collector\Administrator\Controller;

defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\Controller\AdminController;
use Joomla\CMS\Session\Session;
use Joomla\CMS\Router\Route;
use Joomla\Utilities\ArrayHelper;

/**
 * Fields Controller
 *
 * @package  	Collector
 */
class FieldsController extends AdminController
{
	/**
	 * Proxy for getModel.
	 * @since	1.6
	 */
	public function getModel($name = 'field', $prefix = 'Administrator', $config = array('ignore_request' => true))
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
		$app = Factory::getApplication();
		
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
		$app = Factory::getApplication();
		
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
		$app = Factory::getApplication();
		
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
		$app = Factory::getApplication();
		
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
		$app = Factory::getApplication();
		
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
		$app = Factory::getApplication();
		
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
		$app = Factory::getApplication();
		
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
		$app = Factory::getApplication();
		
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
		$app = Factory::getApplication();
		
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
		$app = Factory::getApplication();
		
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
		$app = Factory::getApplication();
		
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
		$app = Factory::getApplication();
		
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
		$app = Factory::getApplication();
		
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
        $app = Factory::getApplication();
		
		// Check for request forgeries.
        Session::checkToken() or jexit(Text::_('JINVALID_TOKEN'));
 
        // Get the input
        $pks		= $app->input->get('cid',    null,    'post',    'array');
        $order		= $app->input->get('order',    null,    'post',    'array');
		$collection = $app->input->getCmd( 'collection' );
 
        // Sanitize the input
        $pks = ArrayHelper::toInteger($pks);
        $order = ArrayHelper::toInteger($order);
 
        // Get the model
        $model = $this->getModel();
 
        // Save the ordering
        $return = $model->saveorder($pks, $order);
 
        if ($return === false)
        {
            // Reorder failed
            $message = Text::sprintf('JLIB_APPLICATION_ERROR_REORDER_FAILED', $model->getError());
            $this->setRedirect(Route::_('index.php?option='.$this->option.'&view='.$this->view_list.'&collection='.$collection, false), $message, 'error');
            return false;
        } else
        {
            // Reorder succeeded.
            $this->setMessage(Text::_('JLIB_APPLICATION_SUCCESS_ORDERING_SAVED'));
            $this->setRedirect(Route::_('index.php?option='.$this->option.'&view='.$this->view_list.'&collection='.$collection, false));
            return true;
        }
    }
	
	/**
     * Changes the order of one or more records.
     */
	public function reorder()
    {
        $app = Factory::getApplication();
		
		// Check for request forgeries.
        Session::checkToken() or jexit(Text::_('JINVALID_TOKEN'));
 
        // Initialise variables.
        $ids    	= $app->input->get('cid', null, 'post', 'array');
        $inc    	= ($this->getTask() == 'orderup') ? -1 : +1;
		$collection = $app->input->getCmd( 'collection' );
 
        $model = $this->getModel();
        $return = $model->reorder($ids, $inc);
        if ($return === false) {
            // Reorder failed.
            $message = Text::sprintf('JLIB_APPLICATION_ERROR_REORDER_FAILED', $model->getError());
            $this->setRedirect(Route::_('index.php?option='.$this->option.'&view='.$this->view_list.'&collection='.$collection, false), $message, 'error');
            return false;
        } else {
            // Reorder succeeded.
            $message = Text::_('JLIB_APPLICATION_SUCCESS_ITEM_REORDERED');
            $this->setRedirect(Route::_('index.php?option='.$this->option.'&view='.$this->view_list.'&collection='.$collection, false), $message);
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
		$app = Factory::getApplication();
		
		// Check for request forgeries
		Session::checkToken() or die(Text::_('JINVALID_TOKEN'));
		
		// Get some variables from the request
		$cid	= $app->input->get( 'cid', array(), '', 'array' );
		$cid = ArrayHelper::toInteger($cid);
		$collection = $app->input->get( 'collection' );
		
		if (empty($cid)) {
			Factory::getApplication()->enqueueMessage(Text::_('COM_COLLECTOR_NO_ITEMS_SELECTED'),'warning');
		} else {
			// Get the model.
			$model = $this->getModel();

			// Make sure the item ids are integers
			$id = $cid[0];

			// Publish the items.
			if (!$model->setHome($id)) {
				Factory::getApplication()->enqueueMessage($model->getError(),'warning');
			} else {
				$this->setMessage(Text::_('COM_COLLECTOR_DEFAULT_FIELD_SET'));
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
		$app = Factory::getApplication();
		
		parent::publish();
		
		// Get some variables from the request
		$collection = $app->input->get( 'collection' );
		
		$this->setRedirect( 'index.php?option='.$this->option.'&view='.$this->view_list.'&collection='.$collection );
	}
	
	/**
     * Check in of one or more records.
     */
    public function checkin()
    {
        $app = Factory::getApplication();
		
		// Check for request forgeries.
        Session::checkToken() or jexit(Text::_('JINVALID_TOKEN'));
 
        // Initialise variables.
        $user		= Factory::getApplication()->getIdentity();
        $ids		= $app->input->get('cid', null, 'post', 'array');
		$collection = $app->input->get( 'collection' );
 
        $model = $this->getModel();
        $return = $model->checkin($ids);
        if ($return === false) {
            // Checkin failed.
            $message = Text::sprintf('JLIB_APPLICATION_ERROR_CHECKIN_FAILED', $model->getError());
            $this->setRedirect(Route::_('index.php?option='.$this->option.'&view='.$this->view_list.'&collection='.$collection, false), $message, 'error');
            return false;
        } else {
            // Checkin succeeded.
            $message =  Text::plural($this->text_prefix.'_N_ITEMS_CHECKED_IN', count($ids));
            $this->setRedirect(Route::_('index.php?option='.$this->option.'&view='.$this->view_list.'&collection='.$collection, false), $message);
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
	/* public function saveOrderAjax()
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
		Factory::getApplication()->close();
	}*/
}