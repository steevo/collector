<?php
/**
 * Joomla! 3.0 component Collector
 *
 * @package 	Collector
 * @copyright   Copyright (C) 2010 - 2015 Philippe Ousset. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 *
 * Collector is a Multi Purpose Listing Tool.
 * Originaly developped to list Collections
 * it can be used for several purpose.
 */

// Check to ensure this file is included in Joomla!
defined('_JEXEC') or die( 'Restricted access' );

/**
 * Update Controller
 *
 * @package  	Collector
 */
class CollectorControllerCollection extends JControllerLegacy
{
	/**
	 * Method to display an html select of defined lists
	 * For AJAX request
	 *
	 * @access	public
	 */
	function copy()
	{
		$db = JFactory::getDBO();
		
		$app = JFactory::getApplication();
		$old_collection_id = $app->input->getVar('id');
		$new_name = $app->input->getVar('name');
		$mode = $app->input->getVar('mode');
		
		JTable::addIncludePath(JPATH_ADMINISTRATOR.'/components/com_collector/tables');
		$collection = & JTable::getInstance('Collector','Table');
		
		$response = $collection->copy($old_collection_id, $new_name, $mode);
		
		echo json_encode( $response );

	}
}