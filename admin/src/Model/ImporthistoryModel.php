<?php
namespace Steevo\Component\Collector\Administrator\Model;

defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\MVC\Model\ListModel;
use Steevo\Component\Collector\Administrator\Table\CollectorItemsTable;
use Steevo\Component\Collector\Administrator\Table\CollectorImportHistoryTable;
use Steevo\Component\Collector\Administrator\Table\CollectorImportHistoryItemsTable;

class ImporthistoryModel extends ListModel
{
    // ── Liste des imports ─────────────────────────────────────────────────────

    public function getImports(int $collection): array
    {
        $db = $this->getDbo();
        $db->setQuery(
            $db->getQuery(true)
               ->select(['h.*', 'u.name AS created_by_name'])
               ->from($db->quoteName('#__collector_import_history', 'h'))
               ->leftJoin($db->quoteName('#__users', 'u') . ' ON u.id = h.created_by')
               ->where($db->quoteName('h.collection') . ' = ' . $collection)
               ->order($db->quoteName('h.created') . ' DESC')
        );
        return $db->loadObjectList() ?: [];
    }

    // ── Détail d'un import ────────────────────────────────────────────────────

    public function getImport(int $importId): ?object
    {
        $db    = $this->getDbo();
        $table = new CollectorImportHistoryTable($db);

        if (!$table->load($importId)) {
            return null;
        }

        // Enrichir avec le nom de l'utilisateur
        $db->setQuery(
            $db->getQuery(true)
               ->select('name')
               ->from($db->quoteName('#__users'))
               ->where('id = ' . (int) $table->created_by)
        );
        $table->created_by_name = $db->loadResult() ?? '';

        return (object) $table->getProperties();
    }

    public function getImportItems(int $importId, int $collection): array
    {
        $db = $this->getDbo();

        // Charger via une requête (pas de load() multiple sur Table)
        $db->setQuery(
            $db->getQuery(true)
               ->select('*')
               ->from($db->quoteName('#__collector_import_history_items'))
               ->where($db->quoteName('import_id') . ' = ' . $importId)
               ->order($db->quoteName('id') . ' ASC')
        );
        $rows = $db->loadObjectList() ?: [];

        if (empty($rows)) return [];

        $itemIds      = array_unique(array_map(fn($r) => (int) $r->item_id, $rows));
        $placeholders = implode(',', $itemIds);

        // Version active actuelle de chaque item
        $db->setQuery(
            'SELECT h.item, h.id AS current_version_id
             FROM ' . $db->quoteName('#__collector_items_history_' . $collection) . ' h
             WHERE h.item IN (' . $placeholders . ') AND h.state = 1'
        );
        $currentVersions = [];
        foreach ($db->loadObjectList() as $row) {
            $currentVersions[(int) $row->item] = (int) $row->current_version_id;
        }

        // Alias des items pour affichage
        $db->setQuery(
            $db->getQuery(true)
               ->select(['id', 'alias'])
               ->from($db->quoteName('#__collector_items'))
               ->where('id IN (' . $placeholders . ')')
        );
        $aliases = [];
        foreach ($db->loadObjectList() as $row) {
            $aliases[(int) $row->id] = $row->alias;
        }

        foreach ($rows as $item) {
            $itemId               = (int) $item->item_id;
            $currentVersion       = $currentVersions[$itemId] ?? null;
            $item->item_exists    = ($currentVersion !== null);
            $item->modified_since = $item->item_exists
                && ($currentVersion !== (int) $item->version_after);
            $item->item_alias     = $aliases[$itemId] ?? '#' . $itemId;
        }

        return $rows;
    }

    // ── Rollback ──────────────────────────────────────────────────────────────

    public function rollback(
        int    $importId,
        int    $collection,
        array  $itemIds    = [],
        bool   $force      = false,
        string $deleteMode = 'trash'
    ): array {
        $items  = $this->getImportItems($importId, $collection);
        $result = ['success' => true, 'rolled_back' => 0, 'skipped' => 0, 'errors' => []];

        if (!empty($itemIds)) {
            $items = array_filter($items, fn($i) => in_array((int) $i->item_id, $itemIds));
        }

        foreach ($items as $item) {
            $itemId = (int) $item->item_id;

            if (!$item->item_exists) {
                $result['skipped']++;
                continue;
            }
            if ($item->modified_since && !$force) {
                $result['skipped']++;
                continue;
            }

            try {
                match ($item->action) {
                    'imported' => $this->rollbackImported($itemId, $collection, $deleteMode),
                    'merged'   => $this->rollbackMerged($itemId, $collection, (int) $item->version_before),
                    default    => throw new \RuntimeException('Action inconnue : ' . $item->action),
                };
				// Marquer l'item comme rollbacké
				$db    = $this->getDbo();
				$table = new CollectorImportHistoryItemsTable($db);
				$table->load((int) $item->id);
				$table->rolled_back_at = Factory::getDate()->toSql();
				$table->store();
				
                $result['rolled_back']++;
            } catch (\Throwable $e) {
                $result['success']  = false;
                $result['errors'][] = 'Item #' . $itemId . ' : ' . $e->getMessage();
            }
        }

        return $result;
    }

    private function rollbackImported(int $itemId, int $collection, string $deleteMode): void
    {
        $db    = $this->getDbo();
        $table = new CollectorItemsTable($db);
        $table->initVersion($collection);

        if ($deleteMode === 'trash') {
            $table->load($itemId);
            $table->state = -2;
            if (!$table->store()) {
                throw new \RuntimeException($table->getError());
            }
        } else {
            // Suppression définitive via Table::delete()
            $db->setQuery(
                'DELETE FROM ' . $db->quoteName('#__collector_items_history_' . $collection)
                . ' WHERE item = ' . $itemId
            )->execute();

            if (!$table->delete($itemId)) {
                throw new \RuntimeException($table->getError());
            }
        }
    }

    private function rollbackMerged(int $itemId, int $collection, int $versionBefore): void
    {
        if (!$versionBefore) {
            throw new \RuntimeException('Version précédente inconnue pour item #' . $itemId);
        }

        $db    = $this->getDbo();
        $table = new CollectorItemsTable($db);
        $table->initVersion($collection);
        $table->load($itemId);
        $table->activateVersion($versionBefore);
    }

    // ── Utilitaires ───────────────────────────────────────────────────────────

    public function getModifiedItemIds(int $importId, int $collection): array
    {
        return array_values(array_map(
            fn($i) => (int) $i->item_id,
            array_filter(
                $this->getImportItems($importId, $collection),
                fn($i) => $i->modified_since && $i->item_exists
            )
        ));
    }

    public function deleteImportHistory(int $importId): bool
    {
        $db = $this->getDbo();

        // Supprimer les items via Table
        $db->setQuery(
            $db->getQuery(true)
               ->delete($db->quoteName('#__collector_import_history_items'))
               ->where($db->quoteName('import_id') . ' = ' . $importId)
        )->execute();

        // Supprimer l'entrée principale via Table
        $table = new CollectorImportHistoryTable($db);
        if (!$table->delete($importId)) {
            $this->setError($table->getError());
            return false;
        }

        return true;
    }
}