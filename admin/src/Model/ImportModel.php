<?php
/**
 * @package    Collector
 * @copyright  Copyright (C) 2010 - 2020 Philippe Ousset. All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace Steevo\Component\Collector\Administrator\Model;

defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\Model\ListModel;
use Joomla\Registry\Registry;
use Steevo\Component\Collector\Administrator\Classes\CollectorField;
use Steevo\Component\Collector\Administrator\Table\CollectorItemsTable;
use Steevo\Component\Collector\Administrator\Table\CollectorImportHistoryTable;
use Steevo\Component\Collector\Administrator\Table\CollectorImportHistoryItemsTable;

/**
 * Modèle d'import Excel/CSV — Joomla 5/6.
 *
 * Fournit :
 *  - les métadonnées (champs, collections) pour la vue
 *  - la détection de doublons via les champs uniques (unik=1)
 *  - la comparaison stricte de deux objets (all-fields equality)
 *  - la fusion d'un objet importé avec un existant
 *  - la résolution des valeurs pour affichage dans l'UI de conflit
 */
class ImportModel extends ListModel
{
	private ?int   $_collection = null;
	private array  $_fields     = [];

	// ── Collection ────────────────────────────────────────────────────────────

	public function getCollection(): int
	{
		if ($this->_collection !== null) {
			return $this->_collection;
		}

		$app        = Factory::getApplication();
		$collection = $app->input->getInt('collection', 0);

		if ($collection === 0) {
			$db    = $this->getDbo();
			$query = $db->getQuery(true)
				->select('id')
				->from($db->quoteName('#__collector'))
				->where($db->quoteName('home') . ' = 1');
			$db->setQuery($query);
			$collection = (int) $db->loadResult();
		}

		$this->_collection = $collection;
		return $collection;
	}

	// ── Champs de la collection ───────────────────────────────────────────────

	public function getFields(): array
	{
		if (!empty($this->_fields)) {
			return $this->_fields;
		}

		$collection = $this->getCollection();
		$db         = $this->getDbo();

		$query = $db->getQuery(true)
			->select(['f.id', 'f.field', 'f.description', 'f.tablecolumn',
			          'f.required', 'f.attribs', 'f.unik', 't.type AS type'])
			->from($db->quoteName('#__collector_fields', 'f'))
			->join('LEFT', $db->quoteName('#__collector_fields_type', 't') . ' ON t.id = f.type')
			->where($db->quoteName('f.collection') . ' = ' . $collection)
			->order($db->quoteName('f.ordering') . ' ASC');

		$db->setQuery($query);
		$fieldDefs = $db->loadObjectList();

		foreach ($fieldDefs as $def) {
			$registry = new Registry();
			$registry->loadString($def->attribs ?? '');
			$def->attribs   = $registry->toArray();
			$this->_fields[] = CollectorField::getInstance($collection, $def);
		}

		return $this->_fields;
	}

	// ── Sélecteur de collections ──────────────────────────────────────────────

	public function getCollectionsSelect(): string
	{
		$collection = $this->getCollection();
		$db         = $this->getDbo();

		$query = $db->getQuery(true)
			->select('id AS value, name AS text')
			->from($db->quoteName('#__collector'))
			->order($db->quoteName('name') . ' ASC');

		$db->setQuery($query);
		$options = $db->loadObjectList();

		return HTMLHelper::_('select.genericlist', $options, 'collection',
			'class="form-select" onchange="changeCollection(this)"',
			'value', 'text', $collection
		);
	}
	
	public function getFulttitleCols(int $collection): array
	{
		$db = $this->getDbo();
		$db->setQuery('SELECT custom FROM #__collector WHERE id = ' . $collection);
		$custom = $db->loadResult();

		if ($custom && $custom !== '0') {
			$ids = array_map('intval', array_filter(explode('/', $custom)));
		} else {
			// Fallback : champ home
			$db->setQuery(
				$db->getQuery(true)
				   ->select('id')
				   ->from('#__collector_fields')
				   ->where('collection = ' . $collection)
				   ->where('home = 1')
				   ->setLimit(1)
			);
			$id  = $db->loadResult();
			$ids = $id ? [(int) $id] : [];
		}

		if (empty($ids)) return [];

		$db->setQuery(
			$db->getQuery(true)
			   ->select('tablecolumn')
			   ->from('#__collector_fields')
			   ->where('id IN (' . implode(',', $ids) . ')')
		);
		return $db->loadColumn();
	}

