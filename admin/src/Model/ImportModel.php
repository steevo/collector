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
	/**
	 * @param  bool  $jokers  Accepter aussi les objets dont la valeur texte en base est incomplète
	 *                        (« ** », « K*** », « ? ») : recherche de repli quand la recherche
	 *                        normale ne trouve rien ; ces valeurs ne comptent pas comme absentes
	 */
	public function checkDuplicate(int $collection, array $data, array $fields, string $strategy = 'unik',
	                               array $compositeCols = [], bool $jokers = false): object|array|null
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
		$unknown = []; // « valeur absente en base » pour chaque champ cherché
		foreach ($searchCols as $col) {
			if (($data[$col] ?? '') === '') continue;
			$field = $fieldsByCol[$col] ?? null;
			$cond  = $field
				? $field->getSearchCondition('h', (string) $data[$col], $db)
				: $db->quoteName('h.' . $col) . ' = ' . $db->quote($data[$col]);

			// Valeur vide en base (année, échelle… inconnue) : l'objet reste un candidat ;
			// la fusion complétera la valeur
			$q          = $db->quoteName('h.' . $col);
			$isUnknown  = '(' . $q . ' IS NULL OR ' . $q . ' IN (\'\', \'0\'))';
			$unknown[]  = $isUnknown;

			// Référence incomplète en base (« ** », « K*** ») : à faire vérifier
			$isJoker = ($jokers && $field) ? $field->getUnknownValueCondition('h', $db) : '';
			$conditions[] = '(' . $cond . ' OR ' . $isUnknown . ($isJoker !== '' ? ' OR ' . $isJoker : '') . ')';
		}

		if (empty($conditions)) {
			return null;
		}

		foreach ($conditions as $cond) {
			$query->where($cond);
		}

		// Au plus une valeur absente en base : sinon un objet presque vide
		// correspondrait à toutes les lignes du fichier
		if (count($unknown) > 1) {
			$query->where('(' . implode(' + ', $unknown) . ') <= 1');
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
	 *
	 * @param  bool  $applyEmpty  Valeurs choisies une à une : une valeur vide efface celle de l'objet
	 */
	public function mergeItem(int $collection, int $existingId, array $importData, object $params, array $publishing,
	                          bool $applyEmpty = false): array|false
	{
		$db    = $this->getDbo();
		$table = new CollectorItemsTable($db);
		$table->initVersion($collection);
		$table->load($existingId);
		$table->initVersion($collection); // recharge la structure history

		// Charge les données historiques de la version active
		$table->loadVersion($collection, $existingId);

		// Applique les valeurs importées : les champs vides ne remplacent pas,
		// sauf s'ils ont été choisis explicitement
		foreach ($importData as $col => $value) {
			$value = (string) ($value ?? '');
			if ($col !== 'collection' && ($value !== '' || $applyEmpty)) {
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

	/** @var string Dernière erreur de createItem() */
	public string $lastCreateError = '';

	/**
	 * Crée un objet avec ses valeurs de champs (nouvelle version active).
	 *
	 * @return array{item_id: int, version_after: int}|false
	 */
	public function createItem(int $collection, array $data, array $publishing = []): array|false
	{
		$table = new CollectorItemsTable($this->getDbo());
		$table->initVersion($collection);

		$data['collection']   = $collection;
		$data['state']        = (int) ($publishing['state']  ?? 1);
		$data['access']       = (int) ($publishing['access'] ?? 1);
		$data['publish_up']   = ($publishing['publish_up']   ?? null) ?: null;
		$data['publish_down'] = ($publishing['publish_down'] ?? null) ?: null;

		if (!$table->bind($data)) {
			$this->lastCreateError = 'bind failed: ' . $table->getError();
			return false;
		}
		if (!$table->checkVersion()) {
			$this->lastCreateError = 'checkVersion failed: ' . $table->getError();
			return false;
		}

		$importDate          = Factory::getDate()->format(Text::_('DATE_FORMAT_LC2'));
		$table->modification = Text::sprintf('COM_COLLECTOR_IMPORT_VERSION_NOTE', $importDate);

		if (!(bool) $table->storeVersion(null, 1)) {
			$this->lastCreateError = 'storeVersion failed: ' . $table->getError();
			return false;
		}
		$this->lastCreateError = '';

		return [
			'item_id'       => (int) $table->id,
			'version_after' => (int) ($table->history->id ?? 0),
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
	/**
	 * @param  string|null  $origin   D'où vient la décision : 'auto' (aucune correspondance),
	 *                                'conflict' (choisi dans un conflit), 'report' (rapport de
	 *                                simulation), 'rule' (règle générale), 'history', 'split'
	 * @param  int|null     $fileRow  Ligne du fichier
	 */
	public function addImportHistoryItem(int $importId, int $itemId,
                                     string $action, ?int $versionBefore,
                                     ?int $versionAfter, ?string $origin = null, ?int $fileRow = null): bool
	{
		$db    = $this->getDbo();
		$table = new CollectorImportHistoryItemsTable($db);
		$table->import_id      = $importId;
		$table->item_id        = $itemId;
		$table->action         = $action;
		$table->version_before = $versionBefore;
		$table->version_after  = $versionAfter;

		// Date de l'action : sans suivi des versions, une modification ultérieure écrase
		// la version active au lieu d'en créer une ; seule la date permet de la repérer.
		// (Colonne ajoutée en 0.9.7 : ignorée tant que la base n'est pas à jour.)
		if ($table->hasField('done_at')) {
			$table->done_at = Factory::getDate()->toSql();
		}
		if ($table->hasField('origin')) {
			$table->origin   = $origin;
			$table->file_row = $fileRow ?: null;
		}

		return $table->check() && $table->store();
	}

	/**
	 * Note les valeurs de listes créées par un import, pour pouvoir les supprimer
	 * si l'import est annulé et que plus rien ne les utilise.
	 * (Table ajoutée en 0.9.7 : ignorée tant que la base n'est pas à jour.)
	 *
	 * @param  array  $values  [['defined' => int, 'id' => int, 'content' => string], …]
	 */
	public function addImportHistoryValues(int $importId, array $values): void
	{
		$db  = $this->getDbo();
		$now = Factory::getDate()->toSql();

		try {
			foreach ($values as $v) {
				$row = (object) [
					'import_id' => $importId,
					'defined'   => (int) $v['defined'],
					'value_id'  => (int) $v['id'],
					'content'   => mb_substr((string) $v['content'], 0, 255),
					'created'   => $now,
				];
				$db->insertObject('#__collector_import_history_values', $row);
			}
		} catch (\Throwable $e) {
			error_log('Collector import : valeurs créées non enregistrées (' . $e->getMessage() . ')');
		}
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

			// Valeur qui serait créée par l'import (simulation)
			if (is_string($value) && str_starts_with($value, CollectorField::NEW_PREFIX)) {
				$display[$col] = substr($value, strlen(CollectorField::NEW_PREFIX)) . ' ' . Text::_('COM_COLLECTOR_IMPORT_NEW_VALUE');
				continue;
			}

			$displayed = $field->display($value, false, $params);
			$display[$col] = self::displayOrRaw($field, (string) $value, $displayed);
		}

		return $display;
	}

	/**
	 * Texte affiché d'une valeur ; si l'affichage ne donne aucun texte (une image
	 * s'affiche en HTML), la valeur brute (chemin du fichier…) pour la comparer.
	 */
	private static function displayOrRaw(object $field, string $value, $displayed): string
	{
		$text = self::compactDisplay(($displayed === false || $displayed === '') ? $value : (string) $displayed);

		if ($text === '' && $field->normalizeForComparison($value) !== '') {
			$text = self::compactDisplay(implode(' ', array_filter(array_map('trim', explode('|', $value)))));
		}

		return $text;
	}

	/**
	 * Texte affichable sur une ligne : sans balises ni blancs superflus
	 * (le rapport de simulation conserve ces affichages pour chaque conflit).
	 */
	private static function compactDisplay(string $html): string
	{
		$text = html_entity_decode(strip_tags($html), ENT_QUOTES | ENT_HTML5, 'UTF-8');
		return trim(preg_replace('/\s+/u', ' ', str_replace("\xC2\xA0", ' ', $text)) ?? '');
	}

	/**
	 * Libellés des champs qui diffèrent entre les données importées et un objet en base
	 * (même règle que isIdentical) : explique pourquoi un conflit n'est pas « identique ».
	 *
	 * @return string[]
	 */
	public function getDiffFields(array $importData, object $existingItem, array $fields): array
	{
		$diff = [];
		foreach ($fields as $field) {
			$col = $field->_field->tablecolumn;
			if ($field->normalizeForComparison((string) ($importData[$col] ?? ''))
				!== $field->normalizeForComparison((string) ($existingItem->$col ?? ''))) {
				$diff[] = (string) $field->_field->field;
			}
		}
		return $diff;
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
			$display[$col] = self::displayOrRaw($field, (string) $value, $displayed);
		}

		return $display;
	}
	/**
	 * Objets en base de même nature que le fichier (mêmes valeurs pour les champs de
	 * comparaison qui ne varient pas d'une ligne à l'autre, par ex. Type = 405)
	 * qu'aucune ligne n'a retrouvés, ni à l'identique ni par ressemblance.
	 *
	 * @param  array  $scope      [colonne => valeur commune | null si elle varie]
	 * @param  int[]  $matched    Objets retrouvés par le fichier
	 * @param  array  $scopeSets  Périmètre réglé pour la collection : [colonne => valeurs du fichier] ;
	 *                            s'il est renseigné, il remplace les valeurs communes
	 * @return array{scope: array, total: int, items: array}  items limités à 300
	 */
	public function getMissingItems(int $collection, array $scope, array $matched, array $fields,
	                                array $scopeSets = []): array
	{
		$labels = [];
		foreach ($fields as $field) {
			$labels[$field->_field->tablecolumn] = $field;
		}

		$conditions = [];
		$scopeText  = [];
		$db         = $this->getDbo();

		// Périmètre réglé : objets dont la valeur fait partie de celles du fichier
		foreach ($scopeSets as $col => $values) {
			$values = array_values(array_filter(array_map('strval', (array) $values),
				fn($v) => $v !== '' && $v !== '0'));
			if (!$values || !isset($labels[$col])) {
				continue;
			}
			$conditions[] = $db->quoteName('h.' . $col) . ' IN (' . implode(',', array_map([$db, 'quote'], $values)) . ')';
			$shown = [];
			foreach ($values as $value) {
				$display = $this->getDisplayData($collection, [$col => $value], [$labels[$col]]);
				$shown[] = $display[$col] ?? $value;
			}
			sort($shown, SORT_NATURAL | SORT_FLAG_CASE);
			$scopeText[] = $labels[$col]->_field->field . ' : ' . implode(', ', $shown);
		}
		if ($scopeSets) {
			$scope = []; // le réglage remplace les valeurs communes
		}

		foreach ($scope as $col => $value) {
			if ($value === null || $value === '' || $value === '0' || !isset($labels[$col])) {
				continue;
			}
			$conditions[] = $db->quoteName('h.' . $col) . ' = ' . $db->quote($value);
			$display      = $this->getDisplayData($collection, [$col => $value], [$labels[$col]]);
			$scopeText[]  = $labels[$col]->_field->field . ' = ' . ($display[$col] ?? $value);
		}

		// Sans valeur commune, la comparaison porterait sur toute la collection : pas de liste
		if (!$conditions) {
			return ['scope' => [], 'total' => 0, 'items' => []];
		}

		$query = $db->getQuery(true)
			->select('h.item')
			->from($db->quoteName('#__collector_items_history_' . $collection, 'h'))
			->join('INNER', $db->quoteName('#__collector_items', 'i') . ' ON i.id = h.item')
			->where('h.state = 1')
			->where('i.state >= 0')
			->order('h.item');

		foreach ($conditions as $condition) {
			$query->where($condition);
		}
		if ($matched) {
			$query->where('h.item NOT IN (' . implode(',', array_map('intval', $matched)) . ')');
		}

		$ids   = array_map('intval', $db->setQuery($query)->loadColumn() ?: []);
		$items = [];
		foreach (array_slice($ids, 0, 300) as $id) {
			$items[] = ['id' => $id, 'display' => $this->getItemDisplayData($collection, $id, $fields)];
		}

		return ['scope' => $scopeText, 'total' => count($ids), 'items' => $items, 'ids' => $ids];
	}

	/**
	 * Note dans l'historique les objets de la base absents du fichier (fin d'import réel).
	 *
	 * @param  array  $missing  Résultat de getMissingItems()
	 */
	public function saveImportHistoryMissing(int $importId, array $missing): void
	{
		$db = $this->getDbo();
		$db->setQuery(
			$db->getQuery(true)
				->update($db->quoteName('#__collector_import_history'))
				->set($db->quoteName('missing_items') . ' = ' . $db->quote(json_encode([
					'scope' => array_values((array) ($missing['scope'] ?? [])),
					'ids'   => array_values(array_map('intval', (array) ($missing['ids'] ?? []))),
					// Ligne pour laquelle l'objet avait été trouvé, puis écarté [id => ligne]
					'rows'  => (object) array_map('intval', (array) ($missing['rows'] ?? [])),
					// Lignes ignorées car identiques à une ligne précédente du fichier
					'duplicates' => array_values((array) ($missing['duplicates'] ?? [])),
				])))
				->where($db->quoteName('id') . ' = ' . $importId)
		)->execute();
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
			'decisions'              => is_array($sessionData['decisions'] ?? null) ? $sessionData['decisions'] : [],
			'default_decision'       => (string) ($sessionData['default_decision'] ?? 'ask'),
			'similar_decision'       => (string) ($sessionData['similar_decision'] ?? 'ask'),
			'intra_decision'         => (string) ($sessionData['intra_decision'] ?? 'ask'),
			'similar_keys'           => array_values((array) ($sessionData['similar_keys'] ?? [])),
			'scope_fields'           => array_values((array) ($sessionData['scope_fields'] ?? [])),
			'scope_values'           => (object) array_map(fn($v) => array_values(array_map('strval', (array) $v)),
			                                               (array) ($sessionData['scope_values'] ?? [])),
			'cand_rows'              => (object) array_map('intval', (array) ($sessionData['cand_rows'] ?? [])),
			'file_duplicates'        => array_values((array) ($sessionData['file_duplicates'] ?? [])),
			'similar_threshold'      => (int) ($sessionData['similar_threshold'] ?? 80),
			// [objet => ligne qui l'a retenu]
			'claimed'                => (object) array_map('intval', array_filter((array) ($sessionData['claimed'] ?? []))),
			'own_rows'               => (object) array_map('intval', (array) ($sessionData['own_rows'] ?? [])),
			'dry_run_similar'        => (int) ($sessionData['dry_run_similar'] ?? 0),
			'dry_run_matched'        => array_values(array_map('intval', (array) ($sessionData['dry_run_matched'] ?? []))),
			'dry_run_scope'          => (array) ($sessionData['dry_run_scope'] ?? []),
			'dry_run_missing'        => (array) ($sessionData['dry_run_missing'] ?? []),
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
			'decisions'              => is_array($payload['decisions'] ?? null) ? $payload['decisions'] : [],
			'default_decision'       => (string) ($payload['default_decision'] ?? 'ask'),
			'similar_decision'       => (string) ($payload['similar_decision'] ?? 'ask'),
			'intra_decision'         => (string) ($payload['intra_decision'] ?? 'ask'),
			'similar_keys'           => array_values((array) ($payload['similar_keys'] ?? [])),
			'scope_fields'           => array_values((array) ($payload['scope_fields'] ?? [])),
			'scope_values'           => array_map(fn($v) => array_values(array_map('strval', (array) $v)),
			                                      (array) ($payload['scope_values'] ?? [])),
			'cand_rows'              => array_map('intval', (array) ($payload['cand_rows'] ?? [])),
			'file_duplicates'        => array_values((array) ($payload['file_duplicates'] ?? [])),
			'similar_threshold'      => (int) ($payload['similar_threshold'] ?? 80),
			// Ancien format : liste d'objets sans la ligne
			'claimed'                => array_is_list((array) ($payload['claimed'] ?? []))
				? array_fill_keys(array_map('intval', (array) ($payload['claimed'] ?? [])), true)
				: array_map('intval', (array) $payload['claimed']),
			'own_rows'               => array_map('intval', (array) ($payload['own_rows'] ?? [])),
			'dry_run_similar'        => (int) ($payload['dry_run_similar'] ?? 0),
			'dry_run_matched'        => (array) ($payload['dry_run_matched'] ?? []),
			'dry_run_scope'          => (array) ($payload['dry_run_scope'] ?? []),
			'dry_run_missing'        => (array) ($payload['dry_run_missing'] ?? []),
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
                           array $compositeFields = [], ?array $similarKeys = null,
                           ?int $similarThreshold = null, ?array $scopeFields = null): bool
	{
		$db  = $this->getDbo();
		$now = Factory::getDate()->toSql();

		$ok = $this->saveConfigBase($collection, $colMapping, $valueMapping, $firstLine, $strategy, $compositeFields, $now);

		// Rapprochement par ressemblance (colonnes ajoutées en 0.9.7)
		if ($ok && ($similarKeys !== null || $similarThreshold !== null) && $this->hasSimilarColumns()) {
			$set = [];
			if ($similarKeys !== null) {
				$set[] = $db->quoteName('similar_keys') . ' = ' . $db->quote(json_encode(array_values($similarKeys)));
			}
			if ($similarThreshold !== null) {
				$set[] = $db->quoteName('similar_threshold') . ' = ' . (int) $similarThreshold;
			}
			$db->setQuery(
				'UPDATE ' . $db->quoteName('#__collector_import_config') . ' SET ' . implode(', ', $set)
				. ' WHERE ' . $db->quoteName('collection') . ' = ' . $collection
			)->execute();
		}

		// Périmètre du fichier (colonne ajoutée en 0.9.7)
		if ($ok && $scopeFields !== null && $this->hasConfigColumn('scope_fields')) {
			$db->setQuery(
				'UPDATE ' . $db->quoteName('#__collector_import_config')
				. ' SET ' . $db->quoteName('scope_fields') . ' = ' . $db->quote(json_encode(array_values($scopeFields)))
				. ' WHERE ' . $db->quoteName('collection') . ' = ' . $collection
			)->execute();
		}

		return $ok;
	}

	/** Une colonne de la configuration d'import existe-t-elle (base à jour) ? */
	private function hasConfigColumn(string $column): bool
	{
		static $cols = null;
		if ($cols === null) {
			$cols = $this->getDbo()->getTableColumns('#__collector_import_config', false);
		}
		return isset($cols[$column]);
	}

	/** Les colonnes du rapprochement existent-elles (base à jour) ? */
	private function hasSimilarColumns(): bool
	{
		static $has = null;
		if ($has === null) {
			$cols = $this->getDbo()->getTableColumns('#__collector_import_config', false);
			$has  = isset($cols['similar_keys'], $cols['similar_threshold']);
		}
		return $has;
	}

	private function saveConfigBase(int $collection, array $colMapping, array $valueMapping,
	                                int $firstLine, string $strategy, array $compositeFields, string $now): bool
	{
		$db  = $this->getDbo();

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
			->select('*')
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
			'similar_keys'       => json_decode((string) ($row->similar_keys ?? '[]'), true) ?: [],
			'similar_threshold'  => (int) ($row->similar_threshold ?? 80) ?: 80,
			'scope_fields'       => json_decode((string) ($row->scope_fields ?? '[]'), true) ?: [],
		];
	}

}