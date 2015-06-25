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

// No direct access
defined('_JEXEC') or die;

/**
 * Collector component helper.
 *
 * @package		Collector
 */
class CollectorHelper
{
	public static $extension = 'com_collector';

	/**
	 * Configure the Linkbar.
	 *
	 * @param	string	$vName	The name of the active view.
	 *
	 * @return	void
	 */
	public static function addSubmenu($vName)
	{
		JHtmlSidebar::addEntry(
			JText::_('COM_COLLECTOR_HOME'),
			'index.php?option=com_collector',
			$vName == 'collectors'
		);
		JHtmlSidebar::addEntry(
			JText::_('COM_COLLECTOR_FILE_MANAGER'),
			'index.php?option=com_collector&view=filemanager',
			$vName == 'filemanager'
		);
		JHtmlSidebar::addEntry(
			JText::_('COM_COLLECTOR_COLLECTIONS'),
			'index.php?option=com_collector&view=collections',
			$vName == 'collections'
		);
		JHtmlSidebar::addEntry(
			JText::_('COM_COLLECTOR_LISTS'),
			'index.php?option=com_collector&view=lists',
			($vName == 'lists' || $vName == 'listitems')
		);
		JHtmlSidebar::addEntry(
			JText::_('COM_COLLECTOR_FIELDS'),
			'index.php?option=com_collector&view=fields',
			$vName == 'fields'
		);
		JHtmlSidebar::addEntry(
			JText::_('COM_COLLECTOR_ITEMS'),
			'index.php?option=com_collector&view=items',
			($vName == 'items' || $vName == 'itemversions')
		);
		JHtmlSidebar::addEntry(
			JText::_('COM_COLLECTOR_USERSLISTS'),
			'index.php?option=com_collector&view=userslists',
			$vName == 'userslists'
		);
		// JHtmlSidebar::addEntry(
			// JText::_('COM_COLLECTOR_TEMPLATES'),
			// 'index.php?option=com_collector&view=templates',
			// $vName == 'templates'
		// );
	}

	/**
	 * Gets a list of the actions that can be performed.
	 *
	 * @param	int		The collection ID.
	 * @param	int		The field ID.
	 * @param	int		The item ID.
	 *
	 * @return	JObject
	 */
	public static function getActions($collectionId = 0, $fieldId = 0, $itemId = 0)
	{
		jimport('joomla.access.access');
		$user	= JFactory::getUser();
		$result	= new JObject;

		if (empty($itemId) && empty($fieldId) && empty($collectionId)) {
			$assetName = 'com_collector';
		}
		else if (empty($itemId) && empty($fieldId)) {
			$assetName = 'com_collector.collection.'.(int) $collectionId;
		}
		else if (empty($itemId) && empty($collectionId)) {
			$assetName = 'com_collector.field.'.(int) $fieldId;
		}
		else {
			$assetName = 'com_collector.item.'.(int) $itemId;
		}

		$actions = JAccess::getActions('com_collector', 'component');

		foreach ($actions as $action) {
			$result->set($action->name, $user->authorise($action->name, $assetName));
		}

		return $result;
	}

	/**
	* Applies the content tag filters to arbitrary text as per settings for current user group
	* @param text The string to filter
	* @return string The filtered string
	*/
	public static function filterText($text)
	{
		// Filter settings
		jimport('joomla.application.component.helper');
		$config		= JComponentHelper::getParams('com_content');
		$user		= JFactory::getUser();
		$userGroups	= JAccess::getGroupsByUser($user->get('id'));

		$filters = $config->get('filters');

		$blackListTags			= array();
		$blackListAttributes	= array();

		$whiteListTags			= array();
		$whiteListAttributes	= array();

		$noHtml		= false;
		$whiteList	= false;
		$blackList	= false;
		$unfiltered	= false;

		// Cycle through each of the user groups the user is in.
		// Remember they are include in the Public group as well.
		foreach ($userGroups AS $groupId)
		{
			// May have added a group by not saved the filters.
			if (!isset($filters->$groupId)) {
				continue;
			}

			// Each group the user is in could have different filtering properties.
			$filterData = $filters->$groupId;
			$filterType	= strtoupper($filterData->filter_type);

			if ($filterType == 'NH') {
				// Maximum HTML filtering.
				$noHtml = true;
			}
			else if ($filterType == 'NONE') {
				// No HTML filtering.
				$unfiltered = true;
			}
			else {
				// Black or white list.
				// Preprocess the tags and attributes.
				$tags			= explode(',', $filterData->filter_tags);
				$attributes		= explode(',', $filterData->filter_attributes);
				$tempTags		= array();
				$tempAttributes	= array();

				foreach ($tags AS $tag)
				{
					$tag = trim($tag);

					if ($tag) {
						$tempTags[] = $tag;
					}
				}

				foreach ($attributes AS $attribute)
				{
					$attribute = trim($attribute);

					if ($attribute) {
						$tempAttributes[] = $attribute;
					}
				}

				// Collect the black or white list tags and attributes.
				// Each list is cummulative.
				if ($filterType == 'BL') {
					$blackList				= true;
					$blackListTags			= array_merge($blackListTags, $tempTags);
					$blackListAttributes	= array_merge($blackListAttributes, $tempAttributes);
				}
				else if ($filterType == 'WL') {
					$whiteList				= true;
					$whiteListTags			= array_merge($whiteListTags, $tempTags);
					$whiteListAttributes	= array_merge($whiteListAttributes, $tempAttributes);
				}
			}
		}

		// Remove duplicates before processing (because the black list uses both sets of arrays).
		$blackListTags			= array_unique($blackListTags);
		$blackListAttributes	= array_unique($blackListAttributes);
		$whiteListTags			= array_unique($whiteListTags);
		$whiteListAttributes	= array_unique($whiteListAttributes);

		// Unfiltered assumes first priority.
		if ($unfiltered) {
			// Dont apply filtering.
		}
		else {
			// Black lists take second precedence.
			if ($blackList) {
				// Remove the white-listed attributes from the black-list.
				$filter = JFilterInput::getInstance(
					array_diff($blackListTags, $whiteListTags), 			// blacklisted tags
					array_diff($blackListAttributes, $whiteListAttributes), // blacklisted attributes
					1,														// blacklist tags
					1														// blacklist attributes
				);
			}
			// White lists take third precedence.
			else if ($whiteList) {
				$filter	= JFilterInput::getInstance($whiteListTags, $whiteListAttributes, 0, 0, 0);  // turn off xss auto clean
			}
			// No HTML takes last place.
			else {
				$filter = JFilterInput::getInstance();
			}
			
			$text = $filter->clean($text, 'html');
		}

		return $text;
	}
}