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

// no direct access
defined('_JEXEC') or die;

/**
 * @package		Collector
 */
abstract class JHtmlCollectorTemplates
{
	/**
	 * @param	int $value	The state value
	 * @param	int $i
	 */
	static function home($value = 0, $i, $canChange = true)
	{
		JHtml::_('bootstrap.tooltip');
		
		// Array of image, task, title, action
		$states	= array(
			0	=> array('unfeatured',	'templates.home',	'COM_COLLECTOR_UNHOME',	'COM_COLLECTOR_TOGGLE_TO_HOME'),
			1	=> array('featured',	'templates.unhome',	'COM_COLLECTOR_HOME',	'COM_COLLECTOR_TEMPLATE_HOME'),
		);
		$state	= JArrayHelper::getValue($states, (int) $value, $states[1]);
		$icon	= $state[0];
		
		if ($canChange)
		{
			$html	= '<a href="#" onclick="return listItemTask(\'cb'.$i.'\',\''.$state[1].'\')" class="btn btn-micro hasTooltip' . ($value == 1 ? ' active' : '') . '" title="'.JHtml::tooltipText($state[3]).'"><i class="icon-'
					. $icon.'"></i></a>';
		}
		else
		{
			$html	= '<a class="btn btn-micro hasTooltip disabled' . ($value == 1 ? ' active' : '') . '" title="' . JHtml::tooltipText($state[2]) . '"><i class="icon-'
					. $icon . '"></i></a>';
		}

		return $html;
	}
}
