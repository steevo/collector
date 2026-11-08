<?php
/**
 * Joomla! component Collector
 *
 * @package     Collector
 * @copyright   Copyright (C) 2010 - 2026 Philippe Ousset. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace Steevo\Component\Collector\Administrator\View\Templates;

\defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\View\GenericDataException;
use Joomla\CMS\MVC\View\HtmlView as BaseHtmlView;
use Joomla\CMS\Toolbar\ToolbarHelper;

/**
 * Liste des templates d'affichage d'une collection.
 */
class HtmlView extends BaseHtmlView
{
    public $items;
    public $pagination;
    public $state;
    public $filterForm;
    public $activeFilters;
    public $collection;

    public function display($tpl = null)
    {
        $this->items         = $this->get('Items');
        $this->pagination    = $this->get('Pagination');
        $this->state         = $this->get('State');
        $this->filterForm    = $this->get('FilterForm');
        $this->activeFilters = $this->get('ActiveFilters');
        $this->collection    = $this->get('Collection');

        if (\count($errors = $this->get('Errors'))) {
            throw new GenericDataException(implode("\n", $errors), 500);
        }

        if (!$this->collection) {
            Factory::getApplication()->enqueueMessage(
                Text::_('COM_COLLECTOR_CONF_COL_FIRST')
                . ' <a href="index.php?option=com_collector&view=collections">' . Text::_('COM_COLLECTOR_CONF_COL_MENU') . '</a>',
                'error'
            );
        }

        $this->addToolbar();

        parent::display($tpl);
    }

    protected function addToolbar(): void
    {
        $user = Factory::getApplication()->getIdentity();

        ToolbarHelper::title(Text::_('COM_COLLECTOR_TEMPLATES_MANAGER'), 'file-alt');

        if ($this->collection && $user->authorise('core.create', 'com_collector')) {
            ToolbarHelper::addNew('template.add');
        }

        if ($user->authorise('core.edit.state', 'com_collector')) {
            ToolbarHelper::publish('templates.publish', 'JTOOLBAR_PUBLISH', true);
            ToolbarHelper::unpublish('templates.unpublish', 'JTOOLBAR_UNPUBLISH', true);
            ToolbarHelper::checkin('templates.checkin');
        }

        if ($user->authorise('core.delete', 'com_collector')) {
            ToolbarHelper::deleteList('JGLOBAL_CONFIRM_DELETE', 'templates.delete');
        }
    }
}
