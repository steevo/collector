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

namespace Steevo\Component\Collector\Administrator\View\Collection;

defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\Helper\ContentHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\View\GenericDataException;
use Joomla\CMS\MVC\View\HtmlView as BaseHtmlView;
use Joomla\CMS\Toolbar\Toolbar;
use Joomla\CMS\Toolbar\ToolbarHelper;

/**
 * HTML Collection View class for the Collector component
 *
 * @package	Collector
 */
class HtmlView extends BaseHtmlView
{
	/**
	 * display method of Collection view
	 * @return void
	 */
	public function display($tpl = null) 
	{
		$this->form	 = $this->get('Form');
		$this->item	 = $this->get('Item');
		$this->state = $this->get('State');
		$this->canDo = ContentHelper::getActions('com_collector', 'collection', $this->item->id);
 
		// Check for errors.
		if (count($errors = $this->get('Errors'))) 
		{
			throw new GenericDataException(implode("\n", $errors), 500);
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
		$app = Factory::getApplication();
        $app->input->set('hidemainmenu', true);
		
		$user		= $this->getCurrentUser();
		$isNew		= ($this->item->id == 0);
		$checkedOut = $this->item->checked_out && $this->item->checked_out !== $user->get('id');
		$canDo = $this->canDo;
        
		ToolBarHelper::title($isNew ? Text::_('COM_COLLECTOR_MANAGER_COLLECTION_NEW') : Text::_('COM_COLLECTOR_MANAGER_COLLECTION_EDIT'));
		
		// Built the actions for new and existing records.
		if ($isNew) 
		{
			// For new records, check the create permission.
			if ($canDo->get('core.create')) 
			{
				ToolBarHelper::apply('collection.apply');
				ToolbarHelper::saveGroup(
                    [
                        ['save', 'collection.save'],
                        ['save2new', 'collection.save2new'],
                    ],
                    'btn-success'
                );
			}
			ToolBarHelper::cancel('collection.cancel');
		}
		else
		{
			// Since it's an existing record, check the edit permission, or fall back to edit own if the owner.
            $itemEditable = $canDo->get('core.edit') || ($canDo->get('core.edit.own') && $this->item->created_by == $user->id);

            $toolbarButtons = [];
			
			// Can't save the record if it's checked out.
			if (!$checkedOut && $itemEditable) {
				ToolbarHelper::apply('collection.apply');

                $toolbarButtons[] = ['save', 'collection.save'];
				
				// We can save this record, but check the create permission to see if we can return to make a new one.
                if ($canDo->get('core.create')) {
                    $toolbarButtons[] = ['save2new', 'collection.save2new'];
                }
			}

			// If checked out, we can still save
			if ($canDo->get('core.create')) {
				$toolbarButtons[] = ['save2copy', 'collection.save2copy'];
			}

			ToolbarHelper::saveGroup(
                $toolbarButtons,
                'btn-success'
            );
			
			ToolBarHelper::cancel('collection.cancel', 'JTOOLBAR_CLOSE');
		}
		
		ToolBarHelper::divider();
        ToolBarHelper::inlinehelp();
	}
}