	// ── Détection de doublons ─────────────────────────────────────────────────

	/**
	 * Recherche un item existant dont TOUS les champs uniques (unik=1)
	 * correspondent aux données importées.
	 *
	 * Retourne l'objet history (ligne active) si un doublon est trouvé, null sinon.
	 * Si aucun champ unique n'est défini pour la collection, aucune détection
	 * n'est possible et la méthode retourne toujours null.
	 *
	 * @param  int    $collection
	 * @param  array  $data         Données importées (après getImportedValue())
	 * @param  array  $fields       Instances CollectorField
	 * @return object|null          Ligne de l'historique actif de l'item trouvé
	 */
	public function checkDuplicate(int $collection, array $data, array $fields, string $strategy = 'unik', array $compositeCols = []): object|array|null
	{
		// Récupère les colonnes marquées comme uniques
		$uniqueCols = array_values(array_filter(
			array_map(fn($f) => $f->_field->unik ? $f->_field->tablecolumn : null, $fields)
		));

		$searchCols = match ($strategy) {
			'composite' => array_values(array_filter(
							   $compositeCols,
							   fn($c) => ($data[$c] ?? '') !== ''
						   )),
			default     => $uniqueCols,
		};
	
		if (empty($searchCols)) return null;

		$db    = $this->getDbo();
		$query = $db->getQuery(true)
			->select('i.id')
			->from($db->quoteName('#__collector_items', 'i'))
			->join('LEFT',
				$db->quoteName('#__collector_items_history_' . $collection, 'h')
				. ' ON h.item = i.id AND h.state = 1'
			)
			->where($db->quoteName('i.collection') . ' = ' . $collection)
			->where($db->quoteName('i.state') . ' >= 0');

		$conditions = [];
		$fieldsByCol = [];
		foreach ($fields as $field) {
			$fieldsByCol[$field->_field->tablecolumn] = $field;
		}
		foreach ($searchCols as $col) {
			if (($data[$col] ?? '') === '') continue;
			$field = $fieldsByCol[$col] ?? null;
			$conditions[] = $field
				? $field->getSearchCondition('h', (string) $data[$col], $db)
				: $db->quoteName('h.' . $col) . ' = ' . $db->quote($data[$col]);
		}

		if (empty($conditions)) {
			return null;
		}
		
		foreach ($conditions as $cond) {
			$query->where($cond);
		}

		$db->setQuery($query);
		// $existingIds = array_map('intval', array_filter((array) $db->loadColumn()));
		$existingIds = array_values(array_unique(
			array_map('intval', array_filter((array) $db->loadColumn()))
		));



        if (empty($existingIds)) {
            return null;
        }

		// Charger les données brutes de chaque item correspondant
        $query = $db->getQuery(true)
            ->select('h.*, i.id')
            ->from($db->quoteName('#__collector_items_history_' . $collection, 'h'))
            ->join('LEFT', $db->quoteName('#__collector_items', 'i') . ' ON i.id = h.item')
            ->where('h.item IN (' . implode(',', $existingIds) . ')')
            ->where('h.state = 1');

        $db->setQuery($query);
        $results = $db->loadObjectList();
	
        if (empty($results))    return null;
        if (count($results) === 1) return $results[0];
        return $results;   
	}

	/**
	 * Retourne true si les données importées sont strictement identiques
	 * (champ par champ) à l'objet existant.
	 *
	 * Pour les types define/item, la comparaison se fait sur les valeurs brutes
	 * stockées en base (IDs), pas sur les libellés résolus.
	 */
	public function isIdentical(array $importData, object $existingItem, array $fields): bool
	{
		foreach ($fields as $field) {
			$col         = $field->_field->tablecolumn;
			$importVal   = $field->normalizeForComparison((string) ($importData[$col]   ?? ''));
			$existingVal = $field->normalizeForComparison((string) ($existingItem->$col ?? ''));
			if ($importVal !== $existingVal) {
				return false;
			}
		}
		return true;
	}

	// ── Fusion ────────────────────────────────────────────────────────────────

