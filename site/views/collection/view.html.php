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

jimport('joomla.application.component.view');
jimport('joomla.mail.helper');
jimport('joomla.filesystem.file');

/**
 * HTML Collection View class for the Collector component
 *
 * @package	Collector
 */
class CollectorViewCollection extends JViewLegacy
{
	protected $state;
	protected $items;
	protected $pagination;
	
	/**
	 * Display function
	 */
	function display($tpl = null)
	{
		$app		= JFactory::getApplication();
		$params		= $app->getParams();

		$uri = JFactory::getURI();
		$uri->delVar('reset');
		
		// Get some data from the models
		$state		= $this->get('State');
		$collection	= $this->get('Collection');
		$fields		= $this->get('Fields');
		$userslists	= $this->get('Userslists');
		$usersitems	= $this->get('Usersitems');
		$items		= $this->get('Items');
		$pagination	= $this->get('Pagination');
		$searched	= $this->get('Searched');
		$pagination->setAdditionalUrlParam('reset',0);
		
		// Check for errors.
		if ($collection->id == 0) {
			$id = $app->input->getVar( 'id', '', 'default', 'int' );
			return JError::raiseError(404, JText::sprintf( 'COM_COLLECTOR_COLLECTION_NOT_FOUND', $id ));
		}

		// Check whether category access level allows access.
		$user	= JFactory::getUser();
		$groups	= $user->getAuthorisedViewLevels();
		if (!in_array($collection->access, $groups)) {
			return JError::raiseError(403, JText::_('COM_COLLECTOR_ALERTNOTAUTH'));
		}
		
		// Compute the collection slug.
		$collection->slug = $collection->alias ? ($collection->id . ':' . $collection->alias) : $collection->id;
		
		// Compute the item slugs.
		for ($i = 0, $n = count($items); $i < $n; $i++)
		{
			$item = &$items[$i];
			$item->slug = $item->alias ? ($item->id . ':' . $item->alias) : $item->id;
		}
		
		//Escape strings for HTML output
		$this->pageclass_sfx = htmlspecialchars($params->get('pageclass_sfx'));
		
		$this->params = $params;
		$this->state = $state;
		$this->collection = $collection;
		$this->fields = $fields;
		$this->userslists = $userslists;
		$this->usersitems = $usersitems;
		$this->items = $items;
		$this->pagination = $pagination;
		$this->searched = $searched;
		
		$this->_prepareDocument();
		
		parent::display($tpl);
	}
	
	/**
	 * Prepares the document
	 */
	protected function _prepareDocument()
	{
		$app		= JFactory::getApplication();
		$menus		= $app->getMenu();
		$pathway	= $app->getPathway();
		$title		= null;

		// Because the application sets a default page title,
		// we need to get it from the menu item itself
		$menu = $menus->getActive();

		if ($menu)
		{
			$this->params->def('page_heading', $this->params->get('page_title', $menu->title));
		}
		else
		{
			$this->params->def('page_heading', $this->collection->name);
		}

		$title = $this->params->get('page_title', '');

		$id = (int) @$menu->query['id'];
		
		// if the menu item does not concern this collection
		if ($menu && ($menu->query['option'] != 'com_collector' || $menu->query['view'] != 'collection' || $id != $this->collection->id))
		{
			$path = array(array('title' => $this->collection->name, 'link' => ''));

			$path = array_reverse($path);

			foreach ($path as $item)
			{
				$pathway->addItem($item['title'], $item['link']);
			}
			
			$this->params->set('page_heading', $this->collection->name);
		}

		// Check for empty title and add site name if param is set
		if (empty($title))
		{
			$title = $app->getCfg('sitename');
		}
		elseif ($app->getCfg('sitename_pagetitles', 0) == 1)
		{
			$title = JText::sprintf('JPAGETITLE', $app->getCfg('sitename'), $title);
		}
		elseif ($app->getCfg('sitename_pagetitles', 0) == 2)
		{
			$title = JText::sprintf('JPAGETITLE', $title, $app->getCfg('sitename'));
		}

		if (empty($title))
		{
			$title = $this->collection->name;
		}
		$this->document->setTitle($title);

		if ($this->collection->metadesc)
		{
			$this->document->setDescription($this->collection->metadesc);
		}
		elseif (!$this->collection->metadesc && $this->params->get('menu-meta_description'))
		{
			$this->document->setDescription($this->params->get('menu-meta_description'));
		}

		if ($this->collection->metakey)
		{
			$this->document->setMetadata('keywords', $this->collection->metakey);
		}
		elseif (!$this->collection->metakey && $this->params->get('menu-meta_keywords'))
		{
			$this->document->setMetadata('keywords', $this->params->get('menu-meta_keywords'));
		}

		if ($this->params->get('robots'))
		{
			$this->document->setMetadata('robots', $this->params->get('robots'));
		}

		if ($app->getCfg('MetaAuthor') == '1') {
			$this->document->setMetaData('author', $this->collection->author);
		}

		$mdata = $this->collection->metadata->toArray();

		foreach ($mdata as $k => $v)
		{
			if ($v)
			{
				$this->document->setMetadata($k, $v);
			}
		}
	}
}