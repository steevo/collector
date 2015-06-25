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
defined('_JEXEC') or die('Restricted access');

JFormHelper::loadFieldClass('list');
JHtml::_('behavior.multiselect');

/**
 * Form Field class for the Collector.
 *
 * @package		Collector
 */
class JFormFieldUserlisttype extends JFormFieldList
{
	protected $type 		= 'Userlisttype';
}
?>