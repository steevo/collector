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

defined('_JEXEC') or die;

/**
 * Collector Component Route Helper.
 */
abstract class CollectorHelperRoute
{
	protected static $lookup = array();

	/**
	 * Get the item route.
	 *
	 * @param   integer  $id        The route of the collector item.
	 * @param   integer  $colid     The collection ID.
	 * @param   integer  $language  The language code.
	 *
	 * @return  string  The item route.
	 *
	 * @since   1.5
	 */
	public static function getItemRoute($id, $colid = 0, $language = 0)
	{
		$needles = array(
			'item'  => array((int) $id)
		);

		// Create the link
		$link = 'index.php?option=com_collector&view=item&collection=' . $colid . '&id=' . $id;

		// if ($language && $language != "*" && JLanguageMultilang::isEnabled())
		// {
			// $link .= '&lang=' . $language;
			// $needles['language'] = $language;
		// }

		// if ($item = self::_findItem($needles))
		// {
			// $link .= '&Itemid=' . $item;
		// }

		return $link;
	}

	/**
	 * Get the category route.
	 *
	 * @param   integer  $colid     The category ID.
	 * @param   integer  $language  The language code.
	 *
	 * @return  string  The article route.
	 *
	 * @since   1.5
	 */
	public static function getCollectionRoute($colid, $language = 0)
	{
		$needles = array();

		$link = 'index.php?option=com_collector&view=collection&id=' . $colid;

		// if ($language && $language != "*" && JLanguageMultilang::isEnabled())
		// {
			// $link .= '&lang=' . $language;
			// $needles['language'] = $language;
		// }

		// if ($item = self::_findItem($needles))
		// {
			// $link .= '&Itemid=' . $item;
		// }

		return $link;
	}

	/**
	 * Get the form route.
	 *
	 * @param   integer  $id  The form ID.
	 *
	 * @return  string  The article route.
	 *
	 * @since   1.5
	 */
	public static function getFormRoute($id)
	{
		// Create the link
		if ($id)
		{
			$link = 'index.php?option=com_collector&task=item.edit&id=' . $id;
		}
		else
		{
			$link = 'index.php?option=com_collector&task=item.edit&a_id=0';
		}

		return $link;
	}

	/**
	 * Find an item ID.
	 *
	 * @param   array  $needles  An array of language codes.
	 *
	 * @return  mixed  The ID found or null otherwise.
	 *
	 * @since   1.5
	 */
	protected static function _findItem($needles = null)
	{
		$app      = JFactory::getApplication();
		$menus    = $app->getMenu('site');
		$language = isset($needles['language']) ? $needles['language'] : '*';

		// Prepare the reverse lookup array.
		if (!isset(self::$lookup[$language]))
		{
			self::$lookup[$language] = array();

			$component  = JComponentHelper::getComponent('com_collector');

			$attributes = array('component_id');
			$values     = array($component->id);

			if ($language != '*')
			{
				$attributes[] = 'language';
				$values[]     = array($needles['language'], '*');
			}

			$items = $menus->getItems($attributes, $values);

			foreach ($items as $item)
			{
				if (isset($item->query) && isset($item->query['view']))
				{
					$view = $item->query['view'];

					if (!isset(self::$lookup[$language][$view]))
					{
						self::$lookup[$language][$view] = array();
					}

					if (isset($item->query['id']))
					{
						/**
						 * Here it will become a bit tricky
						 * language != * can override existing entries
						 * language == * cannot override existing entries
						 */
						if (!isset(self::$lookup[$language][$view][$item->query['id']]) || $item->language != '*')
						{
							self::$lookup[$language][$view][$item->query['id']] = $item->id;
						}
					}
				}
			}
		}

		if ($needles)
		{
			foreach ($needles as $view => $ids)
			{
				if (isset(self::$lookup[$language][$view]))
				{
					foreach ($ids as $id)
					{
						if (isset(self::$lookup[$language][$view][(int) $id]))
						{
							return self::$lookup[$language][$view][(int) $id];
						}
					}
				}
			}
		}

		// Check if the active menuitem matches the requested language
		$active = $menus->getActive();

		if ($active
			&& $active->component == 'com_collector'
			&& ($language == '*' || in_array($active->language, array('*', $language)) || !JLanguageMultilang::isEnabled()))
		{
			return $active->id;
		}

		// If not found, return language specific home link
		$default = $menus->getDefault($language);

		return !empty($default->id) ? $default->id : null;
	}
}
