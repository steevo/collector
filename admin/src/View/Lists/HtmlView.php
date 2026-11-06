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

namespace Steevo\Component\Collector\Administrator\View\Lists;

defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\Helper\ContentHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\View\GenericDataException;
use Joomla\CMS\MVC\View\HtmlView as BaseHtmlView;
use Joomla\CMS\Toolbar\Toolbar;
use Joomla\CMS\Toolbar\ToolbarHelper;

/**
 * HTML Lists View class for the Collector component
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
	 * Setting the toolbar
	 */
	protected function addToolBar() 
	{
		$canDo = ContentHelper::getActions('com_collector', 'lists');
        $user  = $this->getCurrentUser();
		
		// Get the toolbar object instance
		$toolbar = ToolBar::getInstance('toolbar');
		
		ToolBarHelper::title( Text::_( 'COM_COLLECTOR_LISTS_MANAGER' ), 'sections' );
		
		if ($canDo->get('core.create') || (count($user->getAuthorisedCategories('com_collector', 'core.create'))) > 0 ) {
			ToolBarHelper::addNew('list.add');
		}
		
		if (($canDo->get('core.edit')) || ($this->canDo->get('core.edit.own'))) {
			ToolBarHelper::editList('list.edit');
		}
		
		if ($canDo->get('core.delete')) {
			ToolBarHelper::deleteList('', 'lists.delete');
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
			'c.name' => Text::_('JGLOBAL_TITLE'),
			'c.created_by' => Text::_('JAUTHOR'),
			'c.created' => Text::_('JDATE'),
			'c.id' => Text::_('JGRID_HEADING_ID')
		);
	}
}
?>