	/**
	 * Fusionne les données importées avec un item existant.
	 *
	 * Principe : on charge l'item existant, on écrase avec les valeurs importées
	 * non vides, puis on crée une nouvelle version en historique.
	 * Les champs vides dans l'import conservent la valeur existante.
	 */
	public function mergeItem(int $collection, int $existingId, array $importData, object $params, array $publishing): array|false
	{
		$db    = $this->getDbo();
		$table = new CollectorItemsTable($db);
		$table->initVersion($collection);
		$table->load($existingId);
		$table->initVersion($collection); // recharge la structure history

		// Charge les données historiques de la version active
		$table->loadVersion($collection, $existingId);

		// Applique les valeurs importées : les champs vides ne remplacent pas
		foreach ($importData as $col => $value) {
			if ($col !== 'collection' && $value !== '') {
				$table->$col = $value;
			}
		}
		// Appliquer les paramètres de publication
		if (!empty($publishing)) {
			$table->state       = (int) ($publishing['state']  ?? 1);
			$table->access      = (int) ($publishing['access'] ?? 1);
			$table->publish_up   = $publishing['publish_up']   ?? null;
			$table->publish_down = $publishing['publish_down'] ?? null;
		}

		$importDate          = Factory::getDate()->format(Text::_('DATE_FORMAT_LC2'));
		$table->modification = Text::sprintf('COM_COLLECTOR_IMPORT_VERSION_NOTE', $importDate);
		
		if (!$table->checkVersion()) {
			return false;
		}
		
		$versionBefore = (int) ($table->historyId ?? 0);

		if (!$table->storeVersion(null)) {
			return false;
		}

		return [
			'item_id'        => $existingId,
			'version_before' => $versionBefore,
			'version_after'  => (int) ($table->history->id ?? 0),
		];
	}

	/**
	 * Crée l'entrée d'historique au début d'un import.
	 * Retourne l'ID de l'entrée créée.
	 */
	public function createImportHistory(int $collection, string $file,
                                    string $strategy, int $userId, bool $dryRun = false): int
	{
		$db    = $this->getDbo();
		$table = new CollectorImportHistoryTable($db);
		$table->collection  = $collection;
		$table->file        = $file;
		$table->strategy    = $strategy;
		$table->created_by  = $userId;
		$table->dry_run    = $dryRun ? 1 : 0;

		if (!$table->check() || !$table->store()) {
			return 0;
		}
		return (int) $table->id;
	}

	/**
	 * Enregistre un objet affecté par l'import.
	 */
	public function addImportHistoryItem(int $importId, int $itemId,
                                     string $action, ?int $versionBefore,
                                     ?int $versionAfter): bool
	{
		$db    = $this->getDbo();
		$table = new CollectorImportHistoryItemsTable($db);
		$table->import_id      = $importId;
		$table->item_id        = $itemId;
		$table->action         = $action;
		$table->version_before = $versionBefore;
		$table->version_after  = $versionAfter;

		return $table->check() && $table->store();
	}

	/**
	 * Met à jour les compteurs finaux de l'import.
	 */
	public function updateImportHistory(
		int $importId,
		int $imported, int $merged, int $skipped, int $errors,
		int $conflicts = 0, int $multiconflicts = 0  // ← nouveau
	): bool {
		$db    = $this->getDbo();
		$table = new CollectorImportHistoryTable($db);
		$table->load($importId);
		return $table->updateCounters($imported, $merged, $skipped, $errors,
									  $conflicts, $multiconflicts);
	}
	
	// ── Résolution des valeurs pour affichage dans l'UI de conflit ───────────

	/**
	 * Retourne un tableau [column => displayValue] pour les données importées.
	 * Appelle display() de chaque CollectorField avec la valeur brute importée.
	 *
	 * Note : pour les types define/item dont la valeur importée est un ID,
	 * la valeur brute est affichée telle quelle (l'affichage reste lisible
	 * pour les types text, line, number, date qui sont les plus courants).
	 */
	public function getDisplayData(int $collection, array $rawData, array $fields): array
	{
		$display = [];
		$params  = new Registry(['show_emptyfield' => true]);

		foreach ($fields as $field) {
			$col       = $field->_field->tablecolumn;
			$value     = $rawData[$col] ?? '';
			$displayed = $field->display($value, false, $params);
			$display[$col] = ($displayed === false || $displayed === '') ? $value : strip_tags((string) $displayed);
		}

		return $display;
	}

