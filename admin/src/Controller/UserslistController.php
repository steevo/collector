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

namespace Steevo\Component\Collector\Administrator\Controller;

defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\MVC\Controller\FormController;
use Joomla\CMS\Router\Route;

class UserslistController extends FormController
{
    protected function allowAdd($data = []): bool
    {
        $user  = Factory::getApplication()->getIdentity();
        $allow = $user->authorise('core.create', 'com_collector');

        return $allow ?? parent::allowAdd();
    }

    protected function allowEdit($data = [], $key = 'id'): bool
    {
        $user      = Factory::getApplication()->getIdentity();
        $recordKey = $data[$key] ?? 0;
        $allow     = $user->authorise('core.edit', 'com_collector');

        if ($allow === null) {
            return parent::allowEdit($data, $key);
        }

        if (!$allow) {
            $allow = $user->authorise('core.edit.own', 'com_collector')
                  && isset($data['created_by'])
                  && $data['created_by'] == $user->id;
        }

        return (bool) $allow;
    }

    protected function getRedirectToItemAppend($recordId = null, $urlVar = 'id'): string
    {
        $append     = parent::getRedirectToItemAppend($recordId, $urlVar);
        $collection = Factory::getApplication()->input->getInt('collection');

        if ($collection) {
            $append .= '&collection=' . $collection;
        }

        return $append;
    }

    protected function getRedirectToListAppend(): string
    {
        $append     = parent::getRedirectToListAppend();
        $collection = Factory::getApplication()->input->getInt('collection');

        if ($collection) {
            $append .= '&collection=' . $collection;
        }

        return $append;
    }
}