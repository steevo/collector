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

namespace Steevo\Component\Collector\Site\View\Item;

// Check to ensure this file is included in Joomla!
defined('_JEXEC') or die( 'Restricted access' );

use Joomla\CMS\Factory;
use Joomla\CMS\Helper\ContentHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\View\GenericDataException;
use Joomla\CMS\MVC\View\HtmlView as BaseHtmlView;
use Joomla\CMS\Toolbar\Toolbar;
use Joomla\CMS\Toolbar\ToolbarHelper;
use Joomla\CMS\Router\Route;
use Joomla\CMS\Uri\Uri;
use Steevo\Component\Collector\Site\Model\UseritemModel;

// JTable::addIncludePath(JPATH_ADMINISTRATOR.'/components/com_collector/tables');

/**
 * HTML Item View class for the Collector component
 *
 * @package	Collector
 */
class HtmlView extends BaseHtmlView
{
	protected $collection;
	protected $fields;
	protected $item;
	protected $params;
	protected $state;
	protected $form;
	protected $user;
	protected $navigation;
	
	/**
	 * Display function
	 */
	public function display($tpl = null)
	{
		// Initialise variables.
		$app		= Factory::getApplication();
		$user		= Factory::getApplication()->getIdentity();
		$userId		= $user->get('id');
		
		$this->params		= $app->getParams();
		
		// get the Data
		$this->collection	= $this->get('Collection');
		$this->fields		= $this->get('Fields');
		$this->item			= $this->get('Item');
		$this->state		= $this->get('State');
		$this->form			= $this->get('Form');
		if ( $this->params->get('navigation',1) ) {
			$this->navigation	= $this->get('Navigation');
		}
		$this->user			= $user;

		$this->userlistsStatus  = [];
		$this->userlistWant     = [];
		$this->userlistExchange = [];

		if ($user->id && $this->item) {
			$useritemModel = new UseritemModel();
			$useritemModel->setState('user.id', $user->id);
			$this->userlistsStatus = $useritemModel->getItemStatus(
				(int) $this->item->collection,
				(int) $this->item->id
			);
			$this->userlistWant     = $useritemModel->getUsersWithItem(
				(int) $this->item->id, 
				(int) $this->collection->id, 
				0  // type 0 = cherchent
			);
			$this->userlistExchange = $useritemModel->getUsersWithItem(
				(int) $this->item->id,
				(int) $this->collection->id,
				1  // type 1 = échangent
			);
		}
		
		if (($this->collection->id == 0))
		{
			$id = $app->input->get( 'id', '', 'default', 'int' );
			return Factory::getApplication()->enqueueMessage(Text::sprintf( 'COM_COLLECTOR_COLLECTION_NOT_FOUND', $id ),'error');
		}

		// Compute the collection slug.
		$this->collection->slug = $this->collection->alias ? ($this->collection->id . ':' . $this->collection->alias) : $this->collection->id;

		// Increment the hit counter of the article.
		$model = $this->getModel();
		$model->hit();
		
		// Check the view access to the article (the model has already computed the values).
		if ($this->item->params->get('access-view') != true)
		{
			Factory::getApplication()->enqueueMessage(Text::_('JERROR_ALERTNOAUTHOR'),'warning');
			return;
		}
		
		//Escape strings for HTML output
		$this->pageclass_sfx = htmlspecialchars($this->params->get('pageclass_sfx', ''));
		
		$this->_prepareDocument();
		
		parent::display($tpl);
	}

	/**
	 * Prepares the document
	 */
	protected function _prepareDocument()
	{
		$app	= Factory::getApplication();
		$menus	= $app->getMenu();
		$pathway = $app->getPathway();
		$title = null;

		// Because the application sets a default page title,
		// we need to get it from the menu item itself
		$menu = $menus->getActive();
		
		if ($menu)
		{
			$this->params->def('page_heading', $this->params->get('page_title', $menu->title));
		}
		else
		{
			$this->params->def('page_heading', $this->item->titleItem);
		}

		$title = $this->params->get('page_title', '');

		$id = (int) @$menu->query['id'];

		// if the menu item does not concern this article
		if ($menu && ($menu->query['option'] != 'com_collector' || $menu->query['view'] != 'item' || $id != $this->item->id))
		{
			// If this is not a single article menu item, set the page title to the article title
			if ($this->item->titleItem)
			{
				$title = $this->item->titleItem;
			}
			$path = array(array('title' => $this->item->titleItem, 'link' => ''));
			$collection = $this->item->collection;
			
			if ($menu && ($menu->query['option'] != 'com_collector' || $menu->query['view'] != 'collection' || $id != $this->collection->id))
			{
				$path[] = array('title' => $this->collection->name, 'link' => Route::_('index.php?option=com_collector&view=collection&id='.$this->collection->id));
			}
			
			$path = array_reverse($path);
			
			foreach($path as $item)
			{
				$pathway->addItem($item['title'], $item['link']);
			}
		}

		// Check for empty title and add site name if param is set
		if (empty($title))
		{
			$title = $app->getCfg('sitename');
		}
		elseif ($app->getCfg('sitename_pagetitles', 0) == 1)
		{
			$title = Text::sprintf('JPAGETITLE', $app->getCfg('sitename'), $title);
		}
		elseif ($app->getCfg('sitename_pagetitles', 0) == 2)
		{
			$title = Text::sprintf('JPAGETITLE', $title, $app->getCfg('sitename'));
		}
		
		if (empty($title))
		{
			$title = $this->item->titleItem;
		}
		$this->document->setTitle($title);

		if ($this->item->metadesc)
		{
			$this->document->setDescription($this->item->metadesc);
		}
		elseif (!$this->item->metadesc && $this->params->get('menu-meta_description'))
		{
			$this->document->setDescription($this->params->get('menu-meta_description'));
		}

		if ($this->item->metakey)
		{
			$this->document->setMetadata('keywords', $this->item->metakey);
		}
		elseif (!$this->item->metakey && $this->params->get('menu-meta_keywords'))
		{
			$this->document->setMetadata('keywords', $this->params->get('menu-meta_keywords'));
		}

		if ($this->params->get('robots'))
		{
			$this->document->setMetadata('robots', $this->params->get('robots'));
		}

		if ($app->getCfg('MetaAuthor') == '1')
		{
			$this->document->setMetaData('author', $this->item->author);
		}

		$mdata = $this->item->metadata->toArray();
		
		foreach ($mdata as $k => $v)
		{
			if ($v)
			{
				$this->document->setMetadata($k, $v);
			}
		}
	}
}