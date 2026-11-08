<?php
/**
 * Joomla! component Collector
 *
 * @package     Collector
 * @copyright   Copyright (C) 2010 - 2026 Philippe Ousset. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace Steevo\Component\Collector\Administrator\Model;

\defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\MVC\Model\ListModel;
use Joomla\Database\ParameterType;

/**
 * Templates d'affichage d'une collection.
 */
class TemplatesModel extends ListModel
{
    public function __construct($config = [])
    {
        if (empty($config['filter_fields'])) {
            $config['filter_fields'] = [
                'id', 't.id',
                'name', 't.name',
                'client', 't.client',
                'state', 't.state',
            ];
        }

        parent::__construct($config);
    }

    protected function populateState($ordering = 't.name', $direction = 'asc')
    {
        $app = Factory::getApplication();

        // collection partagée avec les autres écrans de Collector
        $collection = $app->input->getInt('collection', 0)
            ?: (int) $app->getUserState('com_collector.current_collection', 0);

        if ($collection <= 0) {
            $collection = $this->getHomeCollection();
        }

        if ($collection !== (int) $app->getUserState($this->context . '.collection', 0)) {
            $app->setUserState($this->context . '.collection', $collection);
            $app->input->set('limitstart', 0);
        }

        $app->setUserState('com_collector.current_collection', $collection);
        $this->setState('filter.collection', $collection);

        parent::populateState($ordering, $direction);
    }

    protected function getListQuery()
    {
        $db         = $this->getDatabase();
        $collection = (int) $this->getState('filter.collection');

        $query = $db->getQuery(true)
            ->select('t.id, t.collection, t.name, t.client, t.state, t.checked_out, t.checked_out_time, t.modified')
            ->from($db->quoteName('#__collector_templates', 't'))
            ->where('t.collection = :collection')
            ->bind(':collection', $collection, ParameterType::INTEGER);

        // template choisi par la collection pour sa vue
        $query->select('CASE WHEN (t.client = 1 AND c.template_list = t.id) OR (t.client = 0 AND c.template_item = t.id) THEN 1 ELSE 0 END AS used')
            ->join('LEFT', $db->quoteName('#__collector', 'c'), 'c.id = t.collection');

        $search = trim((string) $this->getState('filter.search'));

        if ($search !== '') {
            $search = '%' . $search . '%';
            $query->where('t.name LIKE :search')->bind(':search', $search);
        }

        $client = $this->getState('filter.client');

        if ($client !== null && $client !== '') {
            $client = (int) $client;
            $query->where('t.client = :client')->bind(':client', $client, ParameterType::INTEGER);
        }

        $order = $this->getState('list.ordering', 't.name');
        $dir   = $this->getState('list.direction', 'asc');
        $query->order($db->escape($order . ' ' . $dir));

        return $query;
    }

    public function getCollection(): int
    {
        return (int) $this->getState('filter.collection');
    }

    protected function getHomeCollection(): int
    {
        $db = $this->getDatabase();

        return (int) $db->setQuery(
            $db->getQuery(true)
                ->select('id')
                ->from($db->quoteName('#__collector'))
                ->order('home DESC, ordering ASC'),
            0,
            1
        )->loadResult();
    }
}
