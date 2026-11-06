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
 * Items Controller
 *
 * @package  	Collector
 */
class ItemsController extends AdminController
{
	/**
	 * Proxy for getModel.
	 * @since	1.6
	 */
	public function getModel($name = 'item', $prefix = 'Administrator', $config = array('ignore_request' => true)) 
	{
		$model = parent::getModel($name, $prefix, $config);
		return $model;
	}
	
	/**
	 * Method to save the submitted ordering values for records via AJAX.
	 *
	 * @return  void
	 *
	 * @since   3.0
	 */
	/*public function saveOrderAjax()
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
	
	/**
	 * Method to display item history
	 *
	 * @access	public
	 */
	function history()
	{
		$app = Factory::getApplication();
		
		// Get some variables from the request
		$collection = $app->input->get( 'collection' );
		$array = $app->input->get('cid', 0, '', 'array');
		$item = (int)$array[0];
		
		$this->setRedirect( 'index.php?option='.$this->option.'&view=itemversions&collection='.$collection.'&item='.$item );
	}
	
	/**
	 * Method to display item history
	 *
	 * @access	public
	 */
	function import()
	{
		$app = Factory::getApplication();
		
		// Get some variables from the request
		$collection = $app->input->get( 'collection' );
		
		$this->setRedirect( 'index.php?option='.$this->option.'&view=import&collection='.$collection );
	}
	
	/**
	 * Method to delete item
	 *
	 * @access	public
	 */
	function remove()
	{
		$app = Factory::getApplication();
		
		$option = $app->input->getCmd( 'option' );
		$view = $app->input->getCmd( 'view' );
		$collection = $app->input->getCmd( 'collection' );
		
		$model = $this->getModel('items');
		
		$msg = $model->remove();
		
		$this->setRedirect( 'index.php?option='.$option.'&view='.$view.'&collection='.$collection, $msg );
	}
	
	/**
	 * Method to rebuild fulltitle
	 *
	 * @return  boolean  True on success
	 */
	public function rebuild()
	{
		$app = Factory::getApplication();
		
		// Check for request forgeries.
		Session::checkToken() or jexit(Text::_('JINVALID_TOKEN'));

		$ids = $app->input->post->get('cid', array(), 'array');
		$collection = $app->input->get( 'collection' );

		$model = $this->getModel();
		$return = $model->rebuild($ids);
		
		if ($return === false)
		{
			// Rebuild failed.
			$message = Text::sprintf('COM_COLLECTOR_FULLTITLES_REBUILT_FAILED', $model->getError());
			$this->setRedirect(Route::_('index.php?option=' . $this->option . '&view=' . $this->view_list.'&collection='.$collection, false), $message);
			return false;
		}
		else
		{
			// Rebuild succeeded.
			$message = 'COM_COLLECTOR_N_FULLTITLES_REBUILT';
			$this->setMessage(Text::plural($message, count($ids)));
			$this->setRedirect(Route::_('index.php?option=' . $this->option . '&view=' . $this->view_list.'&collection='.$collection, false));
			return true;
		}
	}
	
	/**
	 * Method to publish item
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
	 * Method to unpublish item
	 *
	 * @access	public
	 */
	public function unpublish()
	{
		$app = Factory::getApplication();
		
		parent::unpublish();
		
		// Get some variables from the request
		$collection = $app->input->get( 'collection' );
		
		$this->setRedirect( 'index.php?option='.$this->option.'&view='.$this->view_list.'&collection='.$collection );
	}
	
	/**
	 * Method to delete item
	 *
	 * @access	public
	 */
	public function delete()
	{
		$app = Factory::getApplication();
		
		parent::delete();
		
		// Get some variables from the request
		$collection = $app->input->get( 'collection' );
		
		$this->setRedirect( 'index.php?option='.$this->option.'&view='.$this->view_list.'&collection='.$collection );
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
	 * Method to reset hits item
	 *
	 * @access	public
	 */
	function resethits()
	{
		$app = Factory::getApplication();
		
		// Check for request forgeries
		Session::checkToken() or jexit( 'Invalid Token' );
		
		// Initialize variables
		$option = $app->input->getCmd( 'option' );
		$controller = $app->input->getCmd( 'controller' );
		$collection = $app->input->getCmd( 'collection' );
		$id = $app->input->getCmd( 'id' );
		
		$model = $this->getModel('itemedit');
		
		if ($model->resetHits($id))
		{
			$msg = Text::_('COM_COLLECTOR_SUCCESSFULLY_RESET_HIT_COUNT');
		}
		else
		{
			$msg = Text::_('COM_COLLECTOR_ERROR_SAVE');
		}
		
		$this->setRedirect( 'index.php?option='.$option.'&controller='.$controller.'&collection='.$collection.'&task=edit&cid[]='.$id, $msg );
	}
}