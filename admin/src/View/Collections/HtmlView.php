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

namespace Steevo\Component\Collector\Administrator\View\Collections;

defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\Helper\ContentHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\View\GenericDataException;
use Joomla\CMS\MVC\View\HtmlView as BaseHtmlView;
use Joomla\CMS\Toolbar\Toolbar;
use Joomla\CMS\Toolbar\ToolbarHelper;
use Steevo\Component\Collector\Administrator\Helper\CollectorHelper;

/**
 * HTML Collections View class for the Collector component
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
     * Display the view.
     *
     * @param   string  $tpl  The name of the template file to parse; automatically searches through the template paths.
     *
     * @return  mixed  A string if successful, otherwise an Error object.
     */
	public function display($tpl = null)
	{
		$this->items         = $this->get('Items');
		$this->pagination    = $this->get('Pagination');
		$this->state         = $this->get('State');
		$this->filterForm    = $this->get('FilterForm');
		$this->activeFilters = $this->get('ActiveFilters');
		
		// Check for errors.
        if (count($errors = $this->get('Errors'))) {
            throw new GenericDataException(implode("\n", $errors), 500);
        }
		
		if (!\count($this->items) && $this->isEmptyState = $this->get('IsEmptyState')) {
            $this->setLayout('emptystate');
        }

		// We don't need toolbar in the modal window.
		if ($this->getLayout() !== 'modal') {
			$this->addToolbar();
		}

		parent::display($tpl);
	}

    /**
     * Add the page title and toolbar.
     *
     * @return  void
     *
     * @since   1.6
     */
	protected function addToolbar()
	{
		$canDo = ContentHelper::getActions('com_collector', 'collection', $this->state->get('filter.collection_id'));
        $user  = $this->getCurrentUser();
		
		// Get the toolbar object instance
		$toolbar = ToolBar::getInstance('toolbar');
		
		ToolBarHelper::title(Text::_('COM_COLLECTOR_COL_MANAGER'), 'categories.png');

		if ($canDo->get('core.create') || (count($user->getAuthorisedCategories('com_collector', 'core.create'))) > 0 ) {
			ToolBarHelper::addNew('collection.add');
		}

		if (!$this->isEmptyState && $canDo->get('core.edit.state')) {
            $dropdown = $toolbar->dropdownButton('status-group')
                ->text('JTOOLBAR_CHANGE_STATUS')
                ->toggleSplit(false)
                ->icon('icon-ellipsis-h')
                ->buttonClass('btn btn-action')
                ->listCheck(true);

            $childBar = $dropdown->getChildToolbar();

            $childBar->publish('collections.publish')->listCheck(true);

            $childBar->unpublish('collections.unpublish')->listCheck(true);

            $childBar->archive('collections.archive')->listCheck(true);

            if ($user->authorise('core.admin')) {
                $childBar->checkin('collections.checkin')->listCheck(true);
            }

            if ($this->state->get('filter.published') != -2) {
                $childBar->trash('collections.trash')->listCheck(true);
            }

            // Add a batch button
            /*if (
                $user->authorise('core.create', 'com_collector.collection')
                && $user->authorise('core.edit', 'com_collector.collection')
                && $user->authorise('core.edit.state', 'com_collector.collection')
            ) {
                $childBar->popupButton('batch', 'JTOOLBAR_BATCH')
                    ->popupType('inline')
                    ->textHeader(Text::_('COM_COLLECTOR_BATCH_OPTIONS'))
                    ->url('#joomla-dialog-batch')
                    ->modalWidth('800px')
                    ->modalHeight('fit-content')
                    ->listCheck(true);
            }*/
        }

		// if ($canDo->get('core.edit.state')) {
			
			
			// ToolBarHelper::custom('collections.home', 'featured.png', 'featured_f2.png', 'JDEFAULT', true);
			
			
		// }

		if (!$this->isEmptyState && $this->state->get('filter.published') == -2 && $canDo->get('core.delete')) {
            $toolbar->delete('collections.delete')
                ->text('JTOOLBAR_EMPTY_TRASH')
                ->message('JGLOBAL_CONFIRM_DELETE')
                ->listCheck(true);
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
			'c.ordering' => Text::_('JGRID_HEADING_ORDERING'),
			'c.state' => Text::_('JSTATUS'),
			'c.name' => Text::_('JGLOBAL_TITLE'),
			'c.access' => Text::_('JGRID_HEADING_ACCESS'),
			'c.created_by' => Text::_('JAUTHOR'),
			'c.created' => Text::_('JDATE'),
			'c.id' => Text::_('JGRID_HEADING_ID')
		);
	}
}