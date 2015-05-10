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
 * HTML Templates View class for the Collector component
 *
 * @package	Collector
 */
class CollectorViewTemplates extends JViewLegacy
{
	/**
	 * Display the view
	 *
	 * @return	void
	 */
	public function display($tpl = null)
	{
		// What Access Permissions does this user have? What can (s)he do?
		$this->canDo	= CollectorHelper::getActions();
		
		CollectorHelper::addSubmenu('templates');
		
		// We don't need toolbar in the modal window.
		if ($this->getLayout() !== 'modal') {
			$this->addToolbar();
			$this->sidebar = JHtmlSidebar::render();
		}
		
		$msg = JText::_( 'COM_COLLECTOR_NOT_AVAILABLE' );
		$type = 'error';
		$app = JFactory::getApplication();
		$app->enqueueMessage($msg,$type);
		
		parent::display($tpl);
	}
	
	/**
	 * Add the page title and toolbar.
	 */
	protected function addToolbar()
	{
		$user		= JFactory::getUser();
		JToolBarHelper::title(JText::_('COM_COLLECTOR_TEMPLATES_MANAGER'), 'thememanager');

		// if ($this->canDo->get('core.create') || (count($user->getAuthorisedCategories('com_collector', 'core.create'))) > 0 ) {
			// JToolBarHelper::addNew('item.add','JTOOLBAR_NEW');
		// }

		// if (($this->canDo->get('core.edit')) || ($this->canDo->get('core.edit.own'))) {
			// JToolBarHelper::editList('item.edit','JTOOLBAR_EDIT');
		// }

		// JToolBarHelper::custom('items.history', 'preview', 'preview', 'COM_COLLECTOR_HISTORY');

		// if ($this->canDo->get('core.edit.state')) {
			// JToolBarHelper::divider();
			// JToolBarHelper::custom('items.publish', 'publish.png', 'publish_f2.png','JTOOLBAR_PUBLISH', true);
			// JToolBarHelper::custom('items.unpublish', 'unpublish.png', 'unpublish_f2.png', 'JTOOLBAR_UNPUBLISH', true);
			// JToolBarHelper::divider();
			// JToolBarHelper::archiveList('items.archive','JTOOLBAR_ARCHIVE');
			// JToolBarHelper::custom('items.checkin', 'checkin.png', 'checkin_f2.png', 'JTOOLBAR_CHECKIN', true);
		// }

		// if ($this->state->get('filter.published') == -2 && $this->canDo->get('core.delete')) {
			// JToolBarHelper::deleteList('', 'items.delete','JTOOLBAR_EMPTY_TRASH');
		// }
		// else if ($this->canDo->get('core.edit.state')) {
			// JToolBarHelper::trash('items.trash','JTOOLBAR_TRASH');
		// }
	}
}