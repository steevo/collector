<?php
/**
 * Joomla! component Collector
 *
 * @package     Collector
 * @copyright   Copyright (C) 2010 - 2026 Philippe Ousset. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace Steevo\Component\Collector\Administrator\View\Template;

\defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\View\GenericDataException;
use Joomla\CMS\MVC\View\HtmlView as BaseHtmlView;
use Joomla\CMS\Toolbar\ToolbarHelper;

/**
 * Édition d'un template d'affichage de collection.
 */
class HtmlView extends BaseHtmlView
{
    protected $form;
    protected $item;
    protected $state;
    protected $tags;
    protected $previewItems;

    public function display($tpl = null)
    {
        $this->form  = $this->get('Form');
        $this->item  = $this->get('Item');
        $this->state = $this->get('State');
        $this->tags  = $this->get('Tags');
        $this->previewItems = $this->get('PreviewItems');

        if (\count($errors = $this->get('Errors'))) {
            throw new GenericDataException(implode("\n", $errors), 500);
        }

        $this->addToolbar();

        parent::display($tpl);
    }

    protected function addToolbar(): void
    {
        $app   = Factory::getApplication();
        $user  = $app->getIdentity();
        $isNew = empty($this->item->id);

        $app->input->set('hidemainmenu', true);

        ToolbarHelper::title(Text::_($isNew ? 'COM_COLLECTOR_TEMPLATE_NEW' : 'COM_COLLECTOR_TEMPLATE_EDIT'), 'file-alt');

        if ($user->authorise($isNew ? 'core.create' : 'core.edit', 'com_collector')) {
            ToolbarHelper::apply('template.apply');
            ToolbarHelper::save('template.save');
        }

        if (!$isNew && $user->authorise('core.create', 'com_collector')) {
            ToolbarHelper::save2copy('template.save2copy');
        }

        ToolbarHelper::cancel('template.cancel', $isNew ? 'JTOOLBAR_CANCEL' : 'JTOOLBAR_CLOSE');
    }
}
