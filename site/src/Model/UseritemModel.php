<?php
namespace Steevo\Component\Collector\Site\Model;

defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\Model\BaseDatabaseModel;

class UseritemModel extends BaseDatabaseModel
{
    /**
     * Ajoute un item dans la liste d'un utilisateur.
     * Crée la liste utilisateur si elle n'existe pas encore.
     *
     * @param  int     $userslistId  ID du type de liste (#__collector_userslists)
     * @param  int     $itemId       ID de l'item
     * @param  string  $note         Note optionnelle
     * @return int|false             ID du #__collector_usersitems créé, ou false
     */
    public function add(int $userslistId, int $itemId, string $note = ''): int|false
    {
        $db     = $this->getDbo();
        $userId = (int) $this->getState('user.id');

        if (!$userId || !$userslistId || !$itemId) {
            $this->setError(Text::_('COM_COLLECTOR_USERITEM_INVALID_PARAMS'));
            return false;
        }

        // Vérifier que ce type de liste est actif et accessible
        $db->setQuery(
            $db->getQuery(true)
               ->select('id, access')
               ->from($db->quoteName('#__collector_userslists'))
               ->where($db->quoteName('id')    . ' = ' . $userslistId)
               ->where($db->quoteName('state') . ' = 1')
        );
        $userslist = $db->loadObject();

        if (!$userslist) {
            $this->setError(Text::_('COM_COLLECTOR_USERITEM_LIST_NOT_AVAILABLE'));
            return false;
        }

        // Créer ou récupérer la liste utilisateur
        $userlistId = $this->getOrCreateUserlist($userslistId, $userId);
        if (!$userlistId) {
            return false;
        }

        // Vérifier si l'item est déjà dans la liste
        $db->setQuery(
            $db->getQuery(true)
               ->select('id')
               ->from($db->quoteName('#__collector_usersitems'))
               ->where($db->quoteName('userlist') . ' = ' . $userlistId)
               ->where($db->quoteName('itemid')     . ' = ' . $itemId)
               ->setLimit(1)
        );
        $existing = (int) $db->loadResult();

        if ($existing) {
            return $existing; // Déjà présent
        }

        // Ajouter l'item
        $db->setQuery(
            $db->getQuery(true)
               ->insert($db->quoteName('#__collector_usersitems'))
               ->columns($db->quoteName(['userlist', 'itemid', 'note', 'created']))
               ->values(
				   $userlistId . ', '
				   . $itemId . ', '
				   . $db->quote($note) . ', '
				   . $db->quote(Factory::getDate()->toSql()))
        )->execute();

        return (int) $db->insertid();
    }

    /**
     * Retire un item d'une liste utilisateur.
     */
    public function remove(int $userslistId, int $itemId): bool
    {
        $db     = $this->getDbo();
        $userId = (int) $this->getState('user.id');

        if (!$userId || !$userslistId || !$itemId) {
            $this->setError(Text::_('COM_COLLECTOR_USERITEM_INVALID_PARAMS'));
            return false;
        }

        // Récupérer la liste utilisateur
        $userlistId = $this->getUserlistId($userslistId, $userId);
        if (!$userlistId) {
            return true; // Pas de liste → rien à supprimer
        }

        $db->setQuery(
            $db->getQuery(true)
               ->delete($db->quoteName('#__collector_usersitems'))
               ->where($db->quoteName('userlist') . ' = ' . $userlistId)
               ->where($db->quoteName('itemid')     . ' = ' . $itemId)
        )->execute();

        return true;
    }

    /**
     * Met à jour la note d'un item dans une liste.
     */
    public function updateNote(int $userslistId, int $itemId, string $note): bool
    {
        $db     = $this->getDbo();
        $userId = (int) $this->getState('user.id');

        $userlistId = $this->getUserlistId($userslistId, $userId);
        if (!$userlistId) {
            $this->setError(Text::_('COM_COLLECTOR_USERITEM_NOT_FOUND'));
            return false;
        }

        $db->setQuery(
            $db->getQuery(true)
               ->update($db->quoteName('#__collector_usersitems'))
               ->set($db->quoteName('note') . ' = ' . $db->quote($note))
               ->where($db->quoteName('userlist') . ' = ' . $userlistId)
               ->where($db->quoteName('itemid')     . ' = ' . $itemId)
        )->execute();

        return true;
    }

