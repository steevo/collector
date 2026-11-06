<?php
namespace Steevo\Component\Collector\Administrator\Model;

defined('_JEXEC') or die;

use Joomla\CMS\MVC\Model\BaseDatabaseModel;
use Joomla\CMS\Language\Text;
use Steevo\Component\Collector\Administrator\Table\CollectorItemsTable;

class ToolsModel extends BaseDatabaseModel
{
    const EXPORT_DIR = JPATH_ADMINISTRATOR . '/components/com_collector/exports/';

	/**
     * Régénère fulltitle et alias pour tous les items d'une collection
     * dont fulltitle est vide ou null.
     *
     * @return int  Nombre d'items corrigés
     */
    public function regenerateFulltitlesBatch(int $collection, string $mode, int $afterId, int $limit = 20): array
	{
		$db = $this->getDbo();

		$query = $db->getQuery(true)
			->select('id')
			->from($db->quoteName('#__collector_items'))
			->where($db->quoteName('collection') . ' = ' . $collection);

		if ($mode === 'empty') {
			$query->where('(fulltitle IS NULL OR fulltitle = \'\')')
				  ->order('id ASC');
		} else {
			$query->where('id > ' . $afterId)
				  ->order('id ASC');
		}

		$query->setLimit($limit);
		$db->setQuery($query);
		$itemIds = $db->loadColumn() ?: [];

		$fixed  = 0;
		$lastId = $afterId;

		foreach ($itemIds as $itemId) {
			$table = new CollectorItemsTable($db);
			$table->collection = $collection;
			$table->initVersion($collection);
			$table->load($itemId);
			$table->loadVersion($collection, $itemId);

			if ($table->check()) {
				$db->setQuery(
					$db->getQuery(true)
					   ->update($db->quoteName('#__collector_items'))
					   ->set($db->quoteName('fulltitle') . ' = ' . $db->quote($table->fulltitle))
					   ->set($db->quoteName('alias')     . ' = ' . $db->quote($table->alias))
					   ->where($db->quoteName('id')      . ' = ' . $itemId)
				)->execute();

				$fixed++;
			}

			$lastId = $itemId;
		}

		return [
			'processed' => count($itemIds),
			'fixed'     => $fixed,
			'last_id'   => $lastId,
			'done'      => count($itemIds) < $limit,
		];
	}
	
	public function countTotalItems(int $collection): int
	{
		$db = $this->getDbo();
		$db->setQuery(
			$db->getQuery(true)
			   ->select('COUNT(*)')
			   ->from($db->quoteName('#__collector_items'))
			   ->where($db->quoteName('collection') . ' = ' . $collection)
		);
		return (int) $db->loadResult();
	}

    /**
     * Retourne le nombre d'items avec fulltitle vide pour une collection.
     */
    public function countEmptyFulltitles(int $collection): int
    {
        $db = $this->getDbo();
        $db->setQuery(
            $db->getQuery(true)
               ->select('COUNT(*)')
               ->from($db->quoteName('#__collector_items'))
               ->where($db->quoteName('collection') . ' = ' . $collection)
               ->where('(fulltitle IS NULL OR fulltitle = \'\')')
        );
        return (int) $db->loadResult();
    }
	
	/**
	 * Compte les entrées #__collector_usersitems pointant vers
	 * un item qui n'existe plus dans la collection.
	 */
	public function countOrphanUsersitems(int $collection): int
	{
		$db = $this->getDbo();
		$db->setQuery(
			$db->getQuery(true)
			   ->select('COUNT(*)')
			   ->from($db->quoteName('#__collector_usersitems', 'ui'))
			   ->join('INNER', '#__collector_userlist AS ul ON ul.id = ui.userlist')
			   ->join('INNER', '#__collector_userslists AS uls ON uls.id = ul.userslist AND uls.collection = ' . $collection)
			   ->join('LEFT',  '#__collector_items AS i ON i.id = ui.itemid')
			   ->where('i.id IS NULL')
		);
		return (int) $db->loadResult();
	}

