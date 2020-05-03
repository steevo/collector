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
class CollectorControllerImport extends JControllerAdmin
{
	/**
	 * Proxy for getModel.
	 * @since	1.6
	 */
	public function getModel($name = 'Import', $prefix = 'CollectorModel', $config = array('ignore_request' => true)) 
	{
		$model = parent::getModel($name, $prefix, $config);
		return $model;
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
		$collection	= $this->input->get('collection', '', '');
		
		// Redirect to the list screen.
		$this->setRedirect(JRoute::_('index.php?option=com_collector&view=items&collection='.$collection, false));
	}
}