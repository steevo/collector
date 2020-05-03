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
 * HTML Listitems View class for the Collector component
 *
 * @package	Collector
 */
class CollectorViewListitems extends JViewLegacy
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
		
		$this->ordering = array();
		
		// Preprocess the list of items to find ordering divisions.
		foreach ($this->items as $item)
		{
			$this->ordering[$item->parent_id][] = $item->id;
		}
		
		// What Access Permissions does this user have? What can (s)he do?
		$this->canDo	= CollectorHelper::getActions();
		
		CollectorHelper::addSubmenu('lists');
		
		// Check for errors.
		if (count($errors = $this->get('Errors'))) {
			JFactory::getApplication()->enqueueMessage(implode('<br />', $errors),'error');
			return false;
		}
		
		// Levels filter.
		$options	= array();
		$options[]	= JHtml::_('select.option', '1', JText::_('J1'));
		$options[]	= JHtml::_('select.option', '2', JText::_('J2'));
		$options[]	= JHtml::_('select.option', '3', JText::_('J3'));
		$options[]	= JHtml::_('select.option', '4', JText::_('J4'));
		$options[]	= JHtml::_('select.option', '5', JText::_('J5'));
		$options[]	= JHtml::_('select.option', '6', JText::_('J6'));
		$options[]	= JHtml::_('select.option', '7', JText::_('J7'));
		$options[]	= JHtml::_('select.option', '8', JText::_('J8'));
		$options[]	= JHtml::_('select.option', '9', JText::_('J9'));
		$options[]	= JHtml::_('select.option', '10', JText::_('J10'));

		$this->f_levels = $options;
		
		// We don't need toolbar in the modal window.
		if ($this->getLayout() !== 'modal') {
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
		
		JToolBarHelper::title( JText::_( 'COM_COLLECTOR_LISTITEMS_MANAGER' ), 'sections' );
		
		if ($this->canDo->get('core.create') || (count($user->getAuthorisedCategories('com_collector', 'core.create'))) > 0 ) {
			JToolBarHelper::addNew('listitem.add','JTOOLBAR_NEW');
		}
		
		if (($this->canDo->get('core.edit')) || ($this->canDo->get('core.edit.own'))) {
			JToolBarHelper::editList('listitem.edit','JTOOLBAR_EDIT');
		}
		
		if ($this->canDo->get('core.delete')) {
			JToolBarHelper::deleteList('', 'listitems.delete','JTOOLBAR_DELETE');
		}
		
		JToolBarHelper::divider();
		
		JToolBarHelper::custom('listitems.back','arrow-left-2','','JTOOLBAR_BACK',false);
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
			'c.lft' => JText::_('JGRID_HEADING_ORDERING'),
			'c.content' => JText::_('JGLOBAL_TITLE'),
			'c.id' => JText::_('JGRID_HEADING_ID')
		);
	}
}
?>