	/**
	 * Supprime les entrées #__collector_usersitems orphelines.
	 *
	 * @return int  Nombre d'entrées supprimées
	 */
	public function cleanOrphanUsersitems(int $collection): int
	{
		$db = $this->getDbo();

		$db->setQuery(
			$db->getQuery(true)
			   ->select('ui.id')
			   ->from($db->quoteName('#__collector_usersitems', 'ui'))
			   ->join('INNER', '#__collector_userlist AS ul ON ul.id = ui.userlist')
			   ->join('INNER', '#__collector_userslists AS uls ON uls.id = ul.userslist AND uls.collection = ' . $collection)
			   ->join('LEFT',  '#__collector_items AS i ON i.id = ui.itemid')
			   ->where('i.id IS NULL')
		);
		$ids = $db->loadColumn() ?: [];

		if (empty($ids)) return 0;

		$db->setQuery(
			$db->getQuery(true)
			   ->delete($db->quoteName('#__collector_usersitems'))
			   ->where($db->quoteName('id') . ' IN (' . implode(',', $ids) . ')')
		)->execute();

		return count($ids);
	}
	
	/**
	 * Compte les items sans version active (state=1) dans leur historique.
	 */
	public function countItemsWithoutActiveVersion(int $collection): int
	{
		$db = $this->getDbo();
		$db->setQuery(
			$db->getQuery(true)
			   ->select('COUNT(*)')
			   ->from($db->quoteName('#__collector_items', 'i'))
			   ->where($db->quoteName('i.collection') . ' = ' . $collection)
			   ->where('NOT EXISTS (
				   SELECT 1 FROM #__collector_items_history_' . $collection . ' h
				   WHERE h.item = i.id AND h.state = 1
			   )')
		);
		return (int) $db->loadResult();
	}

	/**
	 * Active la version la plus récente pour les items sans version active.
	 *
	 * @return int  Nombre d'items corrigés
	 */
	public function fixItemsWithoutActiveVersion(int $collection): int
	{
		$db = $this->getDbo();

		$db->setQuery(
			$db->getQuery(true)
			   ->select('i.id')
			   ->from($db->quoteName('#__collector_items', 'i'))
			   ->where($db->quoteName('i.collection') . ' = ' . $collection)
			   ->where('NOT EXISTS (
				   SELECT 1 FROM #__collector_items_history_' . $collection . ' h
				   WHERE h.item = i.id AND h.state = 1
			   )')
		);
		$itemIds = $db->loadColumn() ?: [];

		$fixed = 0;
		foreach ($itemIds as $itemId) {
			// Trouver la version la plus récente (peu importe son state)
			$db->setQuery(
				$db->getQuery(true)
				   ->select('id')
				   ->from($db->quoteName('#__collector_items_history_' . $collection))
				   ->where($db->quoteName('item') . ' = ' . $itemId)
				   ->order('modified DESC, id DESC')
				   ->setLimit(1)
			);
			$versionId = (int) $db->loadResult();

			if (!$versionId) {
				continue; // aucune version du tout — cas distinct, non traité ici
			}

			$table = new CollectorItemsTable($db);
			$table->collection = $collection;
			$table->initVersion($collection);
			$table->load($itemId);
			$table->loadVersion($collection, $itemId, $versionId);

			if ($table->activateVersion($versionId)) {
				$fixed++;
			}
		}

		return $fixed;
	}
	
	/**
	 * Compte les items dont to_validate ne correspond pas
	 * au nombre réel de versions en attente (state=0).
	 */
	public function countToValidateMismatch(int $collection): int
	{
		$db = $this->getDbo();
		$db->setQuery(
			$db->getQuery(true)
			   ->select('COUNT(*)')
			   ->from($db->quoteName('#__collector_items', 'i'))
			   ->where($db->quoteName('i.collection') . ' = ' . $collection)
			   ->where('i.to_validate != (
				   SELECT COUNT(*) FROM #__collector_items_history_' . $collection . ' h
				   WHERE h.item = i.id AND h.state = 0
			   )')
		);
		return (int) $db->loadResult();
	}

	/**
	 * Recalcule to_validate pour tous les items d'une collection.
	 *
	 * @return int  Nombre d'items corrigés
	 */
	public function fixToValidateCounts(int $collection): int
	{
		$db = $this->getDbo();

		$db->setQuery(
			$db->getQuery(true)
			   ->select('i.id')
			   ->from($db->quoteName('#__collector_items', 'i'))
			   ->where($db->quoteName('i.collection') . ' = ' . $collection)
			   ->where('i.to_validate != (
				   SELECT COUNT(*) FROM #__collector_items_history_' . $collection . ' h
				   WHERE h.item = i.id AND h.state = 0
			   )')
		);
		$itemIds = $db->loadColumn() ?: [];

		if (empty($itemIds)) return 0;

		// UPDATE en une seule requête via sous-requête corrélée
		$db->setQuery(
			'UPDATE ' . $db->quoteName('#__collector_items') . ' i
			 SET i.to_validate = (
				 SELECT COUNT(*) FROM ' . $db->quoteName('#__collector_items_history_' . $collection) . ' h
				 WHERE h.item = i.id AND h.state = 0
			 )
			 WHERE i.id IN (' . implode(',', $itemIds) . ')'
		)->execute();

		return count($itemIds);
	}
	
