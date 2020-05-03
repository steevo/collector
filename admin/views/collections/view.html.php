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

defined('_JEXEC') or die( 'Restricted access' );

/**
 * HTML Collections View class for the Collector component
 *
 * @package	Collector
 */
class CollectorViewCollections extends JViewLegacy
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
		$this->items         = $this->get('Items');
		$this->pagination    = $this->get('Pagination');
		$this->state         = $this->get('State');
		$this->filterForm    = $this->get('FilterForm');
		$this->activeFilters = $this->get('ActiveFilters');
		
		// What Access Permissions does this user have? What can (s)he do?
		$this->canDo	= CollectorHelper::getActions();
		
		CollectorHelper::addSubmenu('collections');
		
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

	/**
	 * Add the page title and toolbar.
	 */
	protected function addToolbar()
	{
		$user		= JFactory::getUser();
		
		// Get the toolbar object instance
		$bar = JToolBar::getInstance('toolbar');
		
		JToolBarHelper::title(JText::_('COM_COLLECTOR_COL_MANAGER'), 'categories.png');

		if ($this->canDo->get('core.create') || (count($user->getAuthorisedCategories('com_collector', 'core.create'))) > 0 ) {
			JToolBarHelper::addNew('collection.add','JTOOLBAR_NEW');
		}

		if (($this->canDo->get('core.edit')) || ($this->canDo->get('core.edit.own'))) {
			JToolBarHelper::editList('collection.edit','JTOOLBAR_EDIT');
		}

		if ($this->canDo->get('core.edit.state')) {
			JToolBarHelper::publish('collections.publish','JTOOLBAR_PUBLISH', true);
			JToolBarHelper::unpublish('collections.unpublish', 'JTOOLBAR_UNPUBLISH', true);
			JToolBarHelper::custom('collections.home', 'featured.png', 'featured_f2.png', 'JDEFAULT', true);
			JToolBarHelper::archiveList('collections.archive','JTOOLBAR_ARCHIVE');
			JToolBarHelper::checkin('collections.checkin', 'JTOOLBAR_CHECKIN', true);
		}

		if ($this->state->get('filter.published') == -2 && $this->canDo->get('core.delete')) {
			JToolBarHelper::deleteList('', 'collections.delete','JTOOLBAR_EMPTY_TRASH');
		}
		else if ($this->canDo->get('core.edit.state')) {
			JToolBarHelper::trash('collections.trash','JTOOLBAR_TRASH');
		}
		
		// Add a batch button
		if ($this->canDo->get('core.create') && $this->canDo->get('core.edit') && $this->canDo->get('core.edit.state'))
		{
			// we use a standard Joomla layout to get the html for the batch button
			$layout = new JLayoutFile('joomla.toolbar.batch');
			$batchButtonHtml = $layout->render(array('title' => JText::_('JTOOLBAR_BATCH')));
			$bar->appendButton('Custom', $batchButtonHtml, 'batch');
		}
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
			'c.ordering' => JText::_('JGRID_HEADING_ORDERING'),
			'c.state' => JText::_('JSTATUS'),
			'c.name' => JText::_('JGLOBAL_TITLE'),
			'c.access' => JText::_('JGRID_HEADING_ACCESS'),
			'c.created_by' => JText::_('JAUTHOR'),
			'c.created' => JText::_('JDATE'),
			'c.id' => JText::_('JGRID_HEADING_ID')
		);
	}
}