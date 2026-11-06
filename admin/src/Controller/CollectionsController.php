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
 * Collections list controller class
 *
 * @package  	Collector
 */
class CollectionsController extends AdminController
{
	/**
	 * Proxy for getModel.
	 * @since	1.6
	 */
	public function getModel($name = 'Collection', $prefix = 'Administrator', $config = array('ignore_request' => true)) 
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
		$app = Factory::getApplication();
		
		// Check for request forgeries
		Session::checkToken() or die(Text::_('JINVALID_TOKEN'));

		// Get items to publish from the request.
		$cid	= $app->input->get('cid', array(), '', 'array');
		
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
				$this->setMessage(Text::_('COM_COLLECTOR_DEFAULT_COLLECTION_SET'));
			}
		}

		$this->setRedirect(Route::_('index.php?option='.$this->option.'&view='.$this->view_list, false));
	}

	/**
	 * Method to return to Items view.
	 *
	 * @return  void
	 *
	 * @since   3.0
	 */
	public function back()
	{
		// Redirect to the list screen.
		$this->setRedirect(Route::_('index.php?option=com_collector&view=collections', false));
	}
}