	/**
	 * Compte les versions archivées (state=2) existantes
	 * alors que save_history=0 pour la collection.
	 */
	public function countArchivedVersionsToClean(int $collection): int
	{
		$params = \Joomla\CMS\Component\ComponentHelper::getParams('com_collector');
		if ($params->get('save_history', 1)) {
			return 0;
		}

		$db = $this->getDbo();
		$db->setQuery(
			$db->getQuery(true)
			   ->select('COUNT(*)')
			   ->from($db->quoteName('#__collector_items_history_' . $collection))
			   ->where('state NOT IN (0, 1)')
		);
		return (int) $db->loadResult();
	}

	/**
	 * Supprime toutes les versions archivées (state=2) d'une collection.
	 *
	 * @return int  Nombre de versions supprimées
	 */
	public function cleanArchivedVersions(int $collection): int
	{
		$db = $this->getDbo();

		$db->setQuery(
			$db->getQuery(true)
			   ->select('COUNT(*)')
			   ->from($db->quoteName('#__collector_items_history_' . $collection))
			   ->where('state NOT IN (0, 1)')
		);
		$count = (int) $db->loadResult();

		if ($count > 0) {
			$db->setQuery(
				$db->getQuery(true)
				   ->delete($db->quoteName('#__collector_items_history_' . $collection))
				   ->where('state NOT IN (0, 1)')
			)->execute();
		}

		return $count;
	}
	
	/**
	 * Compte les sessions d'import abandonnées (non mises à jour depuis X heures).
	 */
	public function countStaleImportSessions(int $collection, int $hoursThreshold = 24): int
	{
		$db = $this->getDbo();
		$db->setQuery(
			$db->getQuery(true)
			   ->select('COUNT(*)')
			   ->from($db->quoteName('#__collector_import_sessions'))
			   ->where($db->quoteName('collection') . ' = ' . $collection)
			   ->where('updated < DATE_SUB(NOW(), INTERVAL ' . $hoursThreshold . ' HOUR)')
		);
		return (int) $db->loadResult();
	}

	/**
	 * Supprime les sessions d'import abandonnées.
	 *
	 * @return int  Nombre de sessions supprimées
	 */
	public function cleanStaleImportSessions(int $collection, int $hoursThreshold = 24): int
	{
		$db = $this->getDbo();

		$db->setQuery(
			$db->getQuery(true)
			   ->select('COUNT(*)')
			   ->from($db->quoteName('#__collector_import_sessions'))
			   ->where($db->quoteName('collection') . ' = ' . $collection)
			   ->where('updated < DATE_SUB(NOW(), INTERVAL ' . $hoursThreshold . ' HOUR)')
		);
		$count = (int) $db->loadResult();

		if ($count > 0) {
			$db->setQuery(
				$db->getQuery(true)
				   ->delete($db->quoteName('#__collector_import_sessions'))
				   ->where($db->quoteName('collection') . ' = ' . $collection)
				   ->where('updated < DATE_SUB(NOW(), INTERVAL ' . $hoursThreshold . ' HOUR)')
			)->execute();
		}

		return $count;
	}
	
	/**
	 * Compte les entrées d'historique pointant vers un item supprimé.
	 */
	public function countOrphanVersions(int $collection): int
	{
		$db = $this->getDbo();
		$db->setQuery(
			$db->getQuery(true)
			   ->select('COUNT(*)')
			   ->from($db->quoteName('#__collector_items_history_' . $collection, 'h'))
			   ->join('LEFT', '#__collector_items AS i ON i.id = h.item')
			   ->where('i.id IS NULL')
		);
		return (int) $db->loadResult();
	}

