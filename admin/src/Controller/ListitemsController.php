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
 * Definedcontent Controller
 *
 * @package  	Collector
 */
class ListitemsController extends AdminController
{
	/**
	 * Proxy for getModel.
	 * @since	1.6
	 */
	public function getModel($name = 'listitem', $prefix = 'Administrator', $config = array('ignore_request' => true)) 
	{
		$model = parent::getModel($name, $prefix, $config);
		return $model;
	}
	
	/**
	 * Removes an item.
	 *
	 * @return  void
	 */
	function delete()
	{
		$app = Factory::getApplication();
		
		parent::delete();
		
		$list = $app->input->get('defined');
		
		$this->setRedirect( 'index.php?option=com_collector&view=listitems&defined='.$list );
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
		$app = Factory::getApplication();
		
		// Check for request forgeries.
        Session::checkToken() or jexit(Text::_('JINVALID_TOKEN'));

		// Get the arrays from the Request
		$pks		= $app->input->get('cid',    null,    'post',    'array');
        $order		= $app->input->get('order',    null,    'post',    'array');
		
		// Get the model
		$model = $this->getModel('listitem');
		
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
	}*/

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
		$this->setRedirect(Route::_('index.php?option=com_collector&view=lists', false));
	}
}