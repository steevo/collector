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

/**
 * HTML Itemversions View class for the Collector component
 *
 * @package	Collector
 */
class CollectorViewItemversions extends JViewLegacy
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
		$this->canDo	= CollectorHelper::getActions($this->state->get('filter.collection'),$this->state->get('filter.item'));
		
		CollectorHelper::addSubmenu('items');
		
		// Check for errors.
		if (count($errors = $this->get('Errors'))) {
			JError::raiseError(500, implode("\n", $errors));
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
		$canDo	= CollectorHelper::getActions();
		
		JToolBarHelper::title(JText::_('COM_COLLECTOR_ITEMS_MANAGER_HISTORY'), 'preview');
		
		if ($canDo->get('core.delete')) {
			JToolBarHelper::deleteList('', 'itemversions.delete','JTOOLBAR_DELETE');
		}
		
		JToolBarHelper::divider();
		
		JToolBarHelper::custom('itemversions.back','arrow-left-2','','JTOOLBAR_BACK',false);
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
			'h.modified' => JText::_('JGRID_HEADING_ORDERING'),
			'h.id' => JText::_('JGRID_HEADING_ID')
		);
	}
}
?>