	/**
	 * Supprime les entrées d'historique orphelines.
	 *
	 * @return int  Nombre de versions supprimées
	 */
	public function cleanOrphanVersions(int $collection): int
	{
		$db = $this->getDbo();

		$count = $this->countOrphanVersions($collection);

		if ($count > 0) {
			$db->setQuery(
				'DELETE h FROM ' . $db->quoteName('#__collector_items_history_' . $collection) . ' h
				 LEFT JOIN ' . $db->quoteName('#__collector_items') . ' i ON i.id = h.item
				 WHERE i.id IS NULL'
			)->execute();
		}

		return $count;
	}
	
	/**
	 * Retourne la liste des items dont la collection n'existe plus.
	 */
	public function getItemsWithInvalidCollection(): array
	{
		$db = $this->getDbo();
		$db->setQuery(
			$db->getQuery(true)
			   ->select(['i.id', 'i.fulltitle', 'i.collection'])
			   ->from($db->quoteName('#__collector_items', 'i'))
			   ->join('LEFT', '#__collector AS c ON c.id = i.collection')
			   ->where('c.id IS NULL')
			   ->order('i.collection, i.id')
		);
		return $db->loadObjectList() ?: [];
	}
	
	/**
	 * Retourne les valeurs definedcontent (feuilles) qui ne sont référencées
	 * par aucun item d'aucune collection utilisant ce groupe de valeurs.
	 *
	 * Limité aux feuilles (lft+1 = rgt) pour éviter les faux positifs sur les
	 * nœuds parents qui regroupent des enfants utilisés.
	 */
	public function getOrphanDefinedContent(): array
	{
		$db = $this->getDbo();

		// 1. Récupérer tous les champs de type "define" avec leur groupe et leur collection
		$db->setQuery(
			$db->getQuery(true)
			   ->select(['f.collection', 'f.tablecolumn', 'f.attribs'])
			   ->from($db->quoteName('#__collector_fields', 'f'))
			   ->where($db->quoteName('f.type') . ' = (
				   SELECT id FROM #__collector_fields_type WHERE type = ' . $db->quote('define') . '
			   )')
		);
		$defineFields = $db->loadObjectList() ?: [];

		// 2. Pour chaque groupe "defined", collecter les IDs utilisés
		$usedByGroup = []; // [defined_group_id => [content_id, ...]]

		foreach ($defineFields as $field) {
			$attribs = json_decode($field->attribs ?? '{}', true) ?: [];
			$groupId = (int) ($attribs['list'] ?? 0);
			if (!$groupId) continue;

			$historyTable = '#__collector_items_history_' . (int) $field->collection;

			// Vérifier que la table existe
			try {
				$db->setQuery(
					$db->getQuery(true)
					   ->select('DISTINCT ' . $db->quoteName($field->tablecolumn))
					   ->from($db->quoteName($historyTable))
					   ->where($db->quoteName($field->tablecolumn) . ' IS NOT NULL')
					   ->where($db->quoteName($field->tablecolumn) . ' != \'\'')
				);
				$usedIds = $db->loadColumn() ?: [];
			} catch (\Exception $e) {
				continue; // table inexistante ou colonne absente
			}

			if (!isset($usedByGroup[$groupId])) {
				$usedByGroup[$groupId] = [];
			}
			$usedByGroup[$groupId] = array_merge($usedByGroup[$groupId], $usedIds);
		}

		// 3. Pour chaque groupe utilisé par au moins un champ, trouver les feuilles non référencées
		$orphans = [];
		foreach ($usedByGroup as $groupId => $usedIds) {
			$usedIds = array_unique(array_filter($usedIds, fn($v) => $v !== '' && $v !== null));

			$query = $db->getQuery(true)
				->select(['dc.id', 'dc.content', 'd.name AS group_name'])
				->from($db->quoteName('#__collector_defined_content', 'dc'))
				->join('INNER', '#__collector_defined AS d ON d.id = dc.defined')
				->where('dc.defined = ' . $groupId)
				->where('dc.lft + 1 = dc.rgt'); // feuilles uniquement

			if (!empty($usedIds)) {
				$query->where('dc.id NOT IN (' . implode(',', array_map('intval', $usedIds)) . ')');
			}

			$db->setQuery($query);
			$rows = $db->loadObjectList() ?: [];
			$orphans = array_merge($orphans, $rows);
		}

		return $orphans;
	}
	
