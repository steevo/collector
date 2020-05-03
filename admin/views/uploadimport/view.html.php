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

jimport('joomla.application.component.view');

/**
 * HTML Itemversions View class for the Collector component
 *
 * @package	Collector
 */
class CollectorViewUploadimport extends JViewLegacy
{
	/**
	 * Display the view
	 *
	 * @return	void
	 */
	public function display($tpl = null)
	{		
		// Check for errors.
		if (count($errors = $this->get('Errors'))) {
			JFactory::getApplication()->enqueueMessage(implode('<br />', $errors),'error');
			return false;
		}
		
		parent::display($tpl);
	}
}
?>
