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

/**
 * HTML Items View class for the Collector component
 *
 * @package	Collector
 */
class CollectorViewItems extends JViewLegacy
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
		$this->collection 	= $this->get('Collection');
		
		if ( $this->collection != false )
		{
			$this->fields        = $this->get('Fields');
			$this->items         = $this->get('Items');
			$this->pagination    = $this->get('Pagination');
			$this->state         = $this->get('State');
			$this->authors       = $this->get('Authors');
			$this->filterForm    = $this->get('FilterForm');
			$this->activeFilters = $this->get('ActiveFilters');
			
			// What Access Permissions does this user have? What can (s)he do?
			$this->canDo	= CollectorHelper::getActions($this->state->get('collection_id'));
			
			CollectorHelper::addSubmenu('items');
			
			// Check for errors.
			if (count($errors = $this->get('Errors'))) {
				JError::raiseError(500, implode("\n", $errors));
				return false;
			}
			
			if ( !empty($this->collection->title) )
			{
				// We don't need toolbar in the modal window.
				if ($this->getLayout() !== 'modal') {
					$this->addToolbar();
					$this->sidebar = JHtmlSidebar::render();
				}

				parent::display($tpl);
			}
			else
			{
				$msg = JText::_( 'COM_COLLECTOR_CONF_FIELD_FIRST' ) . ' <a href="index.php?option=com_collector&view=fields&collection=' . $this->collection->id . '" >' . JText::_( 'COM_COLLECTOR_CONF_FIELD_MENU' ) . '</a>';
				$type = 'error';
				$app = JFactory::getApplication();
				$app->enqueueMessage($msg,$type);
			}
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
		$user	= JFactory::getUser();
		JToolBarHelper::title(JText::_('COM_COLLECTOR_ITEMS_MANAGER'), 'article');

		if ($this->canDo->get('core.create') || (count($user->getAuthorisedCategories('com_collector', 'core.create'))) > 0 ) {
			JToolBarHelper::addNew('item.add');
		}

		if (($this->canDo->get('core.edit')) || ($this->canDo->get('core.edit.own'))) {
			JToolBarHelper::editList('item.edit');
		}

		JToolBarHelper::custom('items.history', 'clock', '', 'COM_COLLECTOR_HISTORY' ,true);

		if ($this->canDo->get('core.edit.state')) {
			JToolBarHelper::publish('items.publish', 'JTOOLBAR_PUBLISH', true);
			JToolBarHelper::unpublish('items.unpublish', 'JTOOLBAR_UNPUBLISH', true);
			JToolBarHelper::archiveList('items.archive');
			JToolBarHelper::checkin('items.checkin');
		}

		if ($this->state->get('filter.published') == -2 && $this->canDo->get('core.delete')) {
			JToolBarHelper::deleteList('', 'items.delete','JTOOLBAR_EMPTY_TRASH');
		}
		else if ($this->canDo->get('core.edit.state')) {
			JToolBarHelper::trash('items.trash');
		}
		
		if ($this->canDo->get('core.admin'))
		{
			JToolbarHelper::custom('items.rebuild', 'refresh.png', 'refresh_f2.png', 'COM_COLLECTOR_REBUILD_FULLTITLES', false);
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
			'i.ordering' => JText::_('JGRID_HEADING_ORDERING'),
			'i.state' => JText::_('JSTATUS'),
			'i.alias' => JText::_('JGLOBAL_TITLE'),
			'access_level' => JText::_('JGRID_HEADING_ACCESS'),
			'i.created_by' => JText::_('JAUTHOR'),
			// 'language' => JText::_('JGRID_HEADING_LANGUAGE'),
			'i.created' => JText::_('JDATE'),
			'i.id' => JText::_('JGRID_HEADING_ID')
		);
	}
}