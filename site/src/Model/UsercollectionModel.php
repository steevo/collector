<?php
namespace Steevo\Component\Collector\Site\Model;

defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\MVC\Model\ListModel;
use Joomla\Registry\Registry;
use Steevo\Component\Collector\Administrator\Classes\CollectorField;

class UsercollectionModel extends ListModel
{
    protected function populateState($ordering = 'i.ordering', $direction = 'ASC'): void
    {
        $app = Factory::getApplication();
		
		$collection = $app->input->getInt('collection');
		$this->setState('collection.id', $collection);
        $typeRaw = $app->input->getString('type', '2');

		if ($typeRaw === 'matching') {
			$this->setState('filter.type', 2);
			$this->setState('filter.show_matching', true);
		} else {
			$this->setState('filter.type', (int) $typeRaw);
			$this->setState('filter.show_matching', false);
		}

        // Utilisateur à afficher — si non passé ? utilisateur courant
        $userId = $app->input->getInt('user', 0)
               ?: $app->getIdentity()->id;
        $this->setState('filter.user', $userId);
		
		// Contexte unique par collection + type + user
		$context = 'com_collector.usercollection.' . $collection
				 . '.' . $app->input->getInt('type', 2)
				 . '.' . $userId;
				 
        $searchAll = $this->getUserStateFromRequest(
			$context . '.filter_search_all', 'filter_search_all'
		);
		$this->setState('filter.filter_search_all', $searchAll);


		// Filtres par champ — lire depuis input ET sauvegarder en user state
		$reset = $app->input->getInt('reset', 0);
		// On récupère les champs directement depuis la DB
		$db = $this->getDbo();
		$db->setQuery(
			$db->getQuery(true)
			   ->select('tablecolumn')
			   ->from($db->quoteName('#__collector_fields'))
			   ->where($db->quoteName('collection') . ' = ' . $collection)
			   ->where($db->quoteName('filter')     . ' = 1')
			   ->where($db->quoteName('state')      . ' = 1')
		);
		$filterCols = $db->loadColumn() ?: [];

		foreach ($filterCols as $col) {
			$key      = 'filterfield_' . $col;
			$stateKey = $context . '.' . $key;

			if ($reset) {
				$app->setUserState($stateKey, '');
				$this->setState('filter.' . $key, '');
			} else {
				$value = $this->getUserStateFromRequest($stateKey, $key);
				$this->setState('filter.' . $key, $value);
			}
		}
		
        $params     = $app->getParams();
		$defaultOrder = $params->get('default_order', 'added');
		$defaultDir   = $params->get('default_order_dir', 'DESC');

		if ($defaultOrder === 'added') {
			$defaultOrder = 'ui.created';
		}


        parent::populateState($defaultOrder, $defaultDir);
    }

    protected function getListQuery()
    {
        $db         = $this->getDbo();
        $app        = Factory::getApplication();
        $collection = $this->getCollection();
        $fields     = $this->getFields();
        $params     = $app->getParams();
        $user       = $app->getIdentity();
        $targetUser = (int) $this->getState('filter.user');
        $type       = (int) $this->getState('filter.type');

        $query = $db->getQuery(true);

        $query->select(
            'i.id, i.alias, i.fulltitle, i.collection, i.ordering, i.state,
             i.hits, i.created, i.created_by, i.access,
             i.publish_up, i.publish_down,
             ui.note AS userlist_note,
			 ui.created AS userlist_created'
        );

        $query->from($db->quoteName('#__collector_items', 'i'));

        // Jointure sur l'historique actif
        $query->join('LEFT',
            '#__collector_items_history_' . $collection->id . ' AS h ON h.item = i.id AND h.state = 1'
        );

        // Jointure sur les listes utilisateur
        $query->join('INNER', '#__collector_usersitems AS ui ON ui.itemid = i.id');
        $query->join('INNER', '#__collector_userlist AS ul ON ul.id = ui.userlist');
        $query->join('INNER',
            '#__collector_userslists AS uls ON uls.id = ul.userslist'
            . ' AND uls.collection = ' . (int) $collection->id
            . ' AND uls.type = ' . $type
            . ' AND uls.state = 1'
        );

        // Filtre utilisateur cible
        $query->where('ul.user = ' . $targetUser);

        // Filtre collection
        $query->where('i.collection = ' . (int) $collection->id);
        $query->where('i.state = 1');
        $query->where('h.state = 1');

        // Filtre accès
        $groups = implode(',', $user->getAuthorisedViewLevels());
        $query->where('i.access IN (' . $groups . ')');

        // Filtre accès liste — si liste privée, seul le propriétaire peut la voir
		if ($targetUser !== $user->id) {
			// Accès à la liste d'un autre utilisateur
			$db->setQuery(
				$db->getQuery(true)
				   ->select('access')
				   ->from($db->quoteName('#__collector_userlist'))
				   ->where($db->quoteName('userslist') . ' = (SELECT id FROM #__collector_userslists WHERE collection = ' . (int) $collection->id . ' AND type = ' . $type . ' AND state = 1 LIMIT 1)')
				   ->where($db->quoteName('user') . ' = ' . $targetUser)
				   ->setLimit(1)
			);
			$targetAccess = (int) $db->loadResult();

			if ($targetAccess === 0) {
				// Privée ? aucun résultat
				$query->where('1 = 0');
			} elseif ($targetAccess === 1 && $type === 2) {
				// Une collection n'est que privée ou publique : l'ancienne valeur « matchs uniquement »
				// (sites mis à jour) vaut privée. Les matchs ne croisent que souhaits et échanges.
				$query->where('1 = 0');
			} elseif ($targetAccess === 1) {
				// Matchs uniquement ? filtrer sur les items en commun
				// Type opposé : si je consulte sa liste type 1 (échange), je cherche mes items type 0 (souhait)
				$myOppositeType = ($type === 1) ? 0 : 1;

				$query->where(
					'i.id IN (
						SELECT ui_me.itemid
						FROM #__collector_usersitems AS ui_me
						JOIN #__collector_userlist AS ul_me ON ul_me.id = ui_me.userlist
						JOIN #__collector_userslists AS uls_me ON uls_me.id = ul_me.userslist
							AND uls_me.collection = ' . (int) $collection->id . '
							AND uls_me.type = ' . $myOppositeType . '
						WHERE ul_me.user = ' . $user->id . '
					)'
				);
			}
			// access=2 ? liste complète, pas de filtre supplémentaire
		}

