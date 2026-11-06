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

namespace Steevo\Component\Collector\Administrator\View\Listitem;

defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\Helper\ContentHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\View\GenericDataException;
use Joomla\CMS\MVC\View\HtmlView as BaseHtmlView;
use Joomla\CMS\Toolbar\Toolbar;
use Joomla\CMS\Toolbar\ToolbarHelper;

/**
 * HTML Definedcontentedit View class for the Collector component
 *
 * @package	Collector
 */
class HtmlView extends BaseHtmlView
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
		$this->state	= $this->get('State');
		$this->canDo = ContentHelper::getActions('com_collector');
		
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
		$userId		= $user->get('id');
		$isNew		= ($this->item->id == 0);
		$canDo = $this->canDo;
		
		ToolBarHelper::title($isNew ? Text::_('COM_COLLECTOR_LISTITEMS_MANAGER_ITEM_NEW') : Text::_('COM_COLLECTOR_LISTITEMS_MANAGER_ITEM_EDIT'));
		
		// Built the actions for new and existing records.
		if ($isNew) 
		{
			// For new records, check the create permission.
			if ($canDo->get('core.create')) 
			{
				ToolBarHelper::apply('listitem.apply');
				ToolbarHelper::saveGroup(
                    [
                        ['save', 'listitem.save'],
                        ['save2new', 'listitem.save2new'],
                    ],
                    'btn-success'
                );
			}
			ToolBarHelper::cancel('listitem.cancel');
		}
		else
		{
			// Since it's an existing record, check the edit permission, or fall back to edit own if the owner.
			if ($canDo->get('core.edit')) {
				ToolBarHelper::apply('listitem.apply');
				ToolBarHelper::save('listitem.save');
			}

			// If checked out, we can still save
			if ($canDo->get('core.create')) {
				//ToolBarHelper::save2copy('listitem.save2copy');
				ToolBarHelper::save2new('listitem.save2new');
			}

			ToolBarHelper::cancel('listitem.cancel', 'JTOOLBAR_CLOSE');
		}
	}
}
?>