	/**
	 * Retourne un tableau [column => displayValue] pour un item existant en base.
	 *
	 * Utilise setQuery() de chaque CollectorField pour résoudre les JOINs
	 * (define → label, item → fulltitle) exactement comme dans la vue Items.
	 */
	public function getItemDisplayData(int $collection, int $itemId, array $fields): array
	{
		$db = $this->getDbo();

		// Requête avec JOINs de résolution (pattern établi dans VersionscompareModel)
		$query = $db->getQuery(true)
			->select(['h.id', 'h.item'])
			->from($db->quoteName('#__collector_items_history_' . $collection, 'h'))
			->where('h.item = ' . $itemId)
			->where('h.state = 1');

		foreach ($fields as $field) {
			$field->setQuery($query);
		}

		$db->setQuery($query);
		$row = $db->loadObject();

		if (!$row) {
			return [];
		}

		$display = [];
		$params  = new Registry(['show_emptyfield' => true]);

		foreach ($fields as $field) {
			$col       = $field->_field->tablecolumn;
			$value     = $row->$col ?? '';
			$displayed = $field->display($value, false, $params);
			$display[$col] = ($displayed === false || $displayed === '') ? (string) $value : strip_tags((string) $displayed);
		}

		return $display;
	}
	// ── Persistance de session d'import (côté serveur) ──────────────────────────

	/**
	 * Sauvegarde ou met à jour la session d'import en base.
	 *
	 * @param int   $collection
	 * @param int   $userId
	 * @param array $sessionData  Doit contenir : file, first_line, mapping (array|string),
	 *                            current_row, total_rows, imported, skipped, merged
	 */
	public function saveSession(int $collection, int $userId, array $sessionData): bool
	{
		$db  = $this->getDbo();
		$now = Factory::getDate()->toSql();

		// Sérialiser mapping + value_mapping + step + publishing dans le même champ JSON
		$mappingPayload = [
			'colmap'                 => is_array($sessionData['mapping'] ?? null) ? $sessionData['mapping'] : [],
			'value_mapping'          => is_array($sessionData['value_mapping'] ?? null) ? $sessionData['value_mapping'] : [],
			'step'                   => (int) ($sessionData['step'] ?? 1),
			'publishing'             => is_array($sessionData['publishing'] ?? null) ? $sessionData['publishing'] : [],
			'errors'                 => (int) ($sessionData['errors'] ?? 0),
			'duplicate_strategy'     => (string) ($sessionData['duplicate_strategy'] ?? 'unik'),
			'composite_fields'       => is_array($sessionData['composite_fields'] ?? null) ? $sessionData['composite_fields'] : [],
			'dry_run'                => (bool)  ($sessionData['dry_run']        ?? false),
			'dry_run_report'         => array_values((array) ($sessionData['dry_run_report'] ?? [])),
			'dry_run_conflicts'      => (int) ($sessionData['dry_run_conflicts']      ?? 0),
			'dry_run_multiconflicts' => (int) ($sessionData['dry_run_multiconflicts'] ?? 0),
			'dry_run_virtual'        => array_values((array) ($sessionData['dry_run_virtual'] ?? [])),
			'import_history_id'      => (int) ($sessionData['import_history_id'] ?? 0),
		];
		$mapping = json_encode($mappingPayload);

		// UPSERT : INSERT … ON DUPLICATE KEY UPDATE
		$query = 'INSERT INTO ' . $db->quoteName('#__collector_import_sessions')
			. ' (' . implode(', ', array_map([$db, 'quoteName'], [
				'collection', 'created_by', 'file', 'first_line', 'mapping',
				'current_row', 'total_rows', 'imported', 'skipped', 'merged', 'updated',
			])) . ')'
			. ' VALUES ('
			. $collection . ', '
			. $userId . ', '
			. $db->quote($sessionData['file']) . ', '
			. (int) $sessionData['first_line'] . ', '
			. $db->quote($mapping) . ', '
			. (int) $sessionData['current_row'] . ', '
			. (int) $sessionData['total_rows'] . ', '
			. (int) $sessionData['imported'] . ', '
			. (int) $sessionData['skipped'] . ', '
			. (int) $sessionData['merged'] . ', '
			. $db->quote($now)
			. ')'
			. ' ON DUPLICATE KEY UPDATE '
			. $db->quoteName('file')        . ' = VALUES(' . $db->quoteName('file')        . '), '
			. $db->quoteName('first_line')  . ' = VALUES(' . $db->quoteName('first_line')  . '), '
			. $db->quoteName('mapping')     . ' = VALUES(' . $db->quoteName('mapping')     . '), '
			. $db->quoteName('current_row') . ' = VALUES(' . $db->quoteName('current_row') . '), '
			. $db->quoteName('total_rows')  . ' = VALUES(' . $db->quoteName('total_rows')  . '), '
			. $db->quoteName('imported')    . ' = VALUES(' . $db->quoteName('imported')    . '), '
			. $db->quoteName('skipped')     . ' = VALUES(' . $db->quoteName('skipped')     . '), '
			. $db->quoteName('merged')      . ' = VALUES(' . $db->quoteName('merged')      . '), '
			. $db->quoteName('updated')     . ' = VALUES(' . $db->quoteName('updated')     . ')';

		$db->setQuery($query);

		try {
			$db->execute();
			return true;
		} catch (\RuntimeException $e) {
			$this->setError($e->getMessage());
			return false;
		}
	}

