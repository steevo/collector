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
use Joomla\CMS\Response\JsonResponse;
use Joomla\CMS\Router\Route;
use Joomla\CMS\Session\Session;
use Joomla\Utilities\ArrayHelper;
use Joomla\Database\DatabaseInterface;

/**
 * Userslists Controller
 *
 * @package  	Collector
 */
class UserslistsController extends AdminController
{
	/**
	 * Proxy for getModel.
	 * @since	1.6
	 */
	public function getModel($name = 'userslist', $prefix = 'Administrator', $config = array('ignore_request' => true))
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
		parent::delete();
		
		$collection = Factory::getApplication()->input->getCmd( 'collection' );
		
		$this->setRedirect( Route::_('index.php?option='.$this->option.'&view='.$this->view_list .'&collection='.$collection , false));
	}
	
	/**
     * Method to save the submitted ordering values for records.
     */
    public function saveorder()
    {
        // Check for request forgeries.
        Session::checkToken() or die(Text::_('JINVALID_TOKEN'));
 
        // Get the input
		$pks = $this->input->post->get('cid', array(), 'array');
		$order = $this->input->post->get('order', array(), 'array');
		$collection = $this->input->post->get( 'collection' );
		
        // Sanitize the input
        $pks = ArrayHelper::toInteger($pks);
        $order = ArrayHelper::toInteger($order);
 
        // Get the model
        $model = $this->getModel('userslists');
 
        // Save the ordering
        $return = $model->saveorder($pks, $order);
 
        if ($return === false)
        {
            // Reorder failed
            $message = Text::sprintf('JLIB_APPLICATION_ERROR_REORDER_FAILED', $model->getError());
            $this->setRedirect(Route::_('index.php?option='.$this->option.'&view='.$this->view_list.'&collection='.$collection, false), $message, 'error');
            return false;
        }
		else
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
		// Check for request forgeries.
        Session::checkToken() or die(Text::_('JINVALID_TOKEN'));

		$ids = $this->input->post->get('cid', [], 'array');
		$inc = ($this->getTask() == 'orderup') ? -1 : 1;
		$collection = $this->input->post->get( 'collection' );

        $model = $this->getModel();
        $return = $model->reorder($ids, $inc);

        if ($return === false)
		{
            // Reorder failed.
            $message = Text::sprintf('JLIB_APPLICATION_ERROR_REORDER_FAILED', $model->getError());
            $this->setRedirect(Route::_('index.php?option='.$this->option.'&view='.$this->view_list.'&collection='.$collection, false), $message, 'error');
            return false;
        }
		else
		{
            // Reorder succeeded.
            $message = Text::_('JLIB_APPLICATION_SUCCESS_ITEM_REORDERED');
            $this->setRedirect(Route::_('index.php?option='.$this->option.'&view='.$this->view_list.'&collection='.$collection, false), $message);
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
		parent::publish();
		
		$collection = Factory::getApplication()->input->get( 'collection' );
		
		$this->setRedirect( Route::_('index.php?option='.$this->option.'&view='.$this->view_list.'&collection='.$collection ,false));
	}
	
	/**
	 * Method to unpublish field
	 *
	 * @access	public
	 */
	public function unpublish()
	{
		parent::unpublish();
		
		$collection = Factory::getApplication()->input->get( 'collection' );
		
		$this->setRedirect( Route::_('index.php?option='.$this->option.'&view='.$this->view_list.'&collection='.$collection ,false));
	}
	
	public function setaccess(): void
	{
		Session::checkToken() or die(Text::_('JINVALID_TOKEN'));

		$id     = $this->input->getInt('id');
		$access = $this->input->getInt('access');
		$db     = Factory::getContainer()->get(DatabaseInterface::class);

		try {
			$db->setQuery(
				$db->getQuery(true)
				   ->update($db->quoteName('#__collector_userslists'))
				   ->set($db->quoteName('access') . ' = ' . $access)
				   ->where($db->quoteName('id')   . ' = ' . $id)
			)->execute();

			$app = Factory::getApplication();
			$app->setHeader('Content-Type', 'application/json; charset=utf-8', true);
			$app->sendHeaders();
			echo new JsonResponse(['success' => true]);
		} catch (\Exception $e) {
			$app = Factory::getApplication();
			$app->setHeader('Content-Type', 'application/json; charset=utf-8', true);
			$app->sendHeaders();
			echo new JsonResponse(null, $e->getMessage(), true);
		}

		Factory::getApplication()->close();
	}
}