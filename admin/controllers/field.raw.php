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
 * Field Controller
 */
class CollectorControllerField extends JControllerLegacy
{
	/**
	 * Method to display an input hidden
	 * For AJAX request
	 *
	 * @access	public
	 */
	function params()
	{
		$model = $this->getModel('field');
		
		$form = $model->getForm();
		
		$params = '<li>'.$form->getLabel('unik').$form->getInput('unik').'</li>';
		$params .= '<li>'.$form->getLabel('edit').$form->getInput('edit').'</li>';
		$params .= '<li>'.$form->getLabel('listing').$form->getInput('listing').'</li>';
		$params .= '<li>'.$form->getLabel('filter').$form->getInput('filter').'</li>';
		$params .= '<li>'.$form->getLabel('sort').$form->getInput('sort').'</li>';
		
		echo $params;
		return;
	}
}