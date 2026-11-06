<?php
/**
 * Joomla! 3.0 component Collector
 *
 * @version 	$Id: collectorgrid.php 146 2014-03-17 23:42:40Z steevo $
 * @author 		Philippe Ousset steevo@steevo.fr
 * * @package 	Collector
 * @copyright   Copyright (C) 2010 - 2020 Philippe Ousset. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 *
 * Collector is a Multi Purpose Listing Tool.
 * Originaly developped to list Collections
 * it can be used for several purpose.
 */

namespace Steevo\Component\Collector\Administrator\Helper;

defined('_JEXEC') or die;

use Joomla\CMS\HTML\Helpers\JGrid;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Language\Text;

/**
 * Utility class for creating HTML Grids
 *
 * @package     Collector
 */
class CollectorGrid extends JGrid
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
	public static function rebuild($i, $fulltitle, $prefix = '', $enabled = false, $checkbox = 'cb', $formId = null)
	{
		if (\is_array($prefix))
		{
			$options = $prefix;
			$enabled = \array_key_exists('enabled', $options) ? $options['enabled'] : $enabled;
			$checkbox = \array_key_exists('checkbox', $options) ? $options['checkbox'] : $checkbox;
			$prefix = \array_key_exists('prefix', $options) ? $options['prefix'] : '';
		}
		
		$text = $fulltitle;
		$active_title = HTMLHelper::_('tooltipText', Text::_('COM_COLLECTOR_REBUILD_FULLTITLE'), $text, 0);
		$inactive_title = HTMLHelper::_('tooltipText', Text::_('COM_COLLECTOR_FULLTITLE_REBUILT'), $text, 0);
		
		return static::action(
			$i,
			'rebuild',
			$prefix,
			// Text::_('COM_COLLECTOR_REBUILD'),
			// Text::_('COM_COLLECTOR_REBUILD'),
			html_entity_decode($active_title, ENT_QUOTES, 'UTF-8'),
			html_entity_decode($inactive_title, ENT_QUOTES, 'UTF-8'),
			true,
			'refresh',
			'refresh',
			$enabled,
			false,
			$checkbox,
            $formId
		);
	}
}
