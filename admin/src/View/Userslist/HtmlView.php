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

namespace Steevo\Component\Collector\Administrator\View\Userslist;

defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\View\GenericDataException;
use Joomla\CMS\MVC\View\HtmlView as BaseHtmlView;
use Joomla\CMS\Toolbar\ToolbarHelper;

/**
 * HTML Userslist View class for the Collector component
 *
 * @package	Collector
 */
class HtmlView extends BaseHtmlView
{
	protected $form;
	protected $item;
	protected $state;
    protected $types;

	/**
	 * Display function
	 */
	public function display($tpl = null)
	{
		// get the Data
		$this->form		= $this->get('Form');
		$this->item		= $this->get('Item');
		$this->state	= $this->get('State');
		$this->types	= $this->get('Types');
		
		// Check for errors.
		if (count($errors = $this->get('Errors'))) {
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
		$app        = Factory::getApplication();
        $user       = $app->getIdentity();
		$userId		= $user->get('id');
		$isNew		= ($this->item->id == 0);
		
		$app->input->set('hidemainmenu', true);
		
		$checkedOut	= !($this->item->checked_out == 0 || $this->item->checked_out == $userId);
		
		ToolBarHelper::title($isNew ? Text::_('COM_COLLECTOR_MANAGER_USERSLIST_NEW') : Text::_('COM_COLLECTOR_MANAGER_USERSLIST_EDIT'));
		
		// Built the actions for new and existing records.
		if ($isNew) {
			// For new records, check the create permission.
			if ($user->authorise('core.create', 'com_collector')) {
				ToolBarHelper::apply('userslist.apply');
				ToolBarHelper::save('userslist.save');
				ToolBarHelper::cancel('userslist.cancel');
			}
		} else {
			// Can't save the record if it's checked out.
			if (!$checkedOut) {
				// Since it's an existing record, check the edit permission, or fall back to edit own if the owner.
				if ($user->authorise('core.edit', 'com_collector')
                    || ($user->authorise('core.edit.own', 'com_collector')
					ToolBarHelper::apply('userslist.apply');
					ToolBarHelper::save('userslist.save');
				}
			}

			ToolbarHelper::cancel('userslist.cancel', 'JTOOLBAR_CLOSE');
		}
	}
}
?>