	/**
	 * Charge la session d'import sauvegardée pour un utilisateur et une collection.
	 * Retourne null si aucune session n'existe.
	 */
	public function getSession(int $collection, int $userId): ?array
	{
		$db = $this->getDbo();

		$query = $db->getQuery(true)
			->select('*')
			->from($db->quoteName('#__collector_import_sessions'))
			->where($db->quoteName('collection') . ' = ' . $collection)
			->where($db->quoteName('created_by') . ' = ' . $userId);

		$db->setQuery($query);
		$row = $db->loadObject();

		// On vérifie uniquement que le fichier est renseigné.
		// current_row peut être 0 aux étapes 1-3 — ne pas l'utiliser comme condition.
		if (!$row || !$row->file) {
			return null;
		}

		$payload = json_decode($row->mapping, true);
		if (!is_array($payload)) { $payload = []; }

		// Rétrocompatibilité : ancienne structure plate (colmap direct)
		if (isset($payload['colmap'])) {
			$colmap       = $payload['colmap']       ?? [];
			$valueMapping = $payload['value_mapping'] ?? [];
			$step         = (int) ($payload['step']  ?? 1);
			$duplicateStrategy = (string) ($payload['duplicate_strategy'] ?? 'unik');
			$compositeFields   = is_array($payload['composite_fields'] ?? null) ? $payload['composite_fields'] : [];
			$publishing   = $payload['publishing']   ?? [];
		} else {
			$colmap       = $payload; // ancienne structure
			$valueMapping = [];
			$step         = 2;
			$duplicateStrategy = 'unik';
			$compositeFields   = [];
			$publishing   = [];
		}

		return [
			'file'                   => $row->file,
			'first_line'             => (int) $row->first_line,
			'mapping'                => $colmap,
			'value_mapping'          => $valueMapping,
			'step'                   => $step,
			'duplicate_strategy'     => $duplicateStrategy,
			'composite_fields'       => $compositeFields,
			'dry_run'                => (bool)  ($payload['dry_run']        ?? false),
			'dry_run_report'         => (array) ($payload['dry_run_report'] ?? []),
			'dry_run_conflicts'      => (int) ($payload['dry_run_conflicts']      ?? 0),
			'dry_run_multiconflicts' => (int) ($payload['dry_run_multiconflicts'] ?? 0),
			'dry_run_virtual'        => (array) ($payload['dry_run_virtual'] ?? []),
			'publishing'             => $publishing,
			'current_row'            => (int) $row->current_row,
			'total_rows'             => (int) $row->total_rows,
			'imported'               => (int) $row->imported,
			'skipped'                => (int) $row->skipped,
			'merged'                 => (int) $row->merged,
			'updated'                => $row->updated,
			'errors'                 => (int) ($payload['errors'] ?? 0),
			'import_history_id'      => (int) ($payload['import_history_id'] ?? 0),
		];
	}

