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

namespace Steevo\Component\Collector\Administrator\View\Items;

defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\Helper\ContentHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\View\GenericDataException;
use Joomla\CMS\MVC\View\HtmlView as BaseHtmlView;
use Joomla\CMS\Router\Route;
use Joomla\CMS\Toolbar\Toolbar;
use Joomla\CMS\Toolbar\ToolbarHelper;
use Joomla\CMS\HTML\HTMLHelper;
use Steevo\Component\Collector\Administrator\Helper\CollectorGrid;

/**
 * HTML Items View class for the Collector component
 *
 * @package	Collector
 */
class HtmlView extends BaseHtmlView
{
	/**
     * An array of items
     *
     * @var  array
     */
    protected $items;

    /**
     * The pagination object
     *
     * @var  \Joomla\CMS\Pagination\Pagination
     */
    protected $pagination;

    /**
     * The model state
     *
     * @var  \Joomla\CMS\Object\CMSObject
     */
    protected $state;

	/**
     * Form object for search filters
     *
     * @var  \Joomla\CMS\Form\Form
     */
    public $filterForm;

    /**
     * The active search filters
     *
     * @var  array
     */
    public $activeFilters;

    /**
    * Is this view an Empty State
    *
    * @var  boolean
    * @since 4.0.0
    */
    private $isEmptyState = false;

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
			
			// Check for errors.
			if (count($errors = $this->get('Errors'))) {
				throw new GenericDataException(implode("\n", $errors), 500);
			}
			
			if ( !empty($this->collection->title) )
			{
				// We don't need toolbar in the modal window.
				if ($this->getLayout() !== 'modal') {
					$this->addToolbar();
				}
				
				parent::display($tpl);
			}
			else
			{
				$msg = Text::_( 'COM_COLLECTOR_CONF_FIELD_FIRST' ) . ' <a href="index.php?option=com_collector&view=fields&collection=' . $this->collection->id . '" >' . Text::_( 'COM_COLLECTOR_CONF_FIELD_MENU' ) . '</a>';
				$type = 'error';
				$app = Factory::getApplication();
				$app->enqueueMessage($msg,$type);
			}
		}
		else
		{
			$msg = Text::_( 'COM_COLLECTOR_CONF_COL_FIRST' ) . ' <a href="index.php?option=com_collector&view=collections" >' . Text::_( 'COM_COLLECTOR_CONF_COL_MENU' ) . '</a>';
			$type = 'error';
			$app = Factory::getApplication();
			$app->enqueueMessage($msg,$type);
		}
	}
	
	/**
	 * Add the page title and toolbar.
	 */
	protected function addToolbar()
	{
		$canDo = ContentHelper::getActions('com_collector', 'collection', $this->state->get('filter.collection_id'));
		$user  = $this->getCurrentUser();
		
		// Get the toolbar object instance
		$toolbar = ToolBar::getInstance('toolbar');
		
		ToolBarHelper::title(Text::_('COM_COLLECTOR_ITEMS_MANAGER'), 'article');

		if ($canDo->get('core.create') || (count($user->getAuthorisedCategories('com_collector', 'core.create'))) > 0 ) {
			ToolBarHelper::addNew('item.add');
		}

		ToolBarHelper::custom('items.history', 'clock', '', 'COM_COLLECTOR_HISTORY' ,true);

		if ($canDo->get('core.edit.state')) {
			$dropdown = $toolbar->dropdownButton('status-group')
                ->text('JTOOLBAR_CHANGE_STATUS')
                ->toggleSplit(false)
                ->icon('icon-ellipsis-h')
                ->buttonClass('btn btn-action')
                ->listCheck(true);
			
			$childBar = $dropdown->getChildToolbar();
			
			$childBar->publish('items.publish')->listCheck(true);
			
			$childBar->unpublish('items.unpublish')->listCheck(true);
			
			$childBar->archive('items.archive')->listCheck(true);
			
			if ($user->authorise('core.admin')) {
                $childBar->checkin('items.checkin')->listCheck(true);
            }
			
			if ($this->state->get('filter.published') != -2) {
				$childBar->trash('items.trash')->listCheck(true);
			}
		}

		if ($this->state->get('filter.published') == -2 && $canDo->get('core.delete')) {
			$toolbar->delete('items.delete')
                ->text('JTOOLBAR_DELETE_FROM_TRASH')
                ->message('JGLOBAL_CONFIRM_DELETE')
                ->listCheck(true);
		}
		
		if ($canDo->get('core.admin'))
		{
			ToolBarHelper::custom('items.rebuild', 'refresh.png', 'refresh_f2.png', 'COM_COLLECTOR_REBUILD_FULLTITLES', false);
		}
		
		// if ($user->authorise('core.edit'))
		// {
			// HTMLHelper::_('bootstrap.modal', 'collapseModal');
			// $title = Text::_('ToolBar_BATCH');
			// $dhtml = "<button data-toggle=\"modal\" data-target=\"#collapseModal\" class=\"btn btn-small\">
						// <i class=\"icon-checkbox-partial\" title=\"$title\"></i>
						// $title</button>";
			// $bar->appendButton('Custom', $dhtml, 'batch');
		// }
		
		if ($canDo->get('core.create') || (count($user->getAuthorisedCategories('com_collector', 'core.create'))) > 0 ) {
			ToolBarHelper::divider();
			// ToolBarHelper::custom('import.upload', 'box-add.png', 'box-add.png', 'COM_COLLECTOR_IMPORT_EXCEL', false);
			$importUrl = Route::_('index.php?option=com_collector&view=import&collection=' . $this->collection->id, false);
			ToolbarHelper::link($importUrl, 'COM_COLLECTOR_IMPORT_EXCEL', 'upload');
		
			ToolbarHelper::link(
				Route::_('index.php?option=com_collector&view=importhistory&collection=' . $this->collection->id, false),
				'COM_COLLECTOR_IMPORTHISTORY_BTN',
				'clock'
			);
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
			'i.ordering' => Text::_('JGRID_HEADING_ORDERING'),
			'i.state' => Text::_('JSTATUS'),
			'i.alias' => Text::_('JGLOBAL_TITLE'),
			'access_level' => Text::_('JGRID_HEADING_ACCESS'),
			'i.created_by' => Text::_('JAUTHOR'),
			// 'language' => Text::_('JGRID_HEADING_LANGUAGE'),
			'i.created' => Text::_('JDATE'),
			'i.id' => Text::_('JGRID_HEADING_ID')
		);
	}
}