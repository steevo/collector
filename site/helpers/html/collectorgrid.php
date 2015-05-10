<?php
/**
 * Joomla! 3.0 component Collector
 *
 * @version 	$Id$
 * @author 		Philippe Ousset steevo@steevo.fr
 * * @package 	Collector
 * @copyright   Copyright (C) 2010 - 2015 Open Source Matters, Inc. All rights reserved.
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
abstract class JHtmlCollectorGrid
{
	/**
	 * Returns an action on a grid
	 *
	 * @param   integer       $i               The row index
	 * @param   string        $task            The task to fire
	 * @param   string|array  $prefix          An optional task prefix or an array of options
	 * @param   string        $text            An optional text to display [unused - @deprecated 4.0]
	 * @param   string        $active_title    An optional active tooltip to display if $enable is true
	 * @param   string        $inactive_title  An optional inactive tooltip to display if $enable is true
	 * @param   boolean       $tip             An optional setting for tooltip
	 * @param   string        $active_class    An optional active HTML class
	 * @param   string        $inactive_class  An optional inactive HTML class
	 * @param   boolean       $enabled         An optional setting for access control on the action.
	 * @param   boolean       $translate       An optional setting for translation.
	 *
	 * @return  string  The HTML markup
	 *
	 * @since   1.6
	 */
	public static function action($i, $task, $prefix = '', $text = '', $active_title = '', $inactive_title = '', $tip = false, $active_class = '',
		$inactive_class = '', $enabled = true, $translate = true)
	{
		if (is_array($prefix))
		{
			$options = $prefix;
			$active_title = array_key_exists('active_title', $options) ? $options['active_title'] : $active_title;
			$inactive_title = array_key_exists('inactive_title', $options) ? $options['inactive_title'] : $inactive_title;
			$tip = array_key_exists('tip', $options) ? $options['tip'] : $tip;
			$active_class = array_key_exists('active_class', $options) ? $options['active_class'] : $active_class;
			$inactive_class = array_key_exists('inactive_class', $options) ? $options['inactive_class'] : $inactive_class;
			$enabled = array_key_exists('enabled', $options) ? $options['enabled'] : $enabled;
			$translate = array_key_exists('translate', $options) ? $options['translate'] : $translate;
			$prefix = array_key_exists('prefix', $options) ? $options['prefix'] : '';
		}

		if ($tip)
		{
			JHtml::_('bootstrap.tooltip');

			$title = $enabled ? $active_title : $inactive_title;
			$title = $translate ? JText::_($title) : $title;
			$title = JHtml::tooltipText($title, '', 0);
		}

		if ($enabled)
		{
			$url = 'index.php?option=com_collector&task='.$prefix.$task.'&id=0&itemid='.$i;
			$html[] = '<a class="btn btn-micro' . ($active_class == 'add' ? ' active' : '') . ($tip ? ' hasTooltip' : '') . '"';
			$html[] = ' href="'.JRoute::_($url).'"';
			$html[] = $tip ? ' title="' . $title . '"' : '';
			$html[] = '>';
			$html[] = '<i class="icon-' . $active_class . '">';
			$html[] = '</i>';
			$html[] = '</a>';
		}
		else
		{
			$html[] = '<a class="btn btn-micro disabled jgrid' . ($tip ? ' hasTooltip' : '') . '"';
			$html[] = $tip ? ' title="' . $title . '"' : '';
			$html[] = '>';

			if ($active_class == "protected")
			{
				$html[] = '<i class="icon-lock"></i>';
			}
			else
			{
				$html[] = '<i class="icon-' . $inactive_class . '"></i>';
			}

			$html[] = '</a>';
		}

		return implode($html);
	}

	/**
	 * Returns a state on a grid
	 *
	 * @param   array         $states     array of value/state. Each state is an array of the form
	 *                                    (task, text, title,html active class, HTML inactive class)
	 *                                    or ('task'=>task, 'text'=>text, 'active_title'=>active title,
	 *                                    'inactive_title'=>inactive title, 'tip'=>boolean, 'active_class'=>html active class,
	 *                                    'inactive_class'=>html inactive class)
	 * @param   integer       $value      The state value.
	 * @param   integer       $i          The row index
	 * @param   string|array  $prefix     An optional task prefix or an array of options
	 * @param   boolean       $enabled    An optional setting for access control on the action.
	 * @param   boolean       $translate  An optional setting for translation.
	 *
	 * @return  string  The HTML markup
	 *
	 * @since   1.6
	 */
	public static function state($states, $value, $i, $prefix = '', $enabled = true, $translate = true)
	{
		if (is_array($prefix))
		{
			$options = $prefix;
			$enabled = array_key_exists('enabled', $options) ? $options['enabled'] : $enabled;
			$translate = array_key_exists('translate', $options) ? $options['translate'] : $translate;
			$prefix = array_key_exists('prefix', $options) ? $options['prefix'] : '';
		}

		$state = JArrayHelper::getValue($states, (int) $value, $states[0]);
		$task = array_key_exists('task', $state) ? $state['task'] : $state[0];
		$text = array_key_exists('text', $state) ? $state['text'] : (array_key_exists(1, $state) ? $state[1] : '');
		$active_title = array_key_exists('active_title', $state) ? $state['active_title'] : (array_key_exists(2, $state) ? $state[2] : '');
		$inactive_title = array_key_exists('inactive_title', $state) ? $state['inactive_title'] : (array_key_exists(3, $state) ? $state[3] : '');
		$tip = array_key_exists('tip', $state) ? $state['tip'] : (array_key_exists(4, $state) ? $state[4] : false);
		$active_class = array_key_exists('active_class', $state) ? $state['active_class'] : (array_key_exists(5, $state) ? $state[5] : '');
		$inactive_class = array_key_exists('inactive_class', $state) ? $state['inactive_class'] : (array_key_exists(6, $state) ? $state[6] : '');

		return static::action(
			$i, $task, $prefix, $text, $active_title, $inactive_title, $tip,
			$active_class, $inactive_class, $enabled, $translate
		);
	}

	/**
	 * Returns a published state on a grid
	 *
	 * @param   integer       $value         The state value.
	 * @param   integer       $i             The row index
	 * @param   string|array  $prefix        An optional task prefix or an array of options
	 * @param   boolean       $enabled       An optional setting for access control on the action.
	 *
	 * @return  string  The HTML markup
	 *
	 * @see     JHtmlPifaGrid::state()
	 */
	public static function edit($value, $i, $prefix = '', $enabled = true)
	{
		if (is_array($prefix))
		{
			$options = $prefix;
			$enabled = array_key_exists('enabled', $options) ? $options['enabled'] : $enabled;
			$prefix = array_key_exists('prefix', $options) ? $options['prefix'] : '';
		}

		$states = array(1 => array('delete', 'COM_COLLECTOR_ITEM_DONE', 'COM_COLLECTOR_DELETE_ITEM_DONE', 'COM_COLLECTOR_ITEM_DONE', true, 'ok', 'ok'),
			0 => array('add', 'COM_COLLECTOR_ITEM_TODO', 'COM_COLLECTOR_CREATE_ITEM_DONE', 'COM_COLLECTOR_ITEM_TODO', true, 'new', 'new'),
			2 => array('delete', 'COM_COLLECTOR_ITEM_PLANED', 'COM_COLLECTOR_DELETE_ITEM_PLANED', 'COM_COLLECTOR_ITEM_PLANED', true, 'clock', 'clock'));

		return static::state($states, $value, $i, $prefix, $enabled, true);
	}
}
