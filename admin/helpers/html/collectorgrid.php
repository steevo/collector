<?php
/**
 * Joomla! 3.0 component Collector
 *
 * @version 	$Id: collectorgrid.php 146 2014-03-17 23:42:40Z steevo $
 * @author 		Philippe Ousset steevo@steevo.fr
 * * @package 	Collector
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
 * Utility class for creating HTML Grids
 *
 * @package     Collector
 */
abstract class JHtmlCollectorGrid extends JHtmlJGrid
{
	/**
	 * Returns a rebuild on a grid
	 *
	 * @param   integer       $value         The state value.
	 * @param   integer       $i             The row index
	 * @param   string|array  $prefix        An optional task prefix or an array of options
	 * @param   boolean       $enabled       An optional setting for access control on the action.
	 *
	 * @return  string  The HTML markup
	 */
	public static function rebuild($i, $fulltitle, $prefix = '', $enabled = false, $checkbox = 'cb')
	{
		JHtml::_('bootstrap.tooltip');

		if (is_array($prefix))
		{
			$options = $prefix;
			$enabled = array_key_exists('enabled', $options) ? $options['enabled'] : $enabled;
			$checkbox = array_key_exists('checkbox', $options) ? $options['checkbox'] : $checkbox;
			$prefix = array_key_exists('prefix', $options) ? $options['prefix'] : '';
		}
		
		$text = $fulltitle;
		$active_title = JHtml::tooltipText(JText::_('COM_COLLECTOR_REBUILD_FULLTITLE'), $text, 0);
		$inactive_title = JHtml::tooltipText(JText::_('COM_COLLECTOR_FULLTITLE_REBUILT'), $text, 0);
		
		return static::action(
			$i, 'rebuild', $prefix, JText::_('COM_COLLECTOR_REBUILD'), $active_title, $inactive_title, true, 'refresh',
			'refresh', $enabled, false, $checkbox
		);
	}
}