	/**
	 * Supprime la session d'import (fin ou abandon).
	 */
	public function deleteSession(int $collection, int $userId): void
	{
		$db = $this->getDbo();

		$query = $db->getQuery(true)
			->delete($db->quoteName('#__collector_import_sessions'))
			->where($db->quoteName('collection') . ' = ' . $collection)
			->where($db->quoteName('created_by') . ' = ' . $userId);

		$db->setQuery($query);
		$db->execute();
	}

	// ── Configuration permanente d'import ────────────────────────────────────
	//
	// Contrairement à la session (état d'un import en cours, supprimée en fin
	// d'import), la config est conservée indéfiniment par collection.
	// Elle pré-remplit les étapes 2 et 3 lors de l'import d'un nouveau fichier.

	/**
	 * Sauvegarde la configuration de mapping pour une collection.
	 * Crée ou met à jour la ligne dans #__collector_import_config.
	 *
	 * @param  int    $collection
	 * @param  array  $colMapping    [tablecolumn => 'A']
	 * @param  array  $valueMapping  [tablecolumn => ['empty'=>id, 'values'=>[…]]]
	 * @param  int    $firstLine
	 */
	public function saveConfig(int $collection, array $colMapping, array $valueMapping,
                           int $firstLine, string $strategy = 'unik',
                           array $compositeFields = []): bool
	{
		$db  = $this->getDbo();
		$now = Factory::getDate()->toSql();

		$query = 'INSERT INTO ' . $db->quoteName('#__collector_import_config')
			. ' (' . implode(', ', array_map([$db, 'quoteName'], [
				'collection', 'col_mapping', 'value_mapping',
				'first_line', 'duplicate_strategy', 'composite_fields', 'updated',
			])) . ')'
			. ' VALUES ('
			. $collection                              . ', '
			. $db->quote(json_encode($colMapping))     . ', '
			. $db->quote(json_encode($valueMapping))   . ', '
			. (int) $firstLine                         . ', '
			. $db->quote($strategy)                    . ', '  // ← quote() indispensable
			. $db->quote(json_encode($compositeFields)). ', '
			. $db->quote($now)
			. ')'
			. ' ON DUPLICATE KEY UPDATE '
			. $db->quoteName('col_mapping')          . ' = VALUES(' . $db->quoteName('col_mapping')          . '), '
			. $db->quoteName('value_mapping')        . ' = VALUES(' . $db->quoteName('value_mapping')        . '), '
			. $db->quoteName('first_line')           . ' = VALUES(' . $db->quoteName('first_line')           . '), '
			. $db->quoteName('duplicate_strategy')   . ' = VALUES(' . $db->quoteName('duplicate_strategy')   . '), '
			. $db->quoteName('composite_fields')     . ' = VALUES(' . $db->quoteName('composite_fields')     . '), '
			. $db->quoteName('updated')              . ' = VALUES(' . $db->quoteName('updated')              . ')';

		$db->setQuery($query);

		try {
			$db->execute();
			return true;
		} catch (\RuntimeException $e) {
			$this->setError($e->getMessage());
			return false;
		}
	}

	/**
	 * Charge la configuration de mapping sauvegardée pour une collection.
	 * Retourne null si aucune config n'existe encore.
	 *
	 * @return array|null  ['col_mapping'=>[], 'value_mapping'=>[], 'first_line'=>2]
	 */
	public function getConfig(int $collection): ?array
	{
		$db = $this->getDbo();

		$query = $db->getQuery(true)
			->select(['col_mapping', 'value_mapping', 'first_line', 'duplicate_strategy', 'composite_fields'])
			->from($db->quoteName('#__collector_import_config'))
			->where($db->quoteName('collection') . ' = ' . $collection);

		$db->setQuery($query);
		$row = $db->loadObject();

		if (!$row) {
			return null;
		}

		$colMapping   = json_decode($row->col_mapping,   true) ?: [];
		$valueMapping = json_decode($row->value_mapping, true) ?: [];
		$compositeFields = json_decode($row->composite_fields ?? '[]', true) ?: [];

		return [
			'col_mapping'   => $colMapping,
			'value_mapping' => $valueMapping,
			'first_line'    => (int) $row->first_line,
			'duplicate_strategy' => $row->duplicate_strategy ?? 'unik',
			'composite_fields'   => $compositeFields,
		];
	}

}