	/**
	 * Retourne les statistiques des tables pour une collection.
	 */
	public function getTableStats(int $collection): array
	{
		$db     = $this->getDbo();
		$prefix = $db->getPrefix();

		$db->setQuery('SELECT DATABASE()');
		$dbName = $db->loadResult();

		$tables = [
			'#__collector_items'                         => Text::_('COM_COLLECTOR_TOOLS_STATS_TABLE_ITEMS'),
			'#__collector_items_history_' . $collection  => Text::_('COM_COLLECTOR_TOOLS_STATS_TABLE_HISTORY'),
			'#__collector_usersitems'                    => Text::_('COM_COLLECTOR_TOOLS_STATS_TABLE_USERSITEMS'),
			'#__collector_userlist'                      => Text::_('COM_COLLECTOR_TOOLS_STATS_TABLE_USERLIST'),
			'#__collector_userslists'                    => Text::_('COM_COLLECTOR_TOOLS_STATS_TABLE_USERSLISTS'),
			'#__collector_import_sessions'               => Text::_('COM_COLLECTOR_TOOLS_STATS_TABLE_IMPORT'),
			'#__collector_defined_content'               => Text::_('COM_COLLECTOR_TOOLS_STATS_TABLE_DEFINED'),
		];

		$result = [];
		foreach ($tables as $table => $label) {
			$realName = str_replace('#__', $prefix, $table);

			// Vérifier que la table existe
			$db->setQuery(
				'SELECT COUNT(*) FROM information_schema.TABLES
				 WHERE table_schema = ' . $db->quote($dbName) . '
				 AND table_name = ' . $db->quote($realName)
			);
			$exists = (bool) $db->loadResult();

			$rows    = null;
			$size_kb = null;

			if ($exists) {
				// Comptage réel
				$db->setQuery('SELECT COUNT(*) FROM ' . $db->quoteName($realName));
				$rows = (int) $db->loadResult();

				// Taille réelle
				$db->setQuery(
					'SELECT ROUND((data_length + index_length) / 1024, 2)
					 FROM information_schema.TABLES
					 WHERE table_schema = ' . $db->quote($dbName) . '
					 AND table_name = ' . $db->quote($realName)
				);
				$size_kb = (float) $db->loadResult();
			}

			$result[] = (object) [
				'label'   => $label,
				'table'   => $realName,
				'rows'    => $rows,
				'size_kb' => $size_kb,
				'exists'  => $exists,
			];
		}

		return $result;
	}

	/**
	 * Retourne le nombre d'items par état pour une collection.
	 */
	public function getItemsCountByState(int $collection): array
	{
		$db = $this->getDbo();
		$db->setQuery(
			$db->getQuery(true)
			   ->select(['state', 'COUNT(*) AS cnt'])
			   ->from($db->quoteName('#__collector_items'))
			   ->where($db->quoteName('collection') . ' = ' . $collection)
			   ->group('state')
			   ->order('state')
		);
		return $db->loadAssocList('state') ?: [];
	}
	
	/**
	 * Génère un export SQL.
	 *
	 * @param  int     $collection  0 = global
	 * @param  string  $mode        'collection' | 'collection_with_lists' | 'global'
	 * @return string  Contenu SQL
	 */
	public function exportSql(int $collection, string $mode): string
	{
		$db     = $this->getDbo();
		$prefix = $db->getPrefix();
		$lines  = [];

		// En-tête
		$lines[] = '-- Collector Export';
		$lines[] = '-- Date    : ' . date('Y-m-d H:i:s');
		$lines[] = '-- Mode    : ' . $mode;
		if ($collection) {
			$lines[] = '-- Collection : ' . $collection;
		}
		$lines[] = '-- ';
		$lines[] = 'SET FOREIGN_KEY_CHECKS=0;';
		$lines[] = '';

		switch ($mode) {
			case 'collection':
				$lines = array_merge($lines,
					$this->exportTable('#__collector_items', ['collection' => $collection]),
					$this->exportTable('#__collector_items_history_' . $collection)
				);
				break;

			case 'collection_with_lists':
				$lines = array_merge($lines,
					$this->exportTable('#__collector_items', ['collection' => $collection]),
					$this->exportTable('#__collector_items_history_' . $collection),
					$this->exportTable('#__collector_userslists', ['collection' => $collection]),
					$this->exportUserlists($collection),
					$this->exportUsersitems($collection)
				);
				break;

			case 'global':
				$lines = array_merge($lines,
					$this->exportTable('#__collector'),
					$this->exportTable('#__collector_fields_type'),
					$this->exportTable('#__collector_fields'),
					$this->exportTable('#__collector_defined'),
					$this->exportTable('#__collector_defined_content'),
					$this->exportTable('#__collector_templates'),
					$this->exportTable('#__collector_userslists')
				);

				// Toutes les collections
				$db->setQuery(
					$db->getQuery(true)
					   ->select('id')
					   ->from($db->quoteName('#__collector'))
				);
				$collectionIds = $db->loadColumn() ?: [];

				foreach ($collectionIds as $cid) {
					$lines = array_merge($lines,
						$this->exportTable('#__collector_items', ['collection' => $cid]),
						$this->exportTable('#__collector_items_history_' . $cid)
					);
				}

				$lines = array_merge($lines,
					$this->exportTable('#__collector_userlist'),
					$this->exportTable('#__collector_usersitems'),
					$this->exportTable('#__collector_import_sessions'),
					$this->exportTable('#__collector_import_config')
				);
				break;
		}

		$lines[] = '';
		$lines[] = 'SET FOREIGN_KEY_CHECKS=1;';

		return implode("\n", $lines);
	}

