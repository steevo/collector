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
	 * Ouvre l'écran de fusion avec la ou les deux valeurs cochées.
	 * Avec deux valeurs, la plus utilisée est gardée (cible).
	 *
	 * @return  void
	 */
	public function merge()
	{
		$this->checkToken();

		$defined = $this->input->getInt('defined', 0);
		$cid     = array_values(array_filter(array_map('intval', (array) $this->input->get('cid', [], 'array'))));
		$url     = 'index.php?option=com_collector&view=listmerge&defined=' . $defined;

		if (count($cid) > 2) {
			$this->setRedirect(Route::_('index.php?option=com_collector&view=listitems&defined=' . $defined, false), Text::_('COM_COLLECTOR_LISTMERGE_SELECT_ONE_OR_TWO'), 'warning');
			return;
		}

		if (count($cid) === 2) {
			$usage = $this->getModel('Listmerge', 'Administrator')->getUsage($defined, $cid);
			[$source, $target] = $cid;

			if (($usage[$source]['items'] ?? 0) > ($usage[$target]['items'] ?? 0)) {
				[$source, $target] = [$target, $source];
			}

			$url .= '&source=' . $source . '&target=' . $target;
		} elseif ($cid) {
			$url .= '&source=' . $cid[0];
		}

		$this->setRedirect(Route::_($url, false));
	}

	/**
	 * Fusionne la valeur source dans la valeur cible.
	 *
	 * @return  void
	 */
	public function domerge()
	{
		$this->checkToken();

		$defined = $this->input->getInt('defined', 0);
		$source  = $this->input->getInt('source', 0);
		$target  = $this->input->getInt('target', 0);
		$user    = $this->app->getIdentity();
		$back    = 'index.php?option=com_collector&view=listmerge&defined=' . $defined . '&source=' . $source . '&target=' . $target;

		if (!$user->authorise('core.edit', 'com_collector') || !$user->authorise('core.delete', 'com_collector')) {
			$this->setRedirect(Route::_($back, false), Text::_('JLIB_APPLICATION_ERROR_EDIT_NOT_PERMITTED'), 'error');
			return;
		}

		try {
			$stats = $this->getModel('Listmerge', 'Administrator')->merge($defined, $source, $target);
		} catch (\RuntimeException $e) {
			$this->setRedirect(Route::_($back, false), Text::sprintf('COM_COLLECTOR_LISTMERGE_ERROR', $e->getMessage()), 'error');
			return;
		}

		$message = Text::sprintf('COM_COLLECTOR_LISTMERGE_DONE', $stats['items'], $stats['versions'], $stats['titles']);

		if ($stats['values'] > 1 || $stats['children']) {
			$message .= ' ' . Text::sprintf('COM_COLLECTOR_LISTMERGE_DONE_TREE', $stats['values'] - 1, $stats['children']);
		}

		if ($stats['import'] || $stats['sessions'] || $stats['menus']) {
			$message .= ' ' . Text::sprintf('COM_COLLECTOR_LISTMERGE_DONE_OTHER', $stats['import'], $stats['sessions'], $stats['menus']);
		}

		$this->setRedirect(Route::_('index.php?option=com_collector&view=listitems&defined=' . $defined, false), $message);
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
		$this->setRedirect(Route::_('index.php?option=com_collector&view=lists', false));
	}
}