        // Champs spécifiques de la collection
        foreach ($fields as $field) {
            $field->setQuery($query);
        }

        // Filtres champs
        $itemid = $app->input->getInt('id', 0) . ':' . $app->input->getInt('Itemid', 0);
        $whereSearch = [];
        foreach ($fields as $field) {
            if ($field->_field->filter == 1) {
                $nameFilter  = 'filterfield_' . $field->_field->tablecolumn;
                $filterValue = $this->getState('filter.' . $nameFilter);
                if ($filterValue !== '' && $filterValue !== null) {
                    // filtre actif
					$field->setFilterWhereClause($query, $filterValue, $params);
                }
            }
            $searchAll = $this->getState('filter.filter_search_all');
            if ($searchAll !== '') {
                $whereSearch[] = $field->getSearchWhereClause($query, $searchAll);
            }
        }
        if (!empty($whereSearch)) {
            $query->where('(' . implode(' OR ', $whereSearch) . ')');
        }

        // Tri
        $orderCol  = $this->getState('list.ordering', 'i.ordering');
        $orderDirn = $this->getState('list.direction', 'ASC');
        $query->order($db->escape($orderCol . ' ' . $orderDirn));

        return $query;
    }

    // -- Mise en relation ------------------------------------------------------

    /**
     * Retourne les utilisateurs avec qui des échanges sont possibles.
     * Items que je cherche (type 0) vs items qu'ils ont en échange (type 1)
     * et items que j'ai en échange (type 1) vs items qu'ils cherchent (type 0)
     */
    public function getMatchingUsers(): array
    {
        $db         = $this->getDbo();
        $collection = $this->getCollection();
        $targetUser = (int) $this->getState('filter.user');
        $myType     = (int) $this->getState('filter.type');
        $otherType  = $myType === 0 ? 1 : 0;

        // Uniquement pertinent pour type 0 ou 1
        if (!in_array($myType, [0, 1])) return [];

        $db->setQuery(
            $db->getQuery(true)
               ->select([
                   'ul2.user AS match_user_id',
                   'u.name AS match_user_name',
                   'COUNT(DISTINCT ui1.itemid) AS match_count',
               ])
               ->from($db->quoteName('#__collector_usersitems', 'ui1'))
               ->join('INNER', '#__collector_userlist AS ul1 ON ul1.id = ui1.userlist')
               ->join('INNER',
                   '#__collector_userslists AS uls1 ON uls1.id = ul1.userslist'
                   . ' AND uls1.collection = ' . (int) $collection->id
                   . ' AND uls1.type = ' . $myType
               )
               ->join('INNER', '#__collector_usersitems AS ui2 ON ui2.itemid = ui1.itemid')
               ->join('INNER', '#__collector_userlist AS ul2 ON ul2.id = ui2.userlist')
               ->join('INNER',
                   '#__collector_userslists AS uls2 ON uls2.id = ul2.userslist'
                   . ' AND uls2.collection = ' . (int) $collection->id
                   . ' AND uls2.type = ' . $otherType
               )
               ->join('LEFT', '#__users AS u ON u.id = ul2.user')
               ->where('ul1.user = ' . $targetUser)
               ->where('ul2.user != ' . $targetUser)
			   ->where('ul2.access > 0')
               ->group('ul2.user, u.name')
               ->order('match_count DESC')
        );

        return $db->loadObjectList() ?: [];
    }

    // -- Helpers ---------------------------------------------------------------

    public function getCollection(): ?object
	{
		$collectionId = (int) Factory::getApplication()->input->getInt('collection');
		
		if (!$collectionId) return null;

		$db = $this->getDbo();
		$db->setQuery(
			$db->getQuery(true)
			   ->select('*')
			   ->from($db->quoteName('#__collector'))
			   ->where($db->quoteName('id')    . ' = ' . $collectionId)
			   ->where($db->quoteName('state') . ' = 1')
		);
		return $db->loadObject();
	}

    public function getFields(): array
	{
		$collection = $this->getCollection();
		if (!$collection) return [];

		$user		= Factory::getApplication()->getIdentity();
		
		$db = $this->getDbo();
		$jnow		= Factory::getDate();
		$now		= $jnow->toSql();
		$nullDate	= $db->getNullDate();
		
		$db->setQuery(
			$db->getQuery(true)
			   ->select('f.*, u.name AS author, t.type AS type')
			   ->from($db->quoteName('#__collector_fields') . ' AS f')
			   ->join('LEFT', '#__collector_fields_type AS t ON t.id = f.type')
			   ->join('LEFT', '#__users AS u ON u.id = f.created_by')
			   ->where($db->quoteName('collection') . ' = ' . (int) $collection->id)
			   ->where('( f.created_by = ' . (int) $user->id . ' OR ( f.state = 1 AND ( f.publish_up IS NULL OR f.publish_up = '.$db->Quote($nullDate).' OR f.publish_up <= '.$db->Quote($now).' ) AND ( f.publish_down IS NULL OR f.publish_down = '.$db->Quote($nullDate).' OR f.publish_down >= '.$db->Quote($now).' ) ) )')
			   ->order($db->quoteName('ordering'))
		);
		$fieldRows = $db->loadObjectList() ?: [];

		// Instancier les classes de champs
		$fields = [];
		foreach ($fieldRows as $fieldRow) {
			$registry = new Registry;
			$registry->loadString($fieldRow->attribs);
			$fieldRow->attribs = $registry->toArray();
			$field = CollectorField::getInstance($collection->id, $fieldRow);
			if ($field) {
				$fields[] = $field;
			}
		}
		return $fields;
	}

    /**
     * Retourne les types de listes actifs pour la collection.
     */
    public function getUserslists(): array
    {
        $collection = $this->getCollection();
        if (!$collection) return [];

        $db = $this->getDbo();
        $db->setQuery(
            $db->getQuery(true)
               ->select('id, name, type, access')
               ->from($db->quoteName('#__collector_userslists'))
               ->where($db->quoteName('collection') . ' = ' . (int) $collection->id)
               ->where($db->quoteName('state')      . ' = 1')
               ->order($db->quoteName('ordering'))
        );
        return $db->loadObjectList() ?: [];
    }
	
	public function getCurrentUserlist(): ?object
	{
		$db         = $this->getDbo();
		$userId     = (int) $this->getState('filter.user');
		$type       = (int) $this->getState('filter.type');
		$collection = $this->getCollection();

		// Pas de liste pour un visiteur non connecté (utilisateur 0)
		if (!$collection || $userId <= 0) return null;

		// Récupérer l'ID de la userslist pour ce type
		$db->setQuery(
			$db->getQuery(true)
			   ->select('id')
			   ->from($db->quoteName('#__collector_userslists'))
			   ->where($db->quoteName('collection') . ' = ' . (int) $collection->id)
			   ->where($db->quoteName('type')       . ' = ' . $type)
			   ->where($db->quoteName('state')      . ' = 1')
			   ->setLimit(1)
		);
		$userslistId = (int) $db->loadResult();

		if (!$userslistId) return null;

		// Chercher la userlist de l'utilisateur
		$db->setQuery(
			$db->getQuery(true)
			   ->select('id, access')
			   ->from($db->quoteName('#__collector_userlist'))
			   ->where($db->quoteName('userslist') . ' = ' . $userslistId)
			   ->where($db->quoteName('user')      . ' = ' . $userId)
			   ->setLimit(1)
		);
		$userlist = $db->loadObject();

		// Si elle n'existe pas encore ? la créer avec access=0 (privée par défaut)
		if (!$userlist) {
			$db->setQuery(
				$db->getQuery(true)
				   ->insert($db->quoteName('#__collector_userlist'))
				   ->columns($db->quoteName(['userslist', 'user', 'access']))
				   ->values($userslistId . ', ' . $userId . ', 0')
			)->execute();

			$userlist         = new \stdClass();
			$userlist->id     = (int) $db->insertid();
			$userlist->access = 0;
		}

		return $userlist;
	}
}