	/**
	 * Génère le SQL d'export pour une table avec filtre optionnel.
	 */
	private function exportTable(string $table, array $filters = []): array
	{
		$db       = $this->getDbo();
		$prefix   = $db->getPrefix();
		$realName = str_replace('#__', $prefix, $table);
		$lines    = [];

		$lines[] = '-- Table : ' . $realName;
		$lines[] = 'TRUNCATE TABLE ' . $db->quoteName($realName) . ';';

		$query = $db->getQuery(true)->select('*')->from($db->quoteName($realName));
		foreach ($filters as $col => $val) {
			$query->where($db->quoteName($col) . ' = ' . (int) $val);
		}

		$db->setQuery($query);
		$rows = $db->loadAssocList() ?: [];

		if (empty($rows)) {
			$lines[] = '';
			return $lines;
		}

		// INSERT par lots de 100
		$chunks = array_chunk($rows, 100);
		foreach ($chunks as $chunk) {
			$cols    = array_keys($chunk[0]);
			$colList = implode(', ', array_map(fn($c) => $db->quoteName($c), $cols));
			$values  = [];

			foreach ($chunk as $row) {
				$vals = array_map(function ($v) use ($db) {
					if ($v === null) return 'NULL';
					if (is_numeric($v)) return $v;
					return $db->quote($v);
				}, array_values($row));
				$values[] = '(' . implode(', ', $vals) . ')';
			}

			$lines[] = 'INSERT INTO ' . $db->quoteName($realName)
				. ' (' . $colList . ') VALUES'
				. "\n" . implode(",\n", $values) . ';';
		}

		$lines[] = '';
		return $lines;
	}

	/**
	 * Export de #__collector_userlist filtrée par collection.
	 */
	private function exportUserlists(int $collection): array
	{
		$db = $this->getDbo();
		$db->setQuery(
			$db->getQuery(true)
			   ->select('ul.*')
			   ->from($db->quoteName('#__collector_userlist', 'ul'))
			   ->join('INNER', '#__collector_userslists AS uls ON uls.id = ul.userslist AND uls.collection = ' . $collection)
		);
		$rows = $db->loadAssocList() ?: [];

		if (empty($rows)) return [];

		$prefix   = $db->getPrefix();
		$realName = $prefix . 'collector_userlist';
		$lines    = ['-- Table : ' . $realName];

		$chunks = array_chunk($rows, 100);
		foreach ($chunks as $chunk) {
			$cols    = array_keys($chunk[0]);
			$colList = implode(', ', array_map(fn($c) => $db->quoteName($c), $cols));
			$values  = [];

			foreach ($chunk as $row) {
				$vals = array_map(function ($v) use ($db) {
					if ($v === null) return 'NULL';
					if (is_numeric($v)) return $v;
					return $db->quote($v);
				}, array_values($row));
				$values[] = '(' . implode(', ', $vals) . ')';
			}

			$lines[] = 'INSERT INTO ' . $db->quoteName($realName)
				. ' (' . $colList . ') VALUES'
				. "\n" . implode(",\n", $values) . ';';
		}

		$lines[] = '';
		return $lines;
	}

