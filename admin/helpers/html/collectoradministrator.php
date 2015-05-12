<?php
/**
 * * * @copyright	Copyright (C) 2005 - 2011 Philippe Ousset. All rights reserved.
 * @license		GNU General Public License version 2 or later; see LICENSE.txt
 */

// no direct access
defined('_JEXEC') or die;

/**
 * @package		Collector
 */
abstract class JHtmlCollectorAdministrator
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
			0	=> array('unfeatured',	'collections.home',		'COM_COLLECTOR_UNHOME',	'COM_COLLECTOR_TOGGLE_TO_HOME'),
			1	=> array('featured',	'collections.unhome',	'COM_COLLECTOR_HOME',	'COM_COLLECTOR_COLLECTION_HOME'),
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
