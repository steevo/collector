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

namespace Steevo\Component\Collector\Administrator\Table;

defined('_JEXEC') or die;

use Joomla\CMS\Application\ApplicationHelper;
use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Table\Table;
use Joomla\Database\DatabaseDriver;
use Joomla\Utilities\ArrayHelper;

class CollectorUserslistsTable extends Table
{
    public function __construct(DatabaseDriver $db)
    {
        parent::__construct('#__collector_userslists', 'id', $db);
    }

    public function check(): bool
    {
        if (trim($this->name) === '') {
            $this->setError(Text::_('COM_COLLECTOR_WARNING_PROVIDE_VALID_NAME'));
            return false;
        }

        // Définir l'ordre
        if ($this->state < 0) {
            $this->ordering = 0;
        } elseif (empty($this->ordering)) {
            $this->ordering = self::getNextOrder(
                $db->quoteName('collection') . ' = ' . $db->quote($this->collection)
                . ' AND state >= 0'
            );
        }

        return true;
    }

    public function store($updateNulls = false): bool
    {
        $date = Factory::getDate();
        $user = Factory::getApplication()->getIdentity();

        if ($this->id) {
            $this->modified    = $date->toSql();
            $this->modified_by = $user->get('id');
        } else {
            if (!intval($this->created)) {
                $this->created = $date->toSql();
            }
            if (!intval($this->publish_up)) {
                $this->publish_up = $this->created;
            }
            if (empty($this->created_by)) {
                $this->created_by = $user->get('id');
            }
        }

        return parent::store($updateNulls);
    }

    public function delete($oid = null): bool
    {
        if ($oid) {
            $this->load($oid);
            $collection = $this->collection;
        }

        parent::delete($oid);

        if (!empty($collection)) {
            $this->reorder($this->getDbo()->quoteName('collection') . ' = ' . (int) $collection);
        }

        return true;
    }

    public function copy(int $pk, int $collection): bool
    {
        $this->load($pk);
        $this->id         = 0;
        $this->collection = $collection;
        $this->check();
        $this->store();
        return true;
    }
	
	public function publish($pks = null, $state = 1, $userId = 0): bool
	{
		$db = $this->getDbo();
		
		$pks = ArrayHelper::toInteger($pks);
		
		if (empty($pks)) {
			if ($this->id) {
				$pks = [$this->id];
			} else {
				return true;
			}
		}
		
		$query = $db->getQuery(true)
			->update($db->quoteName('#__collector_userslists'))
			->set($db->quoteName('state') . ' = ' . (int) $state)
			->where($db->quoteName('id') . ' IN (' . implode(',', $pks) . ')');
		
		$db->setQuery($query)->execute();
		
		return true;
	}

    /**
     * Initialise les 3 listes par défaut pour une collection
     * si elles n'existent pas encore.
     */
    public function initLists(int $collection): bool
    {
        $db = $this->getDbo();
        $db->setQuery(
            $db->getQuery(true)
               ->select('id')
               ->from($db->quoteName('#__collector_userslists'))
               ->where($db->quoteName('collection') . ' = ' . $collection)
               ->setLimit(1)
        );

        if ($db->loadResult()) {
            return true; // Déjà initialisé
        }

        $defaults = [
            ['type' => 2, 'name' => Text::_('COM_COLLECTOR_USERLIST_COLLECTION')],
            ['type' => 0, 'name' => Text::_('COM_COLLECTOR_USERLIST_WISH_LIST')],
            ['type' => 1, 'name' => Text::_('COM_COLLECTOR_USERLIST_SWAP_LIST')],
        ];

        foreach ($defaults as $default) {
            $this->id         = 0;
            $this->collection = $collection;
            $this->state      = 0;
            $this->ordering   = 0;
            $this->access     = 1;
            $this->type       = $default['type'];
            $this->name       = $default['name'];
            $this->check();
            $this->store();
        }

        return true;
    }
}