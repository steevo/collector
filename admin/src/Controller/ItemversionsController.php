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
 * Itemversions Controller
 *
 * @package  	Collector
 */
class ItemversionsController extends AdminController
{
	/**
	 * Proxy for getModel.
	 * @since	1.6
	 */
	public function getModel($name = 'itemversions', $prefix = 'Administrator', $config = array('ignore_request' => true)) 
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
		
		$collection = $app->input->get('collection');
		$item = $app->input->get('item');
		
		// Check for request forgeries
		Session::checkToken() or die(Text::_('JINVALID_TOKEN'));
		
		// Get items to remove from the request.
		$cid    = $app->input->get('cid', array(), '', 'array');
		
		if (!is_array($cid) || count($cid) < 1) {
			Factory::getApplication()->enqueueMessage(Text::_($this->text_prefix.'_NO_ITEM_SELECTED'),'warning');
		} else {
			// Get the model.
			$model = $this->getModel();
			
			// Make sure the item ids are integers
			// jimport('joomla.utilities.arrayhelper');
			$cid = ArrayHelper::toInteger($cid);
			
			// Remove the items.
			if ($model->delete($item,$cid)) {
				$this->setMessage(Text::plural($this->text_prefix.'_N_ITEMS_DELETED', count($cid)));
			} else {
				$this->setMessage($model->getError());
			}
		}
		
		$this->setRedirect(Route::_('index.php?option=com_collector&view=itemversions&collection='.$collection.'&item='.$item, false));
	}
	
	/**
	 * Valide une version en attente (state=0 → state=1).
	 * Contrainte : doit être la plus ancienne version en attente.
	 */
	public function validate(): void
	{
		$app        = Factory::getApplication();
		$collection = $app->input->getInt('collection');
		$item       = $app->input->getInt('item');

		if (!Session::checkToken() && !Session::checkToken('get')) {
			die(Text::_('JINVALID_TOKEN'));
		}

		$cid = $app->input->get('cid', [], 'ARRAY');
		$cid = ArrayHelper::toInteger($cid);

		if (count($cid) !== 1) {
			$app->enqueueMessage(Text::_('COM_COLLECTOR_ITEM_HISTORY_VALIDATE_ONLY_ONE'), 'warning');
			$this->setRedirect(Route::_('index.php?option=com_collector&view=itemversions&collection=' . $collection . '&item=' . $item, false));
			return;
		}

		$model = $this->getModel();

		if ($model->validate($item, $cid[0])) {
			$app->enqueueMessage(Text::_('COM_COLLECTOR_ITEM_HISTORY_VALIDATED'), 'success');
		} else {
			$app->enqueueMessage($model->getError(), 'error');
		}

		$this->setRedirect(Route::_('index.php?option=com_collector&view=itemversions&collection=' . $collection . '&item=' . $item, false));
	}

	/**
	 * Refuse une version en attente (state=0 → state=2).
	 */
	public function refuse(): void
	{
		if (!Session::checkToken() && !Session::checkToken('get')) {
			die(Text::_('JINVALID_TOKEN'));
		}

		$app        = Factory::getApplication();
		
		$collection = $app->input->getInt('collection');
		$item       = $app->input->getInt('item');
		$versionId = $app->input->getInt('version_id');
		$refuseNote = $app->input->getString('refuse_note', '');

		$model = $this->getModel();
		$model->setState('filter.collection', $collection);
		$model->setState('filter.item', $item);

		if ($model->refuse($item, $versionId, $refuseNote)) {
			$app->enqueueMessage(Text::_('COM_COLLECTOR_ITEM_HISTORY_REFUSED'), 'success');
		} else {
			$app->enqueueMessage($model->getError(), 'error');
		}

		$this->setRedirect(Route::_('index.php?option=com_collector&view=itemversions&collection=' . $collection . '&item=' . $item, false));
	}

	/**
	 * Restore an item.
	 *
	 * @return  void
	 */
	public function restore(): void
	{
		$app        = Factory::getApplication();
		$collection = $app->input->getInt('collection');
		$item       = $app->input->getInt('item');

		if (!Session::checkToken() && !Session::checkToken('get')) {
			die(Text::_('JINVALID_TOKEN'));
		}

		$cid = $app->input->get('cid', [], 'ARRAY');
		$cid = ArrayHelper::toInteger($cid);

		if (count($cid) !== 1) {
			$app->enqueueMessage(Text::_('COM_COLLECTOR_ITEM_HISTORY_RESTORE_ONLY_ONE_ITEM'), 'warning');
			$this->setRedirect(Route::_('index.php?option=com_collector&view=itemversions&collection=' . $collection . '&item=' . $item, false));
			return;
		}

		$model = $this->getModel();

		if ($model->restore($item, $cid)) {
			$app->enqueueMessage(Text::_('COM_COLLECTOR_ITEM_HISTORY_RESTORED'), 'success');
		} else {
			$app->enqueueMessage($model->getError(), 'error');
		}

		$this->setRedirect(Route::_('index.php?option=com_collector&view=itemversions&collection=' . $collection . '&item=' . $item, false));
	}
	
	/**
	* Gets the URL arguments to append to a list redirect.
	*
	* @return    string    The arguments to append to the redirect URL.
	*/
	protected function getRedirectToListAppend()
	{
		$append = parent::getRedirectToListAppend();
		
		$form = $this->app->input->get('jform');
		
		$append .= '&collection='.$form['collection'];
		
		return $append;
	}
}