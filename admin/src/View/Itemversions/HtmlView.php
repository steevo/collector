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

namespace Steevo\Component\Collector\Administrator\View\Itemversions;

defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\Helper\ContentHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\View\GenericDataException;
use Joomla\CMS\MVC\View\HtmlView as BaseHtmlView;
use Joomla\CMS\Toolbar\Toolbar;
use Joomla\CMS\Toolbar\ToolbarHelper;

/**
 * HTML Itemversions View class for the Collector component
 *
 * @package	Collector
 */
class HtmlView extends BaseHtmlView
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
		if ($this->getLayout() === 'reload') {
            return parent::display($tpl);
        }
		
		$this->item            = $this->get('Item');
		$this->items           = $this->get('Items');
		$this->pagination      = $this->get('Pagination');
		$this->state           = $this->get('State');
		$this->filterForm      = $this->get('FilterForm');
		$this->activeFilters   = $this->get('ActiveFilters');
		$this->collection      = $this->get('Collection');
		$this->activeVersionId = $this->get('ActiveVersionId');
		$this->oldestPendingId = $this->get('OldestPendingVersionId');
		
		// Check for errors.
		if (count($errors = $this->get('Errors'))) {
			throw new GenericDataException(implode("\n", $errors), 500);
		}
		
		if ($this->getLayout() !== 'modal') {
			$this->addToolbar();
		}
		
		parent::display($tpl);
	}
	
	/**
	 * Add the page title and toolbar.
	 */
	protected function addToolbar()
	{
		$canDo = ContentHelper::getActions('com_collector', 'item', $this->state->get('filter.collection_id'));
		
		// Get the toolbar object instance
		$toolbar = ToolBar::getInstance('toolbar');
		
		$toolbar->standardButton('restore', Text::_('COM_COLLECTOR_ITEM_HISTORY_RESTORE'))
			->task('itemversions.restore')
			->icon('icon-upload')
			->listCheck(true)
			->buttonClass('btn btn-success');
        
		
		$btnPreview = '<joomla-toolbar-button id="toolbar-preview"><button type="button" id="btn-preview" class="btn btn-info" disabled onclick="openPreview()">'
                . '<span class="icon-eye me-1" aria-hidden="true"></span>'.Text::_('COM_COLLECTOR_ITEM_HISTORY_PREVIEW')
                . '</button></joomla-toolbar-button>';
		$toolbar->appendButton('Custom', $btnPreview, 'preview');
	
		$btnCompare = '<joomla-toolbar-button id="toolbar-compare"><button type="button" id="btn-compare" class="btn btn-primary" disabled onclick="openCompare()">'
                . '<span class="icon-search-plus me-1" aria-hidden="true"></span>'.Text::_('COM_COLLECTOR_ITEM_HISTORY_COMPARE')
                . '</button></joomla-toolbar-button>';
    
		$toolbar->appendButton('Custom', $btnCompare, 'compare');
	
		ToolBarHelper::title(Text::_('COM_COLLECTOR_ITEMS_MANAGER_HISTORY'), 'preview');
		
		if ($canDo->get('core.delete')) {
			ToolBarHelper::deleteList('', 'itemversions.delete','JTOOLBAR_DELETE');
		}
		
		ToolBarHelper::cancel('item.cancel', 'JTOOLBAR_CLOSE');
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
			'h.modified' => Text::_('JGRID_HEADING_ORDERING'),
			'h.id' => Text::_('JGRID_HEADING_ID')
		);
	}
}
?>
