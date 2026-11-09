<?php
/**
 * Joomla! component Collector
 *
 * @package     Collector
 * @copyright   Copyright (C) 2010 - 2026 Philippe Ousset. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace Steevo\Component\Collector\Site\Model;

\defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\MVC\Model\ListModel;
use Joomla\Database\ParameterType;

/**
 * Liste des collections publiées et accessibles au visiteur.
 */
class CollectionsModel extends ListModel
{
    protected function populateState($ordering = 'c.name', $direction = 'ASC')
    {
        $app = Factory::getApplication();

        $this->setState('params', $app->getParams());

        // toutes les collections sur une seule page, dans l'ordre alphabétique
        $this->setState('list.start', 0);
        $this->setState('list.limit', 0);
        $this->setState('list.ordering', $ordering);
        $this->setState('list.direction', $direction);
    }

    protected function getListQuery()
    {
        $db     = $this->getDatabase();
        $user   = Factory::getApplication()->getIdentity();
        $groups = $user->getAuthorisedViewLevels();
        $now    = Factory::getDate()->toSql();
        $null   = $db->getNullDate();

        $query = $db->getQuery(true)
            ->select($db->quoteName(['c.id', 'c.name', 'c.alias', 'c.description', 'c.home', 'c.created', 'c.created_by']))
            ->select($db->quoteName('ua.name', 'author_name'))
            ->from($db->quoteName('#__collector', 'c'))
            ->join('LEFT', $db->quoteName('#__users', 'ua') . ' ON ua.id = c.created_by')
            ->where($db->quoteName('c.state') . ' = 1')
            ->whereIn($db->quoteName('c.access'), $groups)
            ->where('(c.publish_up IS NULL OR c.publish_up = :null1 OR c.publish_up <= :now1)')
            ->where('(c.publish_down IS NULL OR c.publish_down = :null2 OR c.publish_down >= :now2)')
            ->bind([':null1', ':null2'], $null)
            ->bind([':now1', ':now2'], $now)
            ->order($db->escape($this->getState('list.ordering', 'c.name')) . ' ' . $db->escape($this->getState('list.direction', 'ASC')));

        return $query;
    }
}
