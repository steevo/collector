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

function CollectorBuildRoute(&$query)
{
	$segments = array();

	// get a menu item based on Itemid or currently active
	$app		= JFactory::getApplication();
	$menu		= $app->getMenu();
	// we need a menu item.  Either the one specified in the query, or the current active one if none specified
	if (empty($query['Itemid'])) {
		$menuItem = $menu->getActive();
		$menuItemGiven = false;
	} else {
		$menuItem = $menu->getItem($query['Itemid']);
		$menuItemGiven = true;
		// $query = $menuItem->query;
	}
	
	// if($menuItemGiven)
	// {
		// $queryMenu = $menuItem->query;
		// if(isset($queryMenu['reset']))
		// {
			// $reset = $queryMenu['reset'];
			// unset($queryMenu['reset']);
			// if ($reset == 0)
			// {
				// $segments[] = 'search';
			// } else {
				// $segments[] = 'nosearch';
			// }
		// }
	// }
	
	if (isset($query['view'])) {
		$view = $query['view'];
		$segments[] = $view;
		unset($query['view']);
	}
	else {
		// we need to have a view in the query or it is an invalid URL
		return $segments;
	}
	
	if ($view == 'collection')
	{
		if (isset($query['id'])){
			// Make sure we have the id and the alias
			if (strpos($query['id'], ':') === false) {
				$db = JFactory::getDbo();
				$aquery = $db->setQuery($db->getQuery(true)
					->select('alias')
					->from('#__collector')
					->where('id='.(int)$query['id'])
				);
				$alias = $db->loadResult();
				$query['id'] = $query['id'].':'.$alias;
			}
		} else {
			// we should have these two set for this view.  If we don't, it is an error
			return $segments;
		}
		
		$segments[] = $query['id'];
		
		unset($query['id']);
	}
	if ($view == 'item')
	{
		if (isset($query['collection']) && isset($query['id'])){
			// Make sure we have the id and the alias
			if (strpos($query['collection'], ':') === false) {
				$db = JFactory::getDbo();
				$aquery = $db->setQuery($db->getQuery(true)
					->select('alias')
					->from('#__collector')
					->where('id='.(int)$query['collection'])
				);
				$alias = $db->loadResult();
				$query['collection'] = $query['collection'].':'.$alias;
			}
			if (strpos($query['id'], ':') === false) {
				$db = JFactory::getDbo();
				$aquery = $db->setQuery($db->getQuery(true)
					->select('alias')
					->from('#__collector_items')
					->where('id='.(int)$query['id'])
				);
				$alias = $db->loadResult();
				$query['id'] = $query['id'].':'.$alias;
			}
		} else {
			// we should have these two set for this view.  If we don't, it is an error
			return $segments;
		}
		
		$segments[] = $query['collection'];
		$segments[] = $query['id'];
		
		unset($query['collection']);
		unset($query['id']);
	}
	if ($view == 'itemform')
	{
		if (isset($query['collection'])){
			// Make sure we have the id and the alias
			if (strpos($query['collection'], ':') === false) {
				$db = JFactory::getDbo();
				$aquery = $db->setQuery($db->getQuery(true)
					->select('alias')
					->from('#__collector')
					->where('id='.(int)$query['collection'])
				);
				$alias = $db->loadResult();
				$query['collection'] = $query['collection'].':'.$alias;
			}
		} else {
			// we should have these two set for this view.  If we don't, it is an error
			return $segments;
		}
		
		$segments[] = $query['collection'];
		
		unset($query['collection']);
	}

	if(isset($query['reset']))
	{
		$reset = $query['reset'];
		unset($query['reset']);
		if ($reset == 0)
		{
			$segments[] = 'search';
		} else {
			$segments[] = 'nosearch';
		}
	}
	
	return $segments;
}

function CollectorParseRoute($segments)
{
	$vars = array();

	$vars['option'] = 'com_collector';

	//Get the active menu item.
	$app	= JFactory::getApplication();
	$menu	= $app->getMenu();
	$item	= $menu->getActive();

	// Count route segments
	$count = count($segments);

	// if( $count == 1 )
	// {
		// if( $segments[0]=='nosearch' ){
			// $vars = $item->query;
			// $vars['reset'] = 1;
			// return $vars;
		// }
		// if( $segments[0]=='search' ){
			// $vars = $item->query;
			// $vars['reset'] = 0;
			// return $vars;
		// }
	// }
	
	//Standard routing for collection
	$vars['view']  = $segments[0];
	if ( $vars['view'] == 'collection' )
	{
		$vars['id'] = $segments[1];
	}
	else if ( $vars['view'] == 'item' )
	{
		$vars['collection'] = $segments[1];
		$vars['id'] = $segments[2];
		if ( isset($segments[3]) ){
			$vars['reset'] = $segments[3];
		}
	}
	else if ( $vars['view'] == 'itemform' )
	{
		$vars['layout'] = 'edit';
		$vars['collection'] = $segments[1];
	}

	return $vars;
}