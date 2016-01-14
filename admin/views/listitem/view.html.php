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

/**
 * HTML Definedcontentedit View class for the Collector component
 *
 * @package	Collector
 */
class CollectorViewListitem extends JViewLegacy
{
	protected $form;

	protected $item;

	protected $state;
	
	/**
	 * display method of defined view
	 * @return void
	 */
	public function display($tpl = null)
	{
		// get the Data
		$this->form	= $this->get('Form');
		$this->item	= $this->get('Item');
		
		// Check for errors.
		if (count($errors = $this->get('Errors'))) 
		{
			JError::raiseError(500, implode('<br />', $errors));
			return false;
		}
		
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
		$canDo		= CollectorHelper::getActions();
		
		JToolBarHelper::title($isNew ? JText::_('COM_COLLECTOR_LISTITEMS_MANAGER_ITEM_NEW') : JText::_('COM_COLLECTOR_LISTITEMS_MANAGER_ITEM_EDIT'));
		
		// Built the actions for new and existing records.
		if ($isNew) 
		{
			// For new records, check the create permission.
			if ($canDo->get('core.create')) 
			{
				JToolBarHelper::apply('listitem.apply');
				JToolBarHelper::save('listitem.save');
				JToolBarHelper::cancel('listitem.cancel');
			}
		}
		else
		{
			// Since it's an existing record, check the edit permission, or fall back to edit own if the owner.
			if ($canDo->get('core.edit')) {
				JToolBarHelper::apply('listitem.apply');
				JToolBarHelper::save('listitem.save');
			}

			// If checked out, we can still save
			if ($canDo->get('core.create')) {
				//JToolBarHelper::save2copy('listitem.save2copy');
			}

			JToolBarHelper::cancel('listitem.cancel', 'JTOOLBAR_CLOSE');
		}
	}
}
?>