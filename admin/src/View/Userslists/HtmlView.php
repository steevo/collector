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

namespace Steevo\Component\Collector\Administrator\View\Userslists;

defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\View\GenericDataException;
use Joomla\CMS\MVC\View\HtmlView as BaseHtmlView;
use Joomla\CMS\Toolbar\ToolbarHelper;

/**
 * HTML Userslists View class for the Collector component
 *
 * @package	Collector
 */
class HtmlView extends BaseHtmlView
{
	public  $items;
	public  $pagination;
	public  $state;
    public  $filterForm;
    public  $activeFilters;
    public  $collection;

	/**
	 * Display the view
	 *
	 * @return	void
	 */
	public function display($tpl = null)
	{
		$this->collection 		= $this->get('Collection');
		
		if (!$this->collection) {
            $app = Factory::getApplication();
            $app->enqueueMessage(
                Text::_('COM_COLLECTOR_CONF_COL_FIRST')
                . ' <a href="index.php?option=com_collector&view=collections">'
                . Text::_('COM_COLLECTOR_CONF_COL_MENU') . '</a>',
                'error'
            );
            return;
        }
		
		$this->items         = $this->get('Items');
		$this->pagination    = $this->get('Pagination');
		$this->state         = $this->get('State');
		$this->filterForm    = $this->get('FilterForm');
		$this->activeFilters = $this->get('ActiveFilters');
		
		if (count($errors = $this->get('Errors'))) {
            throw new GenericDataException(implode("\n", $errors), 500);
        }

		// We don't need toolbar in the modal window.
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
		$user = Factory::getApplication()->getIdentity();
		
		ToolBarHelper::title(Text::_('COM_COLLECTOR_USERSLISTS_MANAGER'), 'users');
		
		ToolbarHelper::link(
			'index.php?option=com_collector&view=userliststats&collection=' . (int) $this->collection,
			Text::_('COM_COLLECTOR_USERLISTSTATS_BUTTON'),
			'icon-chart'
		);

		if ($user->authorise('core.edit.state', 'com_collector')) {
			ToolbarHelper::divider();
			ToolbarHelper::publish('userslists.publish');
			ToolbarHelper::unpublish('userslists.unpublish');
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
			'cu.ordering' => Text::_('JGRID_HEADING_ORDERING'),
			'cu.state' => Text::_('JSTATUS'),
			'access_level' => Text::_('JGRID_HEADING_ACCESS'),
			'cu.id' => Text::_('JGRID_HEADING_ID')
		);
	}
}
?>