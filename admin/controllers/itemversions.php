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

// Check to ensure this file is included in Joomla!
defined('_JEXEC') or die( 'Restricted access' );

/**
 * Itemversions Controller
 *
 * @package  	Collector
 */
class CollectorControllerItemversions extends JControllerAdmin
{
	/**
	 * Proxy for getModel.
	 * @since	1.6
	 */
	public function getModel($name = 'Itemversions', $prefix = 'CollectorModel') 
	{
		$model = parent::getModel($name, $prefix, array('ignore_request' => true));
		return $model;
	}
	
	/**
	 * Removes an item.
	 *
	 * @return  void
	 */
	function delete()
	{
		$app = JFactory::getApplication();
		
		$collection = $app->input->get('collection');
		$item = $app->input->get('item');
		
		// Check for request forgeries
		JSession::checkToken() or die(JText::_('JINVALID_TOKEN'));
		
		// Get items to remove from the request.
		$cid    = $app->input->get('cid', array(), '', 'array');
		
		if (!is_array($cid) || count($cid) < 1) {
			JFactory::getApplication()->enqueueMessage(JText::_($this->text_prefix.'_NO_ITEM_SELECTED'),'warning');
		} else {
			// Get the model.
			$model = $this->getModel();
			
			// Make sure the item ids are integers
			jimport('joomla.utilities.arrayhelper');
			JArrayHelper::toInteger($cid);
			
			// Remove the items.
			if ($model->delete($item,$cid)) {
				$this->setMessage(JText::plural($this->text_prefix.'_N_ITEMS_DELETED', count($cid)));
			} else {
				$this->setMessage($model->getError());
			}
		}
		
		$this->setRedirect(JRoute::_('index.php?option=com_collector&view=itemversions&collection='.$collection.'&item='.$item, false));
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
		$this->setRedirect(JRoute::_('index.php?option=com_collector&view=items', false));
	}
}