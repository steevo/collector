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

// Check to ensure this file is included in Joomla!
defined('_JEXEC') or die( 'Restricted access' );

jimport('joomla.application.component.view');
jimport('joomla.mail.helper');
jimport('joomla.filesystem.file');

JTable::addIncludePath(JPATH_ADMINISTRATOR.'/components/com_collector/tables');

/**
 * HTML Item View class for the Collector component
 *
 * @package	Collector
 */
class CollectorViewItemform extends JViewLegacy
{
	protected $collection;
	protected $fields;
	protected $item;
	protected $params;
	protected $form;
	protected $state;
	protected $user;
	protected $navigation;
	
	/**
	 * Display function
	 */
	public function display($tpl = null)
	{
		// Initialise variables.
		$app		= JFactory::getApplication();
		$user		= JFactory::getUser();
		$userId		= $user->get('id');
		
		$this->params		= $app->getParams();
		
		// get the Data
		$this->collection	= $this->get('Collection');
		$this->fields		= $this->get('Fields');
		$this->item			= $this->get('Item');
		$this->form			= $this->get('Form');
		$this->state		= $this->get('State');
		if ( $this->item->id != 0 ) {
			if ( $this->params->get('navigation',1) ) {
				$this->navigation	= $this->get('Navigation');
			}
		}
		$this->user			= $user;

		if (($this->collection->id == 0))
		{
			$id = $app->input->get( 'id', '', 'default', 'int' );
			return JFactory::getApplication()->enqueueMessage(JText::sprintf( 'COM_COLLECTOR_COLLECTION_NOT_FOUND', $id ),'error');
		}

		if (empty($this->item->id))
		{
			$authorised = $this->item->params->get('access-create');
		}
		else
		{
			$authorised = $this->item->params->get('access-edit');
		}
		
		if ($authorised !== true)
		{
			JFactory::getApplication()->enqueueMessage(JText::_('JERROR_ALERTNOAUTHOR'),'error');
			return false;
		}
		
		// Check for errors.
		if (count($errors = $this->get('Errors')))
		{
			JFactory::getApplication()->enqueueMessage(implode("\n", $errors),'warning');
			return false;
		}
		
		// Compute the collection slug.
		$this->collection->slug = $this->collection->alias ? ($this->collection->id . ':' . $this->collection->alias) : $this->collection->id;
		
		//Escape strings for HTML output
		$this->pageclass_sfx = htmlspecialchars($this->params->get('pageclass_sfx'));
		
		$this->_prepareDocument();
		
		parent::display($tpl);
	}

	/**
	 * Prepares the document
	 */
	protected function _prepareDocument()
	{
		$app	= JFactory::getApplication();
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
			$this->params->def('page_heading', JText::_('COM_COLLECTOR_SUBMIT_AN_ITEM'));
		}

		$title = $this->params->get('page_title', '');

		// Check for empty title and add site name if param is set
		if (empty($title)) {
			$title = $app->getCfg('sitename');
		}
		elseif ($app->getCfg('sitename_pagetitles', 0) == 1) {
			$title = JText::sprintf('JPAGETITLE', $app->getCfg('sitename'), $title);
		}
		elseif ($app->getCfg('sitename_pagetitles', 0) == 2) {
			$title = JText::sprintf('JPAGETITLE', $title, $app->getCfg('sitename'));
		}
		if (empty($title)) {
			$title = $this->item->titleItem;
		}
		$this->document->setTitle($title);

		// if ($this->item->metadesc)
		// {
			// $this->document->setDescription($this->item->metadesc);
		// }
		// elseif (!$this->item->metadesc && $this->params->get('menu-meta_description'))
		// {
			// $this->document->setDescription($this->params->get('menu-meta_description'));
		// }

		// if ($this->item->metakey)
		// {
			// $this->document->setMetadata('keywords', $this->item->metakey);
		// }
		// elseif (!$this->item->metakey && $this->params->get('menu-meta_keywords'))
		// {
			// $this->document->setMetadata('keywords', $this->params->get('menu-meta_keywords'));
		// }

		// if ($this->params->get('robots'))
		// {
			// $this->document->setMetadata('robots', $this->params->get('robots'));
		// }

		// if ($app->getCfg('MetaAuthor') == '1')
		// {
			// $this->document->setMetaData('author', $this->item->author);
		// }

		// $mdata = $this->item->metadata->toArray();
		// foreach ($mdata as $k => $v)
		// {
			// if ($v)
			// {
				// $this->document->setMetadata($k, $v);
			// }
		// }
	}
}

?>