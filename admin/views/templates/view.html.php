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

/**
 * HTML Templates View class for the Collector component
 *
 * @package	Collector
 */
class CollectorViewTemplates extends JViewLegacy
{
	protected $items;
	protected $pagination;
	protected $state;

	/**
	 * Display the view
	 *
	 * @return	void
	 */
	public function display($tpl = null)
	{
		$collection 		= $this->get('Collection');
		
		if ( $collection != false )
		{
			$this->items         = $this->get('Items');
			$this->pagination    = $this->get('Pagination');
			$this->state         = $this->get('State');
			$this->filterForm    = $this->get('FilterForm');
			$this->activeFilters = $this->get('ActiveFilters');
			
			// What Access Permissions does this user have? What can (s)he do?
			$this->canDo	= CollectorHelper::getActions($this->state->get('collection_id'));
			
			CollectorHelper::addSubmenu('templates');
			
			// Check for errors.
			if (count($errors = $this->get('Errors'))) {
				JFactory::getApplication()->enqueueMessage(implode('<br />', $errors),'error');
				return false;
			}
			
			// We don't need toolbar in the modal window.
			if ($this->getLayout() !== 'modal') {
				$this->addToolbar();
				$this->sidebar = JHtmlSidebar::render();
			}
		
			parent::display($tpl);
		}
		else
		{
			$msg = JText::_( 'COM_COLLECTOR_CONF_COL_FIRST' ) . ' <a href="index.php?option=com_collector&view=collections" >' . JText::_( 'COM_COLLECTOR_CONF_COL_MENU' ) . '</a>';
			$type = 'error';
			$app = JFactory::getApplication();
			$app->enqueueMessage($msg,$type);
		}
	}
	
	/**
	 * Add the page title and toolbar.
	 */
	protected function addToolbar()
	{
		$user		= JFactory::getUser();
		JToolBarHelper::title(JText::_('COM_COLLECTOR_TEMPLATES_MANAGER'), 'thememanager');

		if ($this->canDo->get('core.create') || (count($user->getAuthorisedCategories('com_collector', 'core.create'))) > 0 ) {
			JToolBarHelper::addNew('template.add','JTOOLBAR_NEW');
		}

		if (($this->canDo->get('core.edit')) || ($this->canDo->get('core.edit.own'))) {
			JToolBarHelper::editList('template.edit','JTOOLBAR_EDIT');
		}

		if ($this->canDo->get('core.edit.state')) {
			JToolBarHelper::custom('templates.home', 'featured.png', 'featured_f2.png', 'JDEFAULT', true);
		}

		if ($this->canDo->get('core.delete')) {
			JToolBarHelper::deleteList('', 'templates.delete','JTOOLBAR_DELETE');
		}
	}
}