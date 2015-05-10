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
 * HTML Fields View class for the Collector component
 *
 * @package	Collector
 */
class CollectorViewFields extends JViewLegacy
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
			$this->authors       = $this->get('Authors');
			$this->filterForm    = $this->get('FilterForm');
			$this->activeFilters = $this->get('ActiveFilters');
			
			// What Access Permissions does this user have? What can (s)he do?
			$this->canDo	= CollectorHelper::getActions($this->state->get('collection_id'));
			
			CollectorHelper::addSubmenu('fields');
			
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
		JToolBarHelper::title(JText::_('COM_COLLECTOR_FIELDS_MANAGER'), 'menumgr');

		if ($this->canDo->get('core.create') || (count($user->getAuthorisedCategories('com_collector', 'core.create'))) > 0 ) {
			JToolBarHelper::addNew('field.add','JTOOLBAR_NEW');
		}

		if (($this->canDo->get('core.edit')) || ($this->canDo->get('core.edit.own'))) {
			JToolBarHelper::editList('field.edit','JTOOLBAR_EDIT');
		}

		if ($this->canDo->get('core.edit.state')) {
			JToolBarHelper::divider();
			JToolBarHelper::custom('fields.publish', 'publish.png', 'publish_f2.png','JTOOLBAR_PUBLISH', true);
			JToolBarHelper::custom('fields.unpublish', 'unpublish.png', 'unpublish_f2.png', 'JTOOLBAR_UNPUBLISH', true);
			JToolBarHelper::custom('fields.home', 'featured.png', 'featured_f2.png', 'JDEFAULT', true);			
			JToolBarHelper::divider();
			//JToolBarHelper::archiveList('fields.archive','JTOOLBAR_ARCHIVE');
			JToolBarHelper::custom('fields.checkin', 'checkin.png', 'checkin_f2.png', 'JTOOLBAR_CHECKIN', true);
		}

		if ($this->state->get('filter.published') == -2 && $this->canDo->get('core.delete')) {
			JToolBarHelper::deleteList('', 'fields.delete','JTOOLBAR_EMPTY_TRASH');
		}
		else if ($this->canDo->get('core.edit.state')) {
			JToolBarHelper::trash('fields.trash','JTOOLBAR_TRASH');
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
			'f.ordering' => JText::_('JGRID_HEADING_ORDERING'),
			'f.state' => JText::_('JSTATUS'),
			'f.field' => JText::_('JGLOBAL_TITLE'),
			'access_level' => JText::_('JGRID_HEADING_ACCESS'),
			'f.created_by' => JText::_('JAUTHOR'),
			// 'language' => JText::_('JGRID_HEADING_LANGUAGE'),
			'f.created' => JText::_('JDATE'),
			'f.id' => JText::_('JGRID_HEADING_ID')
		);
	}
}
?>