    /**
     * Retourne le statut de toutes les listes pour un item donné.
     * Utilisé pour initialiser l'état des boutons sur la fiche objet.
     *
     * @return array  [userslist_id => ['in_list' => bool, 'note' => string]]
     */
    public function getItemStatus(int $collection, int $itemId): array
    {
        $db     = $this->getDbo();
        $userId = (int) $this->getState('user.id');

        if (!$userId) return [];

        // Récupérer tous les types de listes actives pour cette collection
        $db->setQuery(
            $db->getQuery(true)
               ->select(['ul.id', 'ul.type', 'ul.name'])
               ->from($db->quoteName('#__collector_userslists', 'ul'))
               ->where($db->quoteName('ul.collection') . ' = ' . $collection)
               ->where($db->quoteName('ul.state')      . ' = 1')
               ->order($db->quoteName('ul.ordering'))
        );
        $userslists = $db->loadObjectList();

        $result = [];
        foreach ($userslists as $userslist) {
            $userlistId = $this->getUserlistId((int) $userslist->id, $userId);
            $inList     = false;
            $note       = '';

            if ($userlistId) {
                $db->setQuery(
                    $db->getQuery(true)
                       ->select(['id', 'note'])
                       ->from($db->quoteName('#__collector_usersitems'))
                       ->where($db->quoteName('userlist') . ' = ' . $userlistId)
                       ->where($db->quoteName('itemid')     . ' = ' . $itemId)
                       ->setLimit(1)
                );
                $row = $db->loadObject();
                if ($row) {
                    $inList = true;
                    $note   = $row->note ?? '';
                }
            }

            $result[$userslist->id] = [
                'type'    => (int) $userslist->type,
                'name'    => $userslist->name,
                'in_list' => $inList,
                'note'    => $note,
            ];
        }

        return $result;
    }
	
	/**
	 * Retourne les utilisateurs ayant un item dans une liste d'un type donné.
	 * Respecte les règles de visibilité (access) et les matchs.
	 */
	public function getUsersWithItem(int $itemId, int $collectionId, int $type): array
	{
		$db          = $this->getDbo();
		$currentUser = (int) $this->getState('user.id');
		$app         = Factory::getApplication();
		$groups      = $app->getIdentity()->getAuthorisedViewLevels();

		$db->setQuery(
			$db->getQuery(true)
			   ->select([
				   'u.id   AS user_id',
				   'u.name AS user_name',
				   'ul.access',
				   'ui.note AS userlist_note',
			   ])
			   ->from($db->quoteName('#__collector_usersitems', 'ui'))
			   ->join('INNER', '#__collector_userlist AS ul ON ul.id = ui.userlist')
			   ->join('INNER',
				   '#__collector_userslists AS uls ON uls.id = ul.userslist'
				   . ' AND uls.collection = ' . $collectionId
				   . ' AND uls.type = ' . $type
				   . ' AND uls.state = 1'
			   )
			   ->join('INNER', '#__users AS u ON u.id = ul.user')
			   ->where('ui.itemid = ' . $itemId)
			   ->where('ul.user != ' . $currentUser)  // exclure soi-même
			   ->where(
				   // access=2 (public) OU access=1 (matchs) si on matche avec cet utilisateur
				   '(ul.access = 2 OR (ul.access = 1 AND EXISTS ('
				   . 'SELECT 1 FROM #__collector_usersitems ui2'
				   . ' JOIN #__collector_userlist ul2 ON ul2.id = ui2.userlist'
				   . ' JOIN #__collector_userslists uls2 ON uls2.id = ul2.userslist'
				   . ' AND uls2.collection = ' . $collectionId
				   . ' AND uls2.type = ' . ($type === 0 ? 1 : 0)
				   . ' WHERE ui2.itemid = ' . $itemId
				   . ' AND ul2.user = ' . $currentUser
				   . ')))'
			   )
			   ->order('u.name ASC')
		);

		return $db->loadObjectList() ?: [];
	}

    // ── Helpers ───────────────────────────────────────────────────────────────

    /**
     * Récupère ou crée la liste d'un utilisateur pour un type donné.
     */
    private function getOrCreateUserlist(int $userslistId, int $userId): int
    {
        $existing = $this->getUserlistId($userslistId, $userId);
        if ($existing) return $existing;

        $db = $this->getDbo();
        $db->setQuery(
            $db->getQuery(true)
               ->insert($db->quoteName('#__collector_userlist'))
               ->columns($db->quoteName(['userslist', 'user', 'access']))
               ->values($userslistId . ', ' . $userId . ', 1')
        )->execute();

        return (int) $db->insertid();
    }

    /**
     * Récupère l'ID de la liste d'un utilisateur pour un type donné.
     */
    private function getUserlistId(int $userslistId, int $userId): int
    {
        $db = $this->getDbo();
        $db->setQuery(
            $db->getQuery(true)
               ->select('id')
               ->from($db->quoteName('#__collector_userlist'))
               ->where($db->quoteName('userslist') . ' = ' . $userslistId)
               ->where($db->quoteName('user')      . ' = ' . $userId)
               ->setLimit(1)
        );
        return (int) $db->loadResult();
    }
}