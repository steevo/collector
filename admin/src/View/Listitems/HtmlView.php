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

namespace Steevo\Component\Collector\Administrator\View\Listitems;

defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\Helper\ContentHelper;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\View\GenericDataException;
use Joomla\CMS\MVC\View\HtmlView as BaseHtmlView;
use Joomla\CMS\Toolbar\Toolbar;
use Joomla\CMS\Toolbar\ToolbarHelper;

/**
 * HTML Listitems View class for the Collector component
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
	 * Display function
	 */
	public function display($tpl = null)
	{
		$this->items         = $this->get('Items');
		$this->pagination    = $this->get('Pagination');
		$this->state         = $this->get('State');
		$this->filterForm    = $this->get('FilterForm');
		$this->activeFilters = $this->get('ActiveFilters');
		$this->defined		 = $this->state->get('filter.defined');
		
		$this->ordering = array();
		
		// Preprocess the list of items to find ordering divisions.
		foreach ($this->items as $item)
		{
			$this->ordering[$item->parent_id][] = $item->id;
		}
		
		// Check for errors.
        if (count($errors = $this->get('Errors'))) {
            throw new GenericDataException(implode("\n", $errors), 500);
        }
		
		// Levels filter.
		$options	= array();
		$options[]	= HTMLHelper::_('select.option', '1', Text::_('J1'));
		$options[]	= HTMLHelper::_('select.option', '2', Text::_('J2'));
		$options[]	= HTMLHelper::_('select.option', '3', Text::_('J3'));
		$options[]	= HTMLHelper::_('select.option', '4', Text::_('J4'));
		$options[]	= HTMLHelper::_('select.option', '5', Text::_('J5'));
		$options[]	= HTMLHelper::_('select.option', '6', Text::_('J6'));
		$options[]	= HTMLHelper::_('select.option', '7', Text::_('J7'));
		$options[]	= HTMLHelper::_('select.option', '8', Text::_('J8'));
		$options[]	= HTMLHelper::_('select.option', '9', Text::_('J9'));
		$options[]	= HTMLHelper::_('select.option', '10', Text::_('J10'));

		$this->f_levels = $options;
		
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
		$canDo = ContentHelper::getActions('com_collector');
        $user  = $this->getCurrentUser();
		
		// Get the toolbar object instance
		$toolbar = ToolBar::getInstance('toolbar');
		
		ToolBarHelper::title( Text::_( 'COM_COLLECTOR_LISTITEMS_MANAGER' ), 'sections' );
		
		if ($canDo->get('core.create') || (count($user->getAuthorisedCategories('com_collector', 'core.create'))) > 0 ) {
			ToolBarHelper::addNew('listitem.add');
		}
		
		if (($canDo->get('core.edit')) || ($this->canDo->get('core.edit.own'))) {
			ToolBarHelper::editList('listitem.edit');
		}
		
		if ($canDo->get('core.delete')) {
			ToolBarHelper::deleteList('', 'listitems.delete');
		}
		
		ToolBarHelper::divider();
		
		ToolBarHelper::custom('listitems.back','arrow-left-2','','JTOOLBAR_BACK',false);
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
			'c.lft' => Text::_('JGRID_HEADING_ORDERING'),
			'c.content' => Text::_('JGLOBAL_TITLE'),
			'c.id' => Text::_('JGRID_HEADING_ID')
		);
	}
}
?>