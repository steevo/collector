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
 * HTML utility class for building a dropdown menu
 *
 * @package     Collector
 */
abstract class JHtmlCollectorDropdown
{
	/**
	 * @var    string  HTML markup for the dropdown list
	 * @since  3.2
	 */
	protected static $dropDownList = array();

	/**
	 * Method to render current dropdown menu
	 *
	 * @param   string  $item  An item to render.
	 *
	 * @return  string  HTML markup for the dropdown list
	 *
	 * @since   3.2
	 */
	public static function render($item = '')
	{
		$html = array();

		$html[] = '<button data-toggle="dropdown" class="dropdown-toggle btn btn-micro">';
		$html[] = '<span class="caret"></span>';

		if ($item)
		{
			$html[] = '<span class="element-invisible">' . JText::sprintf('JACTIONS', $item) . '</span>';
		}

		$html[] = '</button>';
		$html[] = '<ul class="dropdown-menu" style="left:initial;right:0;" >';
		$html[] = implode('', static::$dropDownList);
		$html[] = '</ul></div>';

		static::$dropDownList = null;

		return implode('', $html);
	}

	/**
	 * Append an add item to the current dropdown menu
	 *
	 * @param   string  $id      ID of corresponding checkbox of the record
	 * @param   string  $prefix  The task prefix
	 *
	 * @return  void
	 *
	 * @since   3.2
	 */
	public static function add($collection, $id, $userlist, $prefix = '')
	{
		$task = ($prefix ? $prefix . '.' : '') . 'add';
		static::addCustomItem(JText::sprintf('COM_COLLECTOR_DROPDOWN_ADD_ITEM', $userlist->name), 'new', $collection, $id, $userlist->id, $task);
	}

	/**
	 * Append an delete item to the current dropdown menu
	 *
	 * @param   string  $id      ID of corresponding checkbox of the record
	 * @param   string  $prefix  The task prefix
	 *
	 * @return  void
	 *
	 * @since   3.2
	 */
	public static function remove($collection, $id, $userlist, $prefix = '')
	{
		$task = ($prefix ? $prefix . '.' : '') . 'remove';
		static::addCustomItem(JText::sprintf('COM_COLLECTOR_DROPDOWN_REMOVE_ITEM', $userlist->name), 'delete', $collection, $id, $userlist->id, $task);
	}

	/**
	 * Append a custom item to current dropdown menu.
	 *
	 * @param   string  $label  The label of the item.
	 * @param   string  $icon   The icon classname.
	 * @param   string  $id     The item id.
	 * @param   string  $task   The task.
	 *
	 * @return  void
	 *
	 * @since   3.2
	 */
	public static function addCustomItem($label, $icon = '', $collection = '', $id = '', $userlist = '', $task = '')
	{
		if ( $task == "remove" ) {
			$view = "removeuseritem";
		} else {
			$view = "validuseritem";
		}
		$url = 'index.php?option=com_collector&view='.$view.'&tmpl=component&collection='.$collection.'&item='.$id.'&userlist='.$userlist;
		static::$dropDownList[] = '<li>'
			. '<a class="modal" href = "'.JRoute::_($url).'">'
			. ($icon ? '<span class="collector-icon icon-' . $icon . '"></span> ' : '')
			. $label
			. '</a>'
			. '</li>';
	}
}
