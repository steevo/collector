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

namespace Steevo\Component\Collector\Site\View\Collection;

// Check to ensure this file is included in Joomla!
defined('_JEXEC') or die( 'Restricted access' );

use Joomla\CMS\Factory;
use Joomla\CMS\Helper\ContentHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\View\GenericDataException;
use Joomla\CMS\MVC\View\HtmlView as BaseHtmlView;
use Joomla\CMS\Toolbar\Toolbar;
use Joomla\CMS\Toolbar\ToolbarHelper;
use Joomla\CMS\Uri\Uri;
use Steevo\Component\Collector\Administrator\Helper\CollectorTemplateHelper;

/**
 * HTML Collection View class for the Collector component
 *
 * @package	Collector
 */
class HtmlView extends BaseHtmlView
{
	/**
     * The model state
     *
     * @var  \Joomla\CMS\Object\CMSObject
     */
    protected $state = null;

    /**
     * The items array
     *
     * @var  \stdClass[]
     */
    protected $items = null;

    /**
     * The pagination object.
     *
     * @var  \Joomla\CMS\Pagination\Pagination
     */
    protected $pagination = null;

    /**
     * The page parameters
     *
     * @var    \Joomla\Registry\Registry|null
     *
     * @since  4.0.0
     */
    protected $params = null;

    /**
     * The user object
     *
     * @var   \Joomla\CMS\User\User|null
     */
    protected $user = null;

    /**
     * The page class suffix
     *
     * @var    string
     *
     * @since  4.0.0
     */
    protected $pageclass_sfx = '';

    /**
     * Template d'affichage choisi pour la liste (null : mise en page standard)
     *
     * @var  object|null
     */
    protected $listTemplate = null;

    /**
     * Vues proposées au visiteur (templates de liste publiés) ; vide si le sélecteur est désactivé
     *
     * @var  object[]
     */
    protected $listViews = [];

    /**
     * Vue de liste affichée : id du template, 0 pour le tableau standard
     *
     * @var  int
     */
    protected $listView = 0;

	/**
	 * Display function
	 */
	function display($tpl = null)
	{
		$app		= Factory::getApplication();
		$params		= $app->getParams();

		$uri = Uri::getInstance();
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
			$id = $app->input->get( 'id', '', 'default', 'int' );
			return Factory::getApplication()->enqueueMessage(Text::sprintf( 'COM_COLLECTOR_COLLECTION_NOT_FOUND', $id ),'error');
		}

		// Check whether category access level allows access.
		$user	= Factory::getApplication()->getIdentity();
		$groups	= $user->getAuthorisedViewLevels();
		if (!in_array($collection->access, $groups)) {
			return Factory::getApplication()->enqueueMessage(Text::_('COM_COLLECTOR_ALERTNOTAUTH'),'error');
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
		$this->pageclass_sfx = htmlspecialchars($params->get('pageclass_sfx', ''));
		
		$this->params = $params;
		$this->state = $state;
		$this->collection = $collection;
		$this->fields = $fields;
		$this->userslists = $userslists;
		$this->usersitems = $usersitems;
		$this->items = $items;
		$this->pagination = $pagination;
		$this->searched = $searched;
		
		// Indicateurs userlist
		$this->userlistIndicators = [];
		if (!empty($this->items) && !empty($this->collection)) {
			$itemIds = array_column((array) $this->items, 'id');
			$model   = $this->getModel();
			$this->userlistIndicators = $model->getUserlistIndicators($itemIds, (int) $this->collection->id);
		}

		// Template d'affichage de la liste : celui affecté à la collection (0 = tableau standard),
		// ou celui choisi par le visiteur quand le sélecteur de vue est activé
		$listView = (int) ($collection->template_list ?? 0);

		if ($params->get('show_view_switch', 0)) {
			$this->listViews = CollectorTemplateHelper::getPublished((int) $collection->id, CollectorTemplateHelper::CLIENT_LIST);

			if ($this->listViews) {
				$choice  = (int) $app->getUserStateFromRequest('com_collector.collection.' . (int) $collection->id . '.view', 'vue', $listView, 'int');
				$allowed = array_merge([0], array_map(static fn ($view) => (int) $view->id, $this->listViews));

				if (\in_array($choice, $allowed, true)) {
					$listView = $choice;
				}
			}
		}

		$this->listView     = $listView;
		$this->listTemplate = CollectorTemplateHelper::load(
			$listView,
			(int) $collection->id,
			CollectorTemplateHelper::CLIENT_LIST
		);

		if ($this->listTemplate) {
			CollectorTemplateHelper::addCss($this->listTemplate);
		}

		$this->_prepareDocument();
		
		parent::display($tpl);
	}
	
	/**
	 * Prepares the document
	 */
	protected function _prepareDocument()
	{
		$app		= Factory::getApplication();
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
			$title = Text::sprintf('JPAGETITLE', $app->getCfg('sitename'), $title);
		}
		elseif ($app->getCfg('sitename_pagetitles', 0) == 2)
		{
			$title = Text::sprintf('JPAGETITLE', $title, $app->getCfg('sitename'));
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