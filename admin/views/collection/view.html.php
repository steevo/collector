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
 * HTML Collection View class for the Collector component
 *
 * @package	Collector
 */
class CollectorViewCollection extends JViewLegacy
{
	/**
	 * display method of Collection view
	 * @return void
	 */
	public function display($tpl = null) 
	{
		// get the Data
		$form	= $this->get('Form');
		$item	= $this->get('Item');
		$state	= $this->get('State');
 
		// Check for errors.
		if (count($errors = $this->get('Errors'))) 
		{
			JFactory::getApplication()->enqueueMessage(implode('<br />', $errors),'error');
			return false;
		}
		// Assign the Data
		$this->form		= $form;
		$this->item		= $item;
		$this->state	= $state;
		$this->canDo	= CollectorHelper::getActions($this->state->get('filter.collection'));
 
		// Set the toolbar
		$this->addToolBar();
 
		// Display the template
		parent::display($tpl);
	}
 
	/**
	 * Setting the toolbar
	 */
	protected function addToolBar() 
	{
		JFactory::getApplication()->input->set('hidemainmenu', true);
		$user		= JFactory::getUser();
		$userId		= $user->get('id');
		$isNew		= ($this->item->id == 0);
		$checkedOut	= !($this->item->checked_out == 0 || $this->item->checked_out == $userId);
		$canDo		= CollectorHelper::getActions($this->item->id);
		
		JToolBarHelper::title($isNew ? JText::_('COM_COLLECTOR_MANAGER_COLLECTION_NEW') : JText::_('COM_COLLECTOR_MANAGER_COLLECTION_EDIT'));
		
		// Built the actions for new and existing records.
		if ($isNew) 
		{
			// For new records, check the create permission.
			if ($canDo->get('core.create')) 
			{
				JToolBarHelper::apply('collection.apply');
				JToolBarHelper::save('collection.save');
				JToolBarHelper::save2new('collection.save2new');
				JToolBarHelper::cancel('collection.cancel');
			}
		}
		else
		{
			// Can't save the record if it's checked out.
			if (!$checkedOut) {
				// Since it's an existing record, check the edit permission, or fall back to edit own if the owner.
				if ($canDo->get('core.edit') || ($canDo->get('core.edit.own') && $this->item->created_by == $userId)) {
					JToolBarHelper::apply('collection.apply');
					JToolBarHelper::save('collection.save');

					// We can save this record, but check the create permission to see if we can return to make a new one.
					if ($canDo->get('core.create')) {
						JToolBarHelper::save2new('collection.save2new');
					}
				}
			}

			// If checked out, we can still save
			if ($canDo->get('core.create')) {
				// JToolBarHelper::save2copy('collection.save2copy');
			}

			JToolBarHelper::cancel('collection.cancel', 'JTOOLBAR_CLOSE');
		}
	}
}