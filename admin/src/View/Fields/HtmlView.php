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

namespace Steevo\Component\Collector\Administrator\View\Fields;

defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\Helper\ContentHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\View\GenericDataException;
use Joomla\CMS\MVC\View\HtmlView as BaseHtmlView;
use Joomla\CMS\Toolbar\Toolbar;
use Joomla\CMS\Toolbar\ToolbarHelper;

/**
 * HTML Fields View class for the Collector component
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
		$collection 		= $this->get('Collection');
		
		if ( $collection != false )
		{
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

			if (!\count($this->items) && $this->isEmptyState = $this->get('IsEmptyState')) {
				$this->setLayout('emptystate');
			}
			
			// We don't need toolbar in the modal window.
			if ($this->getLayout() !== 'modal') {
				$this->addToolbar();
			}

			parent::display($tpl);
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
		
		ToolBarHelper::title(Text::_('COM_COLLECTOR_FIELDS_MANAGER'), 'menumgr');

		if ($canDo->get('core.create') || (count($user->getAuthorisedCategories('com_collector', 'core.create'))) > 0 ) {
			ToolBarHelper::addNew('field.add');
		}


		if (!$this->isEmptyState && $canDo->get('core.edit.state')) {
			$dropdown = $toolbar->dropdownButton('status-group')
                ->text('JTOOLBAR_CHANGE_STATUS')
                ->toggleSplit(false)
                ->icon('icon-ellipsis-h')
                ->buttonClass('btn btn-action')
                ->listCheck(true);
			
			$childBar = $dropdown->getChildToolbar();
			
			$childBar->publish('fields.publish')->listCheck(true);
			
			$childBar->unpublish('fields.unpublish')->listCheck(true);
			
			// $childBar->unpublish('fields.home')->listCheck(true);
			
			$childBar->archive('fields.archive')->listCheck(true);
			
			if ($user->authorise('core.admin')) {
                $childBar->checkin('fields.checkin')->listCheck(true);
            }
			
			if ($this->state->get('filter.published') != -2) {
                $childBar->trash('fields.trash')->listCheck(true);
            }
		}

		if (!$this->isEmptyState && $this->state->get('filter.published') == -2 && $canDo->get('core.delete')) {
            $toolbar->delete('fields.delete')
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
			'f.ordering' => Text::_('JGRID_HEADING_ORDERING'),
			'f.state' => Text::_('JSTATUS'),
			'f.field' => Text::_('JGLOBAL_TITLE'),
			'access_level' => Text::_('JGRID_HEADING_ACCESS'),
			'f.created_by' => Text::_('JAUTHOR'),
			// 'language' => Text::_('JGRID_HEADING_LANGUAGE'),
			'f.created' => Text::_('JDATE'),
			'f.id' => Text::_('JGRID_HEADING_ID')
		);
	}
}
?>