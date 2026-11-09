<?php
/**
 * Joomla! component Collector
 *
 * @package     Collector
 * @copyright   Copyright (C) 2010 - 2026 Philippe Ousset. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace Steevo\Component\Collector\Site\View\Collections;

\defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\View\GenericDataException;
use Joomla\CMS\MVC\View\HtmlView as BaseHtmlView;

/**
 * Liste des collections (lien de menu « Liste des collections »).
 */
class HtmlView extends BaseHtmlView
{
    /** @var  \stdClass[] */
    protected $items = [];

    /** @var  \Joomla\Registry\Registry */
    protected $params;

    /** @var  \Joomla\CMS\Object\CMSObject */
    protected $state;

    public function display($tpl = null)
    {
        $this->state  = $this->get('State');
        $this->items  = $this->get('Items') ?: [];
        $this->params = $this->state->get('params');

        if (\count($errors = $this->get('Errors'))) {
            throw new GenericDataException(implode("\n", $errors), 500);
        }

        $this->prepareDocument();

        parent::display($tpl);
    }

    /**
     * Titre de la page, titre affiché et métadonnées, d'après le lien de menu.
     */
    protected function prepareDocument(): void
    {
        $app  = Factory::getApplication();
        $menu = $app->getMenu()->getActive();

        $this->params->def('page_heading', $this->params->get('page_title', $menu ? $menu->title : Text::_('COM_COLLECTOR_COLLECTIONS')));

        $this->setDocumentTitle($this->params->get('page_title', ''));

        if ($this->params->get('menu-meta_description')) {
            $this->getDocument()->setDescription($this->params->get('menu-meta_description'));
        }

        if ($this->params->get('robots')) {
            $this->getDocument()->setMetaData('robots', $this->params->get('robots'));
        }
    }
}
