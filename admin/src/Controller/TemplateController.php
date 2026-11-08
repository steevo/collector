<?php
/**
 * Joomla! component Collector
 *
 * @package     Collector
 * @copyright   Copyright (C) 2010 - 2026 Philippe Ousset. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace Steevo\Component\Collector\Administrator\Controller;

\defined('_JEXEC') or die;

use Joomla\CMS\MVC\Controller\FormController;

/**
 * Édition d'un template d'affichage de collection.
 */
class TemplateController extends FormController
{
    protected function allowAdd($data = []): bool
    {
        return $this->app->getIdentity()->authorise('core.create', 'com_collector');
    }

    protected function allowEdit($data = [], $key = 'id'): bool
    {
        return $this->app->getIdentity()->authorise('core.edit', 'com_collector');
    }

    protected function getRedirectToItemAppend($recordId = null, $urlVar = 'id'): string
    {
        $append     = parent::getRedirectToItemAppend($recordId, $urlVar);
        $collection = $this->input->getInt('collection');

        return $collection ? $append . '&collection=' . $collection : $append;
    }

    protected function getRedirectToListAppend(): string
    {
        $append     = parent::getRedirectToListAppend();
        $collection = $this->input->getInt('collection');

        return $collection ? $append . '&collection=' . $collection : $append;
    }
}
