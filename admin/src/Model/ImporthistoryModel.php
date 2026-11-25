<?php
namespace Steevo\Component\Collector\Administrator\Model;

defined('_JEXEC') or die;

use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\Factory;
use Joomla\CMS\MVC\Model\ListModel;
use Steevo\Component\Collector\Administrator\Classes\ImportSimilarity;
use Steevo\Component\Collector\Administrator\Table\CollectorItemsTable;
use Steevo\Component\Collector\Administrator\Table\CollectorDefinedContentTable;
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
               ->select(['h.*', 'u.name AS created_by_name',
                         // Annulations (rollback) : objets concernés, objets annulés, date de la dernière
                         '(SELECT COUNT(*) FROM ' . $db->quoteName('#__collector_import_history_items') . ' hi'
                         . ' WHERE hi.import_id = h.id) AS items_total',
                         '(SELECT COUNT(*) FROM ' . $db->quoteName('#__collector_import_history_items') . ' hi'
                         . ' WHERE hi.import_id = h.id AND hi.rolled_back_at IS NOT NULL) AS items_rolled_back',
                         '(SELECT MAX(hi.rolled_back_at) FROM ' . $db->quoteName('#__collector_import_history_items') . ' hi'
                         . ' WHERE hi.import_id = h.id) AS last_rollback'])
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
               ->select(['id', 'alias', 'modified'])
               ->from($db->quoteName('#__collector_items'))
               ->where('id IN (' . $placeholders . ')')
        );
        $aliases   = [];
        $modifieds = [];
        foreach ($db->loadObjectList() as $row) {
            $aliases[(int) $row->id]   = $row->alias;
            $modifieds[(int) $row->id] = (string) $row->modified;
        }

        // Versions d'avant fusion encore présentes : sans suivi des versions, les versions
        // archivées peuvent avoir été supprimées (outil de nettoyage, restauration d'une version)
        $beforeIds = array_filter(array_map(fn($r) => (int) $r->version_before, $rows));
        $existingBefore = [];
        if ($beforeIds) {
            $db->setQuery(
                'SELECT id FROM ' . $db->quoteName('#__collector_items_history_' . $collection)
                . ' WHERE id IN (' . implode(',', array_unique($beforeIds)) . ')'
            );
            $existingBefore = array_flip(array_map('intval', $db->loadColumn() ?: []));
        }

        foreach ($rows as $item) {
            $itemId               = (int) $item->item_id;
            $currentVersion       = $currentVersions[$itemId] ?? null;
            $item->item_exists    = ($currentVersion !== null);
            // Modifié depuis l'import : nouvelle version active (suivi des versions activé)
            // ou date de modification postérieure à l'action (sans suivi des versions,
            // l'enregistrement écrase la version active sans changer son numéro)
            $doneAt   = (string) ($item->done_at ?? '');
            $modified = $modifieds[$itemId] ?? '';
            $item->modified_since = $item->item_exists
                && ($currentVersion !== (int) $item->version_after
                    || ($doneAt !== '' && $modified !== '' && !str_starts_with($modified, '0000')
                        && strtotime($modified) > strtotime($doneAt)));
            $item->item_alias     = $aliases[$itemId] ?? '#' . $itemId;
            $item->rolled_back    = !empty($item->rolled_back_at);
            // Fusion : l'annulation réactive la version d'avant l'import, qui doit encore exister
            $item->before_missing = $item->action === 'merged'
                && !isset($existingBefore[(int) $item->version_before]);
            $item->can_rollback   = $item->item_exists && !$item->rolled_back && !$item->before_missing;
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

            if (!$item->can_rollback) {
                // Objet supprimé, déjà annulé, ou version d'avant l'import disparue
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

        // Valeurs de listes créées par cet import et que plus rien n'utilise
        $result['values_removed'] = $result['rolled_back'] > 0 ? $this->removeUnusedCreatedValues($importId) : [];

        return $result;
    }

    /**
     * Supprime les valeurs de listes créées par un import quand plus aucune version
     * d'aucun objet ne les utilise (toutes collections) et qu'elles n'ont pas de
     * sous-valeur ; retire aussi les correspondances d'import qui pointaient dessus.
     *
     * Une annulation met en général les objets à la corbeille : la valeur leur sert
     * encore (une restauration les retrouverait intacts). Elle n'est alors supprimée
     * qu'une fois ces objets supprimés définitivement (corbeille vidée).
     *
     * @param  int  $importId  Import concerné, ou 0 pour tous les imports annulés
     * @return string[]  Libellés des valeurs supprimées
     */
    public function removeUnusedCreatedValues(int $importId): array
    {
        $db = $this->getDbo();

        try {
            $query = $db->getQuery(true)
                ->select(['h.id', 'h.defined', 'h.value_id', 'h.content'])
                ->from($db->quoteName('#__collector_import_history_values', 'h'))
                ->where('h.removed_at IS NULL');

            if ($importId > 0) {
                $query->where('h.import_id = ' . $importId);
            } else {
                // Seulement les imports dont au moins un objet a été annulé
                $query->where('h.import_id IN (SELECT hi.import_id FROM ' . $db->quoteName('#__collector_import_history_items')
                    . ' hi WHERE hi.rolled_back_at IS NOT NULL)');
            }

            $created = $db->setQuery($query)->loadObjectList() ?: [];
        } catch (\Throwable $e) {
            return []; // table absente : base pas encore à jour
        }

        if (!$created) {
            return [];
        }

        $merge   = Factory::getApplication()->bootComponent('com_collector')->getMVCFactory()
                          ->createModel('Listmerge', 'Administrator', ['ignore_request' => true]);
        $removed = [];
        $now     = Factory::getDate()->toSql();

        foreach ($created as $c) {
            $id      = (int) $c->value_id;
            $exists  = (int) $db->setQuery('SELECT COUNT(*) FROM ' . $db->quoteName('#__collector_defined_content') . ' WHERE id = ' . $id)->loadResult();
            $usage   = $exists ? $merge->getUsage((int) $c->defined, [$id]) : [];
            $versions = (int) ($usage[$id]['versions'] ?? 0);
            $children = $exists ? (int) $db->setQuery('SELECT COUNT(*) FROM ' . $db->quoteName('#__collector_defined_content') . ' WHERE parent_id = ' . $id)->loadResult() : 0;

            if ($exists && ($versions > 0 || $children > 0)) {
                continue; // encore utilisée (ou mère d'autres valeurs) : on la garde
            }

            if ($exists) {
                $table = new CollectorDefinedContentTable($db);
                if (!$table->delete($id)) {
                    continue;
                }
                $removed[] = (string) $c->content;
                $this->forgetValueInImportConfig($id);
            }

            $db->setQuery(
                'UPDATE ' . $db->quoteName('#__collector_import_history_values')
                . ' SET removed_at = ' . $db->quote($now) . ' WHERE id = ' . (int) $c->id
            )->execute();
        }

        return $removed;
    }

    /**
     * Correspondances d'import qui désignaient une valeur supprimée : la valeur
     * sera de nouveau proposée à la création au prochain import.
     */
    private function forgetValueInImportConfig(int $valueId): void
    {
        $db   = $this->getDbo();
        $rows = $db->setQuery('SELECT id, value_mapping FROM ' . $db->quoteName('#__collector_import_config'))->loadObjectList() ?: [];

        foreach ($rows as $row) {
            $map     = json_decode((string) $row->value_mapping, true);
            $changed = false;

            if (!is_array($map)) {
                continue;
            }

            foreach ($map as &$col) {
                if (!is_array($col)) continue;
                if ((string) ($col['empty'] ?? '') === (string) $valueId) {
                    $col['empty'] = '';
                    $changed      = true;
                }
                if (!isset($col['values']) || !is_array($col['values'])) continue;
                // Par référence sur le tableau lui-même (pas sur une copie)
                foreach ($col['values'] as &$pair) {
                    if ((string) ($pair['db'] ?? '') === (string) $valueId) {
                        $pair['db']           = '';
                        $pair['create']       = '1';
                        $pair['create_label'] = ($pair['create_label'] ?? '') !== '' ? $pair['create_label'] : ($pair['file'] ?? '');
                        $changed              = true;
                    }
                }
                unset($pair);
            }
            unset($col);

            if ($changed) {
                $db->setQuery(
                    'UPDATE ' . $db->quoteName('#__collector_import_config')
                    . ' SET value_mapping = ' . $db->quote(json_encode($map)) . ' WHERE id = ' . (int) $row->id
                )->execute();
            }
        }
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

        // Ne jamais désactiver la version courante sans pouvoir en réactiver une autre
        $exists = (int) $db->setQuery(
            'SELECT COUNT(*) FROM ' . $db->quoteName('#__collector_items_history_' . $collection)
            . ' WHERE id = ' . $versionBefore . ' AND item = ' . $itemId
        )->loadResult();

        if (!$exists) {
            throw new \RuntimeException('Version #' . $versionBefore . ' supprimée pour item #' . $itemId);
        }

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

    /**
     * Objets de la base qu'aucune ligne du fichier n'a retrouvés à la fin de l'import,
     * avec leur état actuel (encore là, à la corbeille, supprimé).
     *
     * @return array{scope: string[], items: array, cols: array}
     */
    public function getMissingItems(object $import, int $collection): array
    {
        $data = json_decode((string) ($import->missing_items ?? ''), true);
        $ids  = array_values(array_filter(array_map('intval', (array) ($data['ids'] ?? []))));
        if (!$ids) {
            return ['scope' => [], 'items' => [], 'cols' => []];
        }

        $db     = $this->getDbo();
        $states = $db->setQuery(
            $db->getQuery(true)
               ->select(['id', 'state'])
               ->from($db->quoteName('#__collector_items'))
               ->whereIn($db->quoteName('id'), $ids)
        )->loadAssocList('id', 'state');

        /** @var ImportModel $importModel */
        $importModel = Factory::getApplication()->bootComponent('com_collector')->getMVCFactory()
                              ->createModel('Import', 'Administrator', ['ignore_request' => true]);
        $fields = $importModel->getFields();
        $config = $importModel->getConfig($collection) ?? [];

        // Colonnes affichées : champs de comparaison et champs du titre
        $wanted = array_unique(array_merge((array) ($config['composite_fields'] ?? []),
                                           $importModel->getFulttitleCols($collection)));
        $cols   = [];
        foreach ($fields as $field) {
            if (in_array($field->_field->tablecolumn, $wanted, true)) {
                $cols[$field->_field->tablecolumn] = $field->_field->field;
            }
        }

        // Objets créés par cet import qui ressemblent à chaque objet absent : sa ligne du
        // fichier a peut-être été importée comme nouvel objet au lieu d'être rapprochée de lui
        $cmpCols = ($config['duplicate_strategy'] ?? 'unik') === 'composite'
            ? array_values((array) ($config['composite_fields'] ?? []))
            : array_values(array_filter(array_map(fn($f) => $f->_field->unik ? $f->_field->tablecolumn : null, $fields)));
        $keys     = (array) ($config['similar_keys'] ?? []);
        $readCols = array_values(array_unique(array_merge($cmpCols, $keys)));
        $imported = $this->loadItemValues($collection, $this->getCreatedItemIds((int) $import->id), $readCols);
        $missing  = $this->loadItemValues($collection, $ids, $readCols);

        // Objets déjà fusionnés avec un objet créé par l'import [objet en base => objet créé]
        $merged = array_map('intval', (array) ($data['merged'] ?? []));

        $items = [];
        foreach ($ids as $id) {
            $exists  = array_key_exists($id, $states);
            $similar = [];
            if ($exists && $imported && isset($missing[$id]) && !isset($merged[$id])) {
                $prepared = ImportSimilarity::prepare($missing[$id], $fields, $cmpCols, $keys);
                foreach ($prepared ? ImportSimilarity::rank($missing[$id], $prepared, $imported, self::SIMILAR_IMPORTED) : [] as $c) {
                    $similar[] = $c + ['display' => $importModel->getItemDisplayData($collection, $c['id'], $fields)];
                }
            }
            $items[] = [
                'id'      => $id,
                'exists'  => $exists,
                'state'   => $exists ? (int) $states[$id] : null,
                'display' => $exists ? $importModel->getItemDisplayData($collection, $id, $fields) : [],
                'row'     => isset($data['rows'][$id]) ? (int) $data['rows'][$id] : null,
                'similar' => $similar,
                'merged'  => $merged[$id] ?? null,
            ];
        }

        // Tous les champs, pour la fenêtre de comparaison
        $allFields = [];
        foreach ($fields as $field) {
            $allFields[$field->_field->tablecolumn] = $field->_field->field;
        }

        return ['scope' => (array) ($data['scope'] ?? []), 'items' => $items, 'cols' => $cols, 'fields' => $allFields];
    }

    /**
     * Comparaison d'un objet non retrouvé avec un objet créé par cet import, choisi par
     * son numéro : mêmes contrôles que la fusion.
     *
     * @return array{target: int, source: int, score: int, a: array, b: array}|string  Message d'erreur
     */
    public function getDuplicateComparison(object $import, int $collection, int $targetId, int $sourceId): array|string
    {
        $data = json_decode((string) ($import->missing_items ?? ''), true) ?: [];

        if (!in_array($targetId, array_map('intval', (array) ($data['ids'] ?? [])), true)
            || isset($data['merged'][$targetId])) {
            return \Joomla\CMS\Language\Text::_('COM_COLLECTOR_IMPORTHISTORY_DUP_NOT_ALLOWED');
        }
        if (!in_array($sourceId, $this->getCreatedItemIds((int) $import->id), true)) {
            return \Joomla\CMS\Language\Text::sprintf('COM_COLLECTOR_IMPORTHISTORY_DUP_NOT_CREATED', $sourceId);
        }

        /** @var ImportModel $importModel */
        $importModel = Factory::getApplication()->bootComponent('com_collector')->getMVCFactory()
                              ->createModel('Import', 'Administrator', ['ignore_request' => true]);
        $fields = $importModel->getFields();
        $config = $importModel->getConfig($collection) ?? [];

        // Note de ressemblance, pour information
        $cmpCols = ($config['duplicate_strategy'] ?? 'unik') === 'composite'
            ? array_values((array) ($config['composite_fields'] ?? []))
            : array_values(array_filter(array_map(fn($f) => $f->_field->unik ? $f->_field->tablecolumn : null, $fields)));
        $keys     = (array) ($config['similar_keys'] ?? []);
        $values   = $this->loadItemValues($collection, [$targetId, $sourceId], array_values(array_unique(array_merge($cmpCols, $keys))));
        $score    = 0;
        if (isset($values[$targetId], $values[$sourceId])
            && ($prepared = ImportSimilarity::prepare($values[$targetId], $fields, $cmpCols, $keys))) {
            $ranked = ImportSimilarity::rank($values[$targetId], $prepared, [$sourceId => $values[$sourceId]], 0.0);
            $score  = (int) round(($ranked[0]['score'] ?? 0) * 100);
        }

        $states = $this->getDbo()->setQuery(
            'SELECT state FROM ' . $this->getDbo()->quoteName('#__collector_items') . ' WHERE id = ' . $targetId
        )->loadResult();

        return [
            'target'  => $targetId,
            'source'  => $sourceId,
            'score'   => $score,
            'a'       => $importModel->getItemDisplayData($collection, $targetId, $fields),
            'b'       => $importModel->getItemDisplayData($collection, $sourceId, $fields),
            'trashed' => (int) $states === -2,
        ];
    }

    /**
     * Fusionne un objet non retrouvé dans le fichier (cible) avec un objet créé par cet
     * import qui lui ressemble (doublon) : les valeurs choisies du doublon passent sur la
     * cible, dans une nouvelle version notée dans l'historique (donc annulable), puis le
     * doublon est mis à la corbeille.
     *
     * @param  string[]  $cols  Colonnes dont la valeur est prise du doublon
     * @return string|null  Message d'erreur, ou null si tout s'est bien passé
     */
    public function mergeDuplicate(object $import, int $collection, int $targetId, int $sourceId, array $cols): ?string
    {
        $data = json_decode((string) ($import->missing_items ?? ''), true) ?: [];

        if (!in_array($targetId, array_map('intval', (array) ($data['ids'] ?? [])), true)
            || isset($data['merged'][$targetId])
            || !in_array($sourceId, $this->getCreatedItemIds((int) $import->id), true)) {
            return \Joomla\CMS\Language\Text::_('COM_COLLECTOR_IMPORTHISTORY_DUP_NOT_ALLOWED');
        }

        /** @var ImportModel $importModel */
        $importModel = Factory::getApplication()->bootComponent('com_collector')->getMVCFactory()
                              ->createModel('Import', 'Administrator', ['ignore_request' => true]);
        $validCols   = array_map(fn($f) => $f->_field->tablecolumn, $importModel->getFields());
        $cols        = array_values(array_intersect($cols, $validCols));

        if ($cols) {
            $values = $this->loadItemValues($collection, [$sourceId], $cols)[$sourceId] ?? null;
            if ($values === null) {
                return \Joomla\CMS\Language\Text::_('COM_COLLECTOR_IMPORTHISTORY_DUP_NOT_ALLOWED');
            }

            // Valeurs choisies une à une : une valeur vide efface aussi celle de la cible
            $result = $importModel->mergeItem($collection, $targetId, $values + ['collection' => $collection],
                ComponentHelper::getParams('com_collector'), [], true);
            if ($result === false) {
                return $importModel->getError() ?: 'mergeItem';
            }
            $importModel->addImportHistoryItem((int) $import->id, $targetId, 'merged',
                $result['version_before'], $result['version_after']);
        }

        // Le doublon va à la corbeille
        $table = new CollectorItemsTable($this->getDbo());
        $table->initVersion($collection);
        if ($table->load($sourceId) && (int) $table->state !== -2) {
            $table->state = -2;
            if (!$table->store()) {
                return $table->getError();
            }
        }

        // Rapprochement noté : l'objet n'est plus proposé à la corbeille ni comparé
        $data['merged'][(string) $targetId] = $sourceId;
        $db = $this->getDbo();
        $db->setQuery(
            $db->getQuery(true)
               ->update($db->quoteName('#__collector_import_history'))
               ->set($db->quoteName('missing_items') . ' = ' . $db->quote(json_encode($data)))
               ->where($db->quoteName('id') . ' = ' . (int) $import->id)
        )->execute();

        return null;
    }

    /** Note minimale d'un objet créé par l'import pour être proposé sous un objet absent */
    private const SIMILAR_IMPORTED = 0.65;

    /** Objets créés par un import, ni annulés ni à la corbeille */
    private function getCreatedItemIds(int $importId): array
    {
        $db = $this->getDbo();

        return array_map('intval', $db->setQuery(
            $db->getQuery(true)
               ->select('hi.item_id')
               ->from($db->quoteName('#__collector_import_history_items', 'hi'))
               ->join('INNER', $db->quoteName('#__collector_items', 'i') . ' ON i.id = hi.item_id')
               ->where('hi.import_id = ' . $importId)
               ->where('hi.action = ' . $db->quote('imported'))
               ->where('hi.rolled_back_at IS NULL')
               ->where('i.state >= 0')
        )->loadColumn() ?: []);
    }

    /** Valeurs actives de quelques colonnes [id => [colonne => valeur]] */
    private function loadItemValues(int $collection, array $ids, array $cols): array
    {
        if (!$ids || !$cols) {
            return [];
        }

        $db     = $this->getDbo();
        $select = ['h.item'];
        foreach ($cols as $col) {
            $select[] = $db->quoteName('h.' . $col);
        }

        $values = [];
        foreach ($db->setQuery(
            $db->getQuery(true)
               ->select($select)
               ->from($db->quoteName('#__collector_items_history_' . $collection, 'h'))
               ->where('h.state = 1')
               ->whereIn($db->quoteName('h.item'), array_map('intval', $ids))
        )->loadAssocList() ?: [] as $row) {
            $id = (int) $row['item'];
            unset($row['item']);
            $values[$id] = array_map('strval', $row);
        }

        return $values;
    }

    /**
     * Met à la corbeille des objets absents du fichier ; seuls ceux notés pour cet import
     * sont acceptés. Retourne le nombre d'objets mis à la corbeille.
     */
    public function trashMissing(object $import, int $collection, array $itemIds): int
    {
        $data    = json_decode((string) ($import->missing_items ?? ''), true);
        $allowed = array_diff(array_map('intval', (array) ($data['ids'] ?? [])),
                              array_map('intval', array_keys((array) ($data['merged'] ?? []))));
        $count   = 0;

        foreach (array_intersect(array_map('intval', $itemIds), $allowed) as $itemId) {
            $table = new CollectorItemsTable($this->getDbo());
            $table->initVersion($collection);
            if (!$table->load($itemId) || (int) $table->state === -2) {
                continue;
            }
            $table->state = -2;
            if ($table->store()) {
                $count++;
            }
        }

        return $count;
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