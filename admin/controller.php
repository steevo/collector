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
 * Collector Controller
 *
 * @package  	Collector
 */
class CollectorController extends JControllerLegacy
{
	/**
	 * @var		string	The default view.
	 * @since	1.6
	 */
	protected $default_view = 'collectors';

	/**
	 * Method to display a view
	 *
	 * @access	public
	 */
	function display($cachable = false, $urlparams = false)
	{
		require_once JPATH_COMPONENT.'/helpers/collector.php';
		
		// call parent behavior
		parent::display($cachable);
	}
}