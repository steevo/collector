<?php
/**
 * Joomla! 3.0 component Collector
 *
 * @package 	Collector
 * @copyright   Copyright (C) 2010 - 2015 Open Source Matters, Inc. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 *
 * Collector is a Multi Purpose Listing Tool.
 * Originaly developped to list Collections
 * it can be used for several purpose.
 */

// Check to ensure this file is included in Joomla!
defined('_JEXEC') or die( 'Restricted access' );

jimport('joomla.application.component.view');

/**
 * HTML Lists View class for the Collector component
 *
 * @package	Collector
 */
class CollectorViewLists extends JViewLegacy
{
	protected $items;
	protected $pagination;
	protected $state;
	
	/**
	 * Display function
	 */
	public function display($tpl = null)
	{
		$this->items         = $this->get('Items');
		$this->pagination    = $this->get('Pagination');
		$this->state         = $this->get('State');
		$this->filterForm    = $this->get('FilterForm');
		$this->activeFilters = $this->get('ActiveFilters');
		
		// What Access Permissions does this user have? What can (s)he do?
		$this->canDo	= CollectorHelper::getActions();
		
		CollectorHelper::addSubmenu('lists');
		
		// Check for errors.
		if (count($errors = $this->get('Errors'))) {
			JError::raiseError(500, implode("\n", $errors));
			return false;
		}
		
		// We don't need toolbar in the modal window.
		if ($this->getLayout() !== 'modal')
		{
			$this->addToolbar();
			$this->sidebar = JHtmlSidebar::render();
		}

		parent::display($tpl);
	}
	
	/**
	 * Setting the toolbar
	 */
	protected function addToolBar() 
	{
		$user		= JFactory::getUser();
		
		JToolBarHelper::title( JText::_( 'COM_COLLECTOR_LISTS_MANAGER' ), 'sections' );
		
		if ($this->canDo->get('core.create') || (count($user->getAuthorisedCategories('com_collector', 'core.create'))) > 0 ) {
			JToolBarHelper::addNew('list.add','JTOOLBAR_NEW');
		}
		
		if (($this->canDo->get('core.edit')) || ($this->canDo->get('core.edit.own'))) {
			JToolBarHelper::editList('list.edit','JTOOLBAR_EDIT');
		}
		
		if ($this->canDo->get('core.delete')) {
			JToolBarHelper::deleteList('', 'lists.delete','JTOOLBAR_DELETE');
		}
		
		// JHtmlSidebar::setAction('index.php?option=com_collector&view=lists');
	}

	/**
	 * Returns an array of fields the table can be sorted by
	 *
	 * @return  array  Array containing the field name to sort by as the key and display text as value
	 *
	 * @since   3.0
	 */
	protected function getSortFields()
	{
		return array(
			'c.name' => JText::_('JGLOBAL_TITLE'),
			'c.created_by' => JText::_('JAUTHOR'),
			'c.created' => JText::_('JDATE'),
			'c.id' => JText::_('JGRID_HEADING_ID')
		);
	}
}
?>