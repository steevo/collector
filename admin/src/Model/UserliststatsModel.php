<?php
namespace Steevo\Component\Collector\Administrator\Model;

defined('_JEXEC') or die;

use Joomla\CMS\MVC\Model\BaseDatabaseModel;
use Joomla\CMS\Factory;

class UserliststatsModel extends BaseDatabaseModel
{
    private int $collection;

    public function setCollection(int $collection): void
    {
        $this->collection = $collection;
    }

    /**
     * Indicateurs globaux : nb utilisateurs actifs, nb items par type, répartition accès
     */
    public function getGlobalStats(): object
    {
        $db = $this->getDbo();
        $c  = $this->collection;

        // Nb utilisateurs distincts ayant au moins une liste
        $db->setQuery(
            $db->getQuery(true)
               ->select('COUNT(DISTINCT ul.user)')
               ->from($db->quoteName('#__collector_userlist', 'ul'))
               ->join('INNER', '#__collector_userslists AS uls ON uls.id = ul.userslist AND uls.collection = ' . $c)
        );
        $activeUsers = (int) $db->loadResult();

        // Nb items par type
        $db->setQuery(
            $db->getQuery(true)
               ->select(['uls.type', 'COUNT(*) AS cnt'])
               ->from($db->quoteName('#__collector_usersitems', 'ui'))
               ->join('INNER', '#__collector_userlist AS ul ON ul.id = ui.userlist')
               ->join('INNER', '#__collector_userslists AS uls ON uls.id = ul.userslist AND uls.collection = ' . $c)
               ->group('uls.type')
        );
        $itemsByType = $db->loadAssocList('type', 'cnt');

        // Répartition accès par type
        $db->setQuery(
            $db->getQuery(true)
               ->select(['uls.type', 'ul.access', 'COUNT(DISTINCT ul.id) AS cnt'])
               ->from($db->quoteName('#__collector_userlist', 'ul'))
               ->join('INNER', '#__collector_userslists AS uls ON uls.id = ul.userslist AND uls.collection = ' . $c)
               ->group('uls.type, ul.access')
        );
        $accessByType = $db->loadObjectList();

        return (object) [
            'active_users'  => $activeUsers,
            'items_by_type' => [
                0 => (int) ($itemsByType[0] ?? 0),
                1 => (int) ($itemsByType[1] ?? 0),
                2 => (int) ($itemsByType[2] ?? 0),
            ],
            'access_by_type' => $accessByType,
        ];
    }

    /**
     * Top N utilisateurs par nombre d'items dans un type de liste donné
     */
    public function getTopUsers(int $type, int $limit = 10): array
    {
        $db = $this->getDbo();
        $db->setQuery(
            $db->getQuery(true)
               ->select(['u.id', 'u.name', 'COUNT(*) AS cnt'])
               ->from($db->quoteName('#__collector_usersitems', 'ui'))
               ->join('INNER', '#__collector_userlist AS ul ON ul.id = ui.userlist')
               ->join('INNER', '#__collector_userslists AS uls ON uls.id = ul.userslist'
                   . ' AND uls.collection = ' . $this->collection
                   . ' AND uls.type = ' . $type)
               ->join('INNER', '#__users AS u ON u.id = ul.user')
               ->group('u.id, u.name')
               ->order('cnt DESC')
               ->setLimit($limit)
        );
        return $db->loadObjectList() ?: [];
    }

    /**
     * Top N objets les plus demandés (type=0) ou proposés (type=1)
     */
    public function getTopItems(int $type, int $limit = 10): array
    {
        $db = $this->getDbo();
        $db->setQuery(
            $db->getQuery(true)
               ->select(['i.id', 'i.fulltitle', 'COUNT(*) AS cnt'])
               ->from($db->quoteName('#__collector_usersitems', 'ui'))
               ->join('INNER', '#__collector_userlist AS ul ON ul.id = ui.userlist')
               ->join('INNER', '#__collector_userslists AS uls ON uls.id = ul.userslist'
                   . ' AND uls.collection = ' . $this->collection
                   . ' AND uls.type = ' . $type)
               ->join('INNER', '#__collector_items AS i ON i.id = ui.itemid')
               ->group('i.id, i.fulltitle')
               ->order('cnt DESC')
               ->setLimit($limit)
        );
        return $db->loadObjectList() ?: [];
    }

    /**
     * Objets ayant à la fois des demandes (type=0) et des offres (type=1)
     * = matchs potentiels
     */
    public function getMatchingItems(int $limit = 20): array
    {
        $db = $this->getDbo();
        $c  = $this->collection;

        $db->setQuery(
            $db->getQuery(true)
               ->select([
                   'i.id',
                   'i.fulltitle',
                   'COUNT(DISTINCT want.user) AS want_count',
                   'COUNT(DISTINCT have.user) AS have_count',
               ])
               ->from($db->quoteName('#__collector_items', 'i'))
               ->join('INNER', '(
                    SELECT ui.itemid, ul.user
                    FROM #__collector_usersitems ui
                    JOIN #__collector_userlist ul ON ul.id = ui.userlist
                    JOIN #__collector_userslists uls ON uls.id = ul.userslist
                        AND uls.collection = ' . $c . ' AND uls.type = 0
                ) AS want ON want.itemid = i.id')
               ->join('INNER', '(
                    SELECT ui.itemid, ul.user
                    FROM #__collector_usersitems ui
                    JOIN #__collector_userlist ul ON ul.id = ui.userlist
                    JOIN #__collector_userslists uls ON uls.id = ul.userslist
                        AND uls.collection = ' . $c . ' AND uls.type = 1
                ) AS have ON have.itemid = i.id AND have.user != want.user')
               ->group('i.id, i.fulltitle')
               ->order('(want_count + have_count) DESC')
               ->setLimit($limit)
        );
        return $db->loadObjectList() ?: [];
    }

    /**
     * Activité par mois (basé sur le champ created de usersitems)
     */
    public function getActivityByMonth(int $months = 12): array
    {
        $db = $this->getDbo();
        $db->setQuery(
            $db->getQuery(true)
               ->select(['DATE_FORMAT(ui.created, "%Y-%m") AS month', 'COUNT(*) AS cnt'])
               ->from($db->quoteName('#__collector_usersitems', 'ui'))
               ->join('INNER', '#__collector_userlist AS ul ON ul.id = ui.userlist')
               ->join('INNER', '#__collector_userslists AS uls ON uls.id = ul.userslist AND uls.collection = ' . $this->collection)
               ->where('ui.created >= DATE_SUB(NOW(), INTERVAL ' . $months . ' MONTH)')
               ->group('month')
               ->order('month ASC')
        );
        return $db->loadObjectList() ?: [];
    }
}