	/**
	 * Export de #__collector_usersitems filtrés par collection.
	 */
	private function exportUsersitems(int $collection): array
	{
		$db = $this->getDbo();
		$db->setQuery(
			$db->getQuery(true)
			   ->select('ui.*')
			   ->from($db->quoteName('#__collector_usersitems', 'ui'))
			   ->join('INNER', '#__collector_userlist AS ul ON ul.id = ui.userlist')
			   ->join('INNER', '#__collector_userslists AS uls ON uls.id = ul.userslist AND uls.collection = ' . $collection)
		);
		$rows = $db->loadAssocList() ?: [];

		if (empty($rows)) return [];

		$prefix   = $db->getPrefix();
		$realName = $prefix . 'collector_usersitems';
		$lines    = ['-- Table : ' . $realName];

		$chunks = array_chunk($rows, 100);
		foreach ($chunks as $chunk) {
			$cols    = array_keys($chunk[0]);
			$colList = implode(', ', array_map(fn($c) => $db->quoteName($c), $cols));
			$values  = [];

			foreach ($chunk as $row) {
				$vals = array_map(function ($v) use ($db) {
					if ($v === null) return 'NULL';
					if (is_numeric($v)) return $v;
					return $db->quote($v);
				}, array_values($row));
				$values[] = '(' . implode(', ', $vals) . ')';
			}

			$lines[] = 'INSERT INTO ' . $db->quoteName($realName)
				. ' (' . $colList . ') VALUES'
				. "\n" . implode(",\n", $values) . ';';
		}

		$lines[] = '';
		return $lines;
	}
	
	/**
	 * Exécute un fichier SQL importé.
	 *
	 * @param  string  $sql         Contenu du fichier SQL
	 * @param  string  $mode        'replace' (TRUNCATE+INSERT) | 'merge' (INSERT IGNORE)
	 * @return array   ['success' => bool, 'queries' => int, 'error' => string]
	 */
	public function importSql(string $sql, string $mode): array
	{
		$db = $this->getDbo();

		// En mode merge, remplacer INSERT INTO par INSERT IGNORE INTO
		if ($mode === 'merge') {
			$sql = preg_replace('/INSERT INTO\s+/i', 'INSERT IGNORE INTO ', $sql);
			// Supprimer les TRUNCATE TABLE
			$sql = preg_replace('/TRUNCATE TABLE[^\n]+;\n?/i', '', $sql);
		}

		// Découper en requêtes individuelles
		$queries = array_filter(
			array_map('trim', explode(';', $sql)),
			fn($q) => !empty($q) && !str_starts_with(ltrim($q), '--')
		);

		$count = 0;
		try {
			foreach ($queries as $query) {
				if (empty(trim($query))) continue;
				$db->setQuery($query)->execute();
				$count++;
			}
		} catch (\Exception $e) {
			return [
				'success' => false,
				'queries' => $count,
				'error'   => $e->getMessage(),
			];
		}

		return [
			'success' => true,
			'queries' => $count,
			'error'   => '',
		];
	}
	
	/**
	 * Sauvegarde le SQL dans un fichier et retourne le nom du fichier.
	 */
	public function saveExportFile(string $sql, string $mode, int $collection): string
	{
		if (!is_dir(self::EXPORT_DIR)) {
			mkdir(self::EXPORT_DIR, 0755, true);
			// Protéger le dossier
			file_put_contents(self::EXPORT_DIR . '.htaccess', 'Deny from all');
			file_put_contents(self::EXPORT_DIR . 'index.html', '');
		}

		$filename = 'collector_export_' . $mode
			. ($collection ? '_col' . $collection : '')
			. '_' . date('Ymd_His') . '.sql';

		file_put_contents(self::EXPORT_DIR . $filename, $sql);

		return $filename;
	}

	/**
	 * Retourne la liste des fichiers d'export disponibles.
	 */
	public function getExportFiles(): array
	{
		if (!is_dir(self::EXPORT_DIR)) {
			return [];
		}

		$files = glob(self::EXPORT_DIR . '*.sql') ?: [];
		$result = [];

		foreach ($files as $file) {
			$result[] = (object) [
				'name'     => basename($file),
				'size'     => filesize($file),
				'date'     => filemtime($file),
			];
		}

		// Trier par date décroissante
		usort($result, fn($a, $b) => $b->date - $a->date);

		return $result;
	}

	/**
	 * Supprime un fichier d'export.
	 */
	public function deleteExportFile(string $filename): bool
	{
		// Sécurité — éviter les path traversal
		$filename = basename($filename);
		$path     = self::EXPORT_DIR . $filename;

		if (!is_file($path) || pathinfo($path, PATHINFO_EXTENSION) !== 'sql') {
			return false;
		}

		return unlink($path);
	}
}