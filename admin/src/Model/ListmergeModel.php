<?php
/**
 * Joomla! component Collector
 *
 * @package     Collector
 * @copyright   Copyright (C) 2010 - 2026 Philippe Ousset. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace Steevo\Component\Collector\Administrator\Model;

defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\Model\BaseDatabaseModel;
use Steevo\Component\Collector\Administrator\Table\CollectorDefinedContentTable;
use Steevo\Component\Collector\Administrator\Table\CollectorItemsTable;

/**
 * Fusion de deux valeurs d'une liste prédéfinie.
 *
 * La valeur « source » disparaît au profit de la valeur « cible » :
 *  - toutes les versions des objets (pas seulement la version active) qui
 *    utilisent la source passent sur la cible, dans tous les champs « define »
 *    reliés à la liste, toutes collections confondues ;
 *  - les enfants de la source passent sous la cible ; un enfant qui porte le
 *    même libellé qu'un enfant de la cible est fusionné avec lui (récursif) ;
 *  - les correspondances de valeurs de l'import (configuration enregistrée et
 *    sessions en cours) et les filtres imposés par les éléments de menu sont
 *    reportés sur la cible ;
 *  - l'arbre de la table #__collector_defined_content est reconstruit, puis le
 *    titre complet (fulltitle) et l'alias des objets touchés sont recalculés.
 *
 * Un champ « define » ne contient qu'un seul id (jointure simple sur
 * #__collector_defined_content.id dans CollectorFieldDefine::setQuery).
 *
 * @package  Collector
 */
class ListmergeModel extends BaseDatabaseModel
{
	/** @var array<int, array> Champs define par liste */
	private array $fieldsCache = [];

	/** @var array<string, bool>|null Tables existantes */
	private ?array $tables = null;

	/**
	 * Retourne la liste prédéfinie.
	 */
	public function getList(int $defined): ?object
	{
		$db = $this->getDatabase();
		$db->setQuery(
			$db->getQuery(true)
				->select(['id', 'name'])
				->from($db->quoteName('#__collector_defined'))
				->where($db->quoteName('id') . ' = ' . $defined)
		);

		return $db->loadObject() ?: null;
	}

	/**
	 * Valeurs de la liste, dans l'ordre de l'arbre (pour les listes déroulantes).
	 *
	 * @return object[]
	 */
	public function getValues(int $defined): array
	{
		$db = $this->getDatabase();
		$db->setQuery(
			$db->getQuery(true)
				->select(['id', 'content', 'level', 'parent_id'])
				->from($db->quoteName('#__collector_defined_content'))
				->where($db->quoteName('defined') . ' = ' . $defined)
				->order($db->quoteName('lft') . ' ASC')
		);

		return $db->loadObjectList() ?: [];
	}

	/**
	 * Champs de type « define » reliés à la liste, dans toutes les collections,
	 * dont la table d'historique existe.
	 *
	 * @return object[]  id, collection, collection_name, field, tablecolumn, history, intitle
	 */
	public function getDefineFields(int $defined): array
	{
		if (isset($this->fieldsCache[$defined])) {
			return $this->fieldsCache[$defined];
		}

		$db = $this->getDatabase();
		$db->setQuery(
			$db->getQuery(true)
				->select(['f.id', 'f.collection', 'f.field', 'f.tablecolumn', 'f.attribs', 'f.home', 'c.name AS collection_name', 'c.custom'])
				->from($db->quoteName('#__collector_fields', 'f'))
				->join('INNER', $db->quoteName('#__collector_fields_type', 't') . ' ON t.id = f.type')
				->join('LEFT', $db->quoteName('#__collector', 'c') . ' ON c.id = f.collection')
				->where('t.type = ' . $db->quote('define'))
				->order('f.collection, f.ordering')
		);

		$fields = [];

		foreach ($db->loadObjectList() ?: [] as $field) {
			$attribs = json_decode((string) $field->attribs, true) ?: [];

			if ((int) ($attribs['list'] ?? 0) !== $defined) {
				continue;
			}

			$field->history = '#__collector_items_history_' . (int) $field->collection;

			if (!$this->tableExists($field->history) || !preg_match('/^\w+$/', (string) $field->tablecolumn)) {
				continue;
			}

			// Champ utilisé dans le titre complet des objets ?
			$custom          = (string) $field->custom;
			$field->intitle  = ($custom !== '' && $custom !== '0')
				? in_array((string) $field->id, explode('/', $custom), true)
				: (int) $field->home === 1;

			unset($field->attribs, $field->custom, $field->home);
			$fields[] = $field;
		}

		return $this->fieldsCache[$defined] = $fields;
	}

	/**
	 * Nombre d'objets et de versions qui utilisent chaque valeur.
	 *
	 * Un objet qui utilise la valeur dans deux champs de la même collection
	 * (listes Oui/Non par exemple) n'est compté qu'une fois.
	 *
	 * @param   int    $defined  Liste
	 * @param   int[]  $ids      Valeurs (vide = toutes celles de la liste)
	 *
	 * @return  array<int, array{items:int, active:int, versions:int}>
	 *          items = objets dont au moins une version utilise la valeur,
	 *          active = objets dont la version en cours l'utilise
	 */
	public function getUsage(int $defined, array $ids = []): array
	{
		$db     = $this->getDatabase();
		$ids    = array_values(array_unique(array_filter(array_map('intval', $ids))));
		$byColl = [];
		$usage  = [];

		foreach ($this->getDefineFields($defined) as $field) {
			$byColl[$field->collection][] = $field;
		}

		$in = $ids ? ' IN (' . implode(',', array_map(fn ($id) => $db->quote((string) $id), $ids)) . ')' : null;

		foreach ($byColl as $fields) {
			$parts = [];

			foreach ($fields as $field) {
				$col     = $db->quoteName($field->tablecolumn);
				$parts[] = 'SELECT id, item, state, ' . $col . ' AS v FROM ' . $db->quoteName($field->history)
					. ' WHERE ' . ($in ? $col . $in : $col . ' <> ' . $db->quote(''));
			}

			$db->setQuery(
				'SELECT x.v, COUNT(DISTINCT x.item) AS items,'
				. ' COUNT(DISTINCT CASE WHEN x.state = 1 THEN x.item END) AS active,'
				. ' COUNT(DISTINCT x.id) AS versions'
				. ' FROM (' . implode(' UNION ALL ', $parts) . ') AS x GROUP BY x.v'
			);

			foreach ($db->loadObjectList() ?: [] as $row) {
				$id = (int) $row->v;

				if (!$id) {
					continue;
				}

				$usage[$id] ??= ['items' => 0, 'active' => 0, 'versions' => 0];
				$usage[$id]['items']    += (int) $row->items;
				$usage[$id]['active']   += (int) $row->active;
				$usage[$id]['versions'] += (int) $row->versions;
			}
		}

		return $usage;
	}

	/**
	 * Détail par champ de l'utilisation d'une valeur.
	 *
	 * @return array<int, array{field:object, items:int, active:int, versions:int}>
	 */
	public function getUsageByField(int $defined, int $id): array
	{
		$db     = $this->getDatabase();
		$result = [];

		foreach ($this->getDefineFields($defined) as $field) {
			$db->setQuery(
				$db->getQuery(true)
					->select([
						'COUNT(DISTINCT item) AS items',
						'COUNT(DISTINCT CASE WHEN state = 1 THEN item END) AS active',
						'COUNT(*) AS versions',
					])
					->from($db->quoteName($field->history))
					->where($db->quoteName($field->tablecolumn) . ' = ' . $db->quote((string) $id))
			);
			$row = $db->loadObject();

			if ($row && (int) $row->versions > 0) {
				$result[] = [
					'field'    => $field,
					'items'    => (int) $row->items,
					'active'   => (int) $row->active,
					'versions' => (int) $row->versions,
				];
			}
		}

		return $result;
	}

	/**
	 * Résumé de la fusion, sans rien modifier.
	 *
	 * @return object  errors[], list, source, target, sourceUsage, targetUsage,
	 *                 sourceFields, pairs, moves, import, sessions, menus
	 */
	public function getSummary(int $defined, int $sourceId, int $targetId): object
	{
		$summary = (object) [
			'errors'       => [],
			'list'         => $this->getList($defined),
			'source'       => null,
			'target'       => null,
			'sourceUsage'  => ['items' => 0, 'active' => 0, 'versions' => 0],
			'targetUsage'  => ['items' => 0, 'active' => 0, 'versions' => 0],
			'sourceFields' => [],
			'pairs'        => [],
			'moves'        => [],
			'import'       => 0,
			'sessions'     => 0,
			'menus'        => 0,
		];

		if (!$sourceId || !$targetId) {
			return $summary;
		}

		try {
			[$source, $target] = $this->validate($defined, $sourceId, $targetId);
		} catch (\RuntimeException $e) {
			$summary->errors[] = $e->getMessage();

			return $summary;
		}

		$summary->source = $source;
		$summary->target = $target;

		[$pairs, $moves] = $this->plan($sourceId, $targetId);

		$usage                 = $this->getUsage($defined, [$sourceId, $targetId]);
		$summary->sourceUsage  = $usage[$sourceId] ?? $summary->sourceUsage;
		$summary->targetUsage  = $usage[$targetId] ?? $summary->targetUsage;
		$summary->sourceFields = $this->getUsageByField($defined, $sourceId);
		$summary->pairs        = $this->labelPairs($defined, $pairs);
		$summary->moves        = $this->labelValues(array_column($moves, 0));

		$map               = array_column($pairs, 1, 0);
		$summary->import   = $this->updateImportConfig($defined, $map, false);
		$summary->sessions = $this->updateImportSessions($defined, $map, false);
		$summary->menus    = $this->updateMenus($defined, $map, false);

		return $summary;
	}

	/**
	 * Fusionne la valeur source dans la valeur cible.
	 *
	 * @return array  Compteurs : values, versions, items, titles, children, import, sessions, menus
	 *
	 * @throws \RuntimeException
	 */
	public function merge(int $defined, int $sourceId, int $targetId): array
	{
		$this->validate($defined, $sourceId, $targetId);

		$db = $this->getDatabase();
		[$pairs, $moves] = $this->plan($sourceId, $targetId);
		$map = array_column($pairs, 1, 0);

		$stats = [
			'values'   => count($pairs),
			'versions' => 0,
			'items'    => 0,
			'titles'   => 0,
			'children' => count($moves),
			'import'   => 0,
			'sessions' => 0,
			'menus'    => 0,
		];

		$db->transactionStart();

		try {
			// 1. Les enfants non homonymes passent sous leur nouvelle valeur
			//    parente, après les enfants qu'elle a déjà.
			if ($moves) {
				$db->setQuery('SELECT MAX(' . $db->quoteName('rgt') . ') FROM ' . $db->quoteName('#__collector_defined_content'));
				$base = (int) $db->loadResult() + 1;

				foreach ($moves as $i => [$childId, $parentId]) {
					$db->setQuery(
						$db->getQuery(true)
							->update($db->quoteName('#__collector_defined_content'))
							->set($db->quoteName('parent_id') . ' = ' . $db->quote((string) $parentId))
							->set($db->quoteName('lft') . ' = ' . ($base + $i))
							->where($db->quoteName('id') . ' = ' . (int) $childId)
					)->execute();
				}
			}

			// 2. Toutes les versions des objets
			$touched = []; // objets dont au moins une version change
			$retitle = []; // objets dont le titre complet change (version en cours, champ du titre)

			foreach ($this->getDefineFields($defined) as $field) {
				$col = $db->quoteName($field->tablecolumn);

				foreach ($map as $from => $to) {
					$db->setQuery(
						$db->getQuery(true)
							->select([$db->quoteName('item'), 'MAX(' . $db->quoteName('state') . ' = 1) AS active'])
							->from($db->quoteName($field->history))
							->where($col . ' = ' . $db->quote((string) $from))
							->group($db->quoteName('item'))
					);

					foreach ($db->loadObjectList() ?: [] as $row) {
						$touched[(int) $field->collection][(int) $row->item] = true;

						if ($field->intitle && (int) $row->active) {
							$retitle[(int) $field->collection][(int) $row->item] = true;
						}
					}

					$db->setQuery(
						$db->getQuery(true)
							->update($db->quoteName($field->history))
							->set($col . ' = ' . $db->quote((string) $to))
							->where($col . ' = ' . $db->quote((string) $from))
					)->execute();

					$stats['versions'] += (int) $db->getAffectedRows();
				}
			}

			// 3. Import, sessions d'import, menus
			$stats['import']   = $this->updateImportConfig($defined, $map, true);
			$stats['sessions'] = $this->updateImportSessions($defined, $map, true);
			$stats['menus']    = $this->updateMenus($defined, $map, true);

			// 4. Suppression des valeurs fusionnées, puis reconstruction de l'arbre
			$db->setQuery(
				$db->getQuery(true)
					->delete($db->quoteName('#__collector_defined_content'))
					->where($db->quoteName('id') . ' IN (' . implode(',', array_map('intval', array_keys($map))) . ')')
			)->execute();

			$table = new CollectorDefinedContentTable($db);

			if ($table->rebuild(1, 0, 0, '') === false) {
				throw new \RuntimeException(Text::_('COM_COLLECTOR_LISTMERGE_ERROR_REBUILD'));
			}

			// 5. Titres complets et alias des objets touchés
			foreach ($touched as $items) {
				$stats['items'] += count($items);
			}

			foreach ($retitle as $collection => $items) {
				$stats['titles'] += $this->refreshTitles((int) $collection, array_keys($items));
			}

			$db->transactionCommit();
		} catch (\Throwable $e) {
			$db->transactionRollback();

			throw new \RuntimeException($e->getMessage(), 0, $e);
		}

		$this->cleanCache('com_collector');

		return $stats;
	}

	/**
	 * Supprime une valeur que plus rien n'utilise (outil de nettoyage).
	 *
	 * Refusée si une version d'un objet (même ancienne) utilise la valeur ou si
	 * elle a des sous-valeurs. Les correspondances de l'import qui la désignaient
	 * redeviennent « à créer » (même traitement que l'annulation d'un import),
	 * les filtres de menu qui la désignaient sont vidés.
	 *
	 * @return string  Libellé de la valeur supprimée
	 *
	 * @throws \RuntimeException
	 */
	public function deleteUnused(int $id): string
	{
		$db    = $this->getDatabase();
		$value = $id ? $this->loadValue($id) : null;

		if (!$value || (int) $value->defined === 0) {
			throw new \RuntimeException(Text::_('COM_COLLECTOR_LISTMERGE_ERROR_NOT_FOUND'));
		}

		if ($this->loadChildren($id)) {
			throw new \RuntimeException(Text::sprintf('COM_COLLECTOR_LISTMERGE_ERROR_HAS_CHILDREN', $value->content));
		}

		$defined  = (int) $value->defined;
		$versions = (int) ($this->getUsage($defined, [$id])[$id]['versions'] ?? 0);

		if ($versions > 0) {
			throw new \RuntimeException(Text::plural('COM_COLLECTOR_LISTMERGE_ERROR_IN_USE', $versions, $value->content));
		}

		$db->transactionStart();

		try {
			$table = new CollectorDefinedContentTable($db);

			if (!$table->delete($id)) {
				throw new \RuntimeException((string) $table->getError());
			}

			$this->forgetValue($defined, $id);

			// Valeur créée par un import : notée comme supprimée dans son historique
			if ($this->tableExists('#__collector_import_history_values')) {
				$db->setQuery(
					$db->getQuery(true)
						->update($db->quoteName('#__collector_import_history_values'))
						->set($db->quoteName('removed_at') . ' = ' . $db->quote(Factory::getDate()->toSql()))
						->where($db->quoteName('value_id') . ' = ' . $id)
						->where($db->quoteName('removed_at') . ' IS NULL')
				)->execute();
			}

			$db->transactionCommit();
		} catch (\Throwable $e) {
			$db->transactionRollback();

			throw new \RuntimeException($e->getMessage(), 0, $e);
		}

		$this->cleanCache('com_collector');

		return (string) $value->content;
	}

	/**
	 * Oublie une valeur supprimée dans les correspondances de l'import
	 * (configuration et imports en cours : la valeur redevient « à créer ») et
	 * dans les filtres imposés par les éléments de menu (vidés).
	 */
	private function forgetValue(int $defined, int $id): void
	{
		$db     = $this->getDatabase();
		$forget = function (array &$valueMapping) use ($id): bool {
			$changed = false;

			foreach ($valueMapping as &$col) {
				if (!is_array($col)) {
					continue;
				}

				if ((string) ($col['empty'] ?? '') === (string) $id) {
					$col['empty'] = '';
					$changed      = true;
				}

				foreach (is_array($col['values'] ?? null) ? array_keys($col['values']) : [] as $k) {
					$pair = &$col['values'][$k];

					if (is_array($pair) && (string) ($pair['db'] ?? '') === (string) $id) {
						$pair['db']           = '';
						$pair['create']       = '1';
						$pair['create_label'] = ($pair['create_label'] ?? '') !== '' ? $pair['create_label'] : ($pair['file'] ?? '');
						$changed              = true;
					}

					unset($pair);
				}
			}

			unset($col);

			return $changed;
		};

		foreach (['#__collector_import_config' => 'value_mapping', '#__collector_import_sessions' => 'mapping'] as $table => $column) {
			if (!$this->tableExists($table)) {
				continue;
			}

			$rows = $db->setQuery(
				$db->getQuery(true)
					->select(['id', $db->quoteName($column, 'json')])
					->from($db->quoteName($table))
			)->loadObjectList() ?: [];

			foreach ($rows as $row) {
				$data = json_decode((string) $row->json, true);

				if (!is_array($data)) {
					continue;
				}

				// Session d'import : les correspondances sont sous la clé value_mapping
				$changed = $column === 'mapping'
					? (is_array($data['value_mapping'] ?? null) && $forget($data['value_mapping']))
					: $forget($data);

				if ($changed) {
					$db->setQuery(
						$db->getQuery(true)
							->update($db->quoteName($table))
							->set($db->quoteName($column) . ' = ' . $db->quote(json_encode($data)))
							->where($db->quoteName('id') . ' = ' . (int) $row->id)
					)->execute();
				}
			}
		}

		// Filtres de menu : la valeur n'existe plus, le filtre est vidé
		$this->updateMenus($defined, [$id => ''], true);
	}

	/**
	 * Vérifie que la fusion est possible.
	 *
	 * @return object[]  [source, target]
	 *
	 * @throws \RuntimeException
	 */
	private function validate(int $defined, int $sourceId, int $targetId): array
	{
		if ($sourceId === $targetId) {
			throw new \RuntimeException(Text::_('COM_COLLECTOR_LISTMERGE_ERROR_SAME'));
		}

		$source = $this->loadValue($sourceId);
		$target = $this->loadValue($targetId);

		if (!$defined || !$source || !$target || (int) $source->defined !== $defined || (int) $target->defined !== $defined) {
			throw new \RuntimeException(Text::_('COM_COLLECTOR_LISTMERGE_ERROR_NOT_IN_LIST'));
		}

		// La cible ne doit pas descendre de la source : elle deviendrait son propre parent.
		$node = $target;
		$seen = [];

		while ($node && (int) $node->parent_id > 1 && !isset($seen[$node->id])) {
			$seen[$node->id] = true;

			if ((int) $node->parent_id === $sourceId) {
				throw new \RuntimeException(Text::_('COM_COLLECTOR_LISTMERGE_ERROR_DESCENDANT'));
			}

			$node = $this->loadValue((int) $node->parent_id);
		}

		$source->labelPath = $this->labelPath($source);
		$target->labelPath = $this->labelPath($target);

		return [$source, $target];
	}

	/**
	 * Prépare la fusion : couples (source → cible) à fusionner, en descendant
	 * dans les enfants homonymes, et enfants à déplacer.
	 *
	 * @return array  [ [[from, to], ...], [[childId, newParentId], ...] ]
	 */
	private function plan(int $sourceId, int $targetId, int $depth = 0): array
	{
		$pairs = [[$sourceId, $targetId]];
		$moves = [];

		if ($depth > 20) {
			return [$pairs, $moves];
		}

		$targetChildren = [];

		foreach ($this->loadChildren($targetId) as $child) {
			if ((int) $child->id !== $sourceId) {
				$targetChildren[$this->normalize($child->content)] ??= (int) $child->id;
			}
		}

		foreach ($this->loadChildren($sourceId) as $child) {
			$same = $targetChildren[$this->normalize($child->content)] ?? null;

			if ($same && $same !== (int) $child->id) {
				[$subPairs, $subMoves] = $this->plan((int) $child->id, $same, $depth + 1);
				$pairs = array_merge($pairs, $subPairs);
				$moves = array_merge($moves, $subMoves);
			} else {
				$moves[] = [(int) $child->id, $targetId];
			}
		}

		return [$pairs, $moves];
	}

	/**
	 * Remplace les ids dans les correspondances de valeurs de l'import
	 * (#__collector_import_config.value_mapping).
	 *
	 * @param   array<int,int>  $map    from => to
	 * @param   bool            $write  false = compter seulement
	 *
	 * @return  int  Nombre de correspondances modifiées
	 */
	private function updateImportConfig(int $defined, array $map, bool $write): int
	{
		$colsByColl = $this->getColumnsByCollection($defined);

		if (!$colsByColl || !$this->tableExists('#__collector_import_config')) {
			return 0;
		}

		$db = $this->getDatabase();
		$db->setQuery(
			$db->getQuery(true)
				->select(['id', 'collection', 'value_mapping'])
				->from($db->quoteName('#__collector_import_config'))
				->where($db->quoteName('collection') . ' IN (' . implode(',', array_keys($colsByColl)) . ')')
		);

		$count = 0;

		foreach ($db->loadObjectList() ?: [] as $row) {
			$valueMapping = json_decode((string) $row->value_mapping, true);

			if (!is_array($valueMapping)) {
				continue;
			}

			$n = $this->replaceInValueMapping($valueMapping, $colsByColl[(int) $row->collection], $map);

			if ($n && $write) {
				$db->setQuery(
					$db->getQuery(true)
						->update($db->quoteName('#__collector_import_config'))
						->set($db->quoteName('value_mapping') . ' = ' . $db->quote(json_encode($valueMapping)))
						->where($db->quoteName('id') . ' = ' . (int) $row->id)
				)->execute();
			}

			$count += $n;
		}

		return $count;
	}

	/**
	 * Remplace les ids dans les sessions d'import en cours
	 * (#__collector_import_sessions.mapping : value_mapping et objets virtuels
	 * de la simulation). Seules ces deux clés sont touchées.
	 *
	 * @return  int  Nombre de sessions modifiées
	 */
	private function updateImportSessions(int $defined, array $map, bool $write): int
	{
		$colsByColl = $this->getColumnsByCollection($defined);

		if (!$colsByColl || !$this->tableExists('#__collector_import_sessions')) {
			return 0;
		}

		$db = $this->getDatabase();
		$db->setQuery(
			$db->getQuery(true)
				->select(['id', 'collection', 'mapping'])
				->from($db->quoteName('#__collector_import_sessions'))
				->where($db->quoteName('collection') . ' IN (' . implode(',', array_keys($colsByColl)) . ')')
		);

		$count = 0;

		foreach ($db->loadObjectList() ?: [] as $row) {
			$payload = json_decode((string) $row->mapping, true);

			if (!is_array($payload)) {
				continue;
			}

			$cols = $colsByColl[(int) $row->collection];
			$n    = 0;

			if (isset($payload['value_mapping']) && is_array($payload['value_mapping'])) {
				$n += $this->replaceInValueMapping($payload['value_mapping'], $cols, $map);
			}

			if (isset($payload['dry_run_virtual']) && is_array($payload['dry_run_virtual'])) {
				foreach ($payload['dry_run_virtual'] as &$virtual) {
					if (!is_array($virtual)) {
						continue;
					}

					foreach ($cols as $col) {
						$value = $virtual[$col] ?? null;

						if (is_scalar($value) && $this->isId($value) && isset($map[(int) $value])) {
							$virtual[$col] = (string) $map[(int) $value];
							$n++;
						}
					}
				}

				unset($virtual);
			}

			if ($n) {
				$count++;

				if ($write) {
					$db->setQuery(
						$db->getQuery(true)
							->update($db->quoteName('#__collector_import_sessions'))
							->set($db->quoteName('mapping') . ' = ' . $db->quote(json_encode($payload)))
							->where($db->quoteName('id') . ' = ' . (int) $row->id)
					)->execute();
				}
			}
		}

		return $count;
	}

	/**
	 * Remplace les ids dans un value_mapping :
	 * {tablecolumn: {empty: id, values: [{file, db, ...}]}}
	 *
	 * @return int  Nombre de remplacements
	 */
	private function replaceInValueMapping(array &$valueMapping, array $cols, array $map): int
	{
		$n = 0;

		foreach ($cols as $col) {
			if (!isset($valueMapping[$col]) || !is_array($valueMapping[$col])) {
				continue;
			}

			$colMap = &$valueMapping[$col];
			$empty  = $colMap['empty'] ?? null;

			if (is_scalar($empty) && $this->isId($empty) && isset($map[(int) $empty])) {
				$colMap['empty'] = (string) $map[(int) $empty];
				$n++;
			}

			if (isset($colMap['values']) && is_array($colMap['values'])) {
				foreach ($colMap['values'] as &$pair) {
					$db = is_array($pair) ? ($pair['db'] ?? null) : null;

					if (is_scalar($db) && $this->isId($db) && isset($map[(int) $db])) {
						$pair['db'] = (string) $map[(int) $db];
						$n++;
					}
				}

				unset($pair);
			}

			unset($colMap);
		}

		return $n;
	}

	/**
	 * Remplace les ids dans les filtres imposés par les éléments de menu
	 * (params.filter.filterfield_<tablecolumn>).
	 *
	 * @return int  Nombre d'éléments de menu modifiés
	 */
	private function updateMenus(int $defined, array $map, bool $write): int
	{
		$colsByColl = $this->getColumnsByCollection($defined);

		if (!$colsByColl) {
			return 0;
		}

		$db = $this->getDatabase();
		$db->setQuery(
			$db->getQuery(true)
				->select(['id', 'link', 'params'])
				->from($db->quoteName('#__menu'))
				->where($db->quoteName('link') . ' LIKE ' . $db->quote('index.php?option=com_collector%'))
				->where($db->quoteName('params') . ' LIKE ' . $db->quote('%filterfield_%'))
		);

		$count = 0;

		foreach ($db->loadObjectList() ?: [] as $row) {
			parse_str((string) parse_url($row->link, PHP_URL_QUERY), $request);
			$collection = (int) ($request['collection'] ?? (($request['view'] ?? '') === 'collection' ? ($request['id'] ?? 0) : 0));
			$params     = json_decode((string) $row->params, true);

			if (!isset($colsByColl[$collection]) || !is_array($params) || !is_array($params['filter'] ?? null)) {
				continue;
			}

			$changed = false;

			foreach ($colsByColl[$collection] as $col) {
				$value = $params['filter']['filterfield_' . $col] ?? null;

				if (is_scalar($value) && $this->isId($value) && isset($map[(int) $value])) {
					$params['filter']['filterfield_' . $col] = (string) $map[(int) $value];
					$changed = true;
				}
			}

			if ($changed) {
				$count++;

				if ($write) {
					$db->setQuery(
						$db->getQuery(true)
							->update($db->quoteName('#__menu'))
							->set($db->quoteName('params') . ' = ' . $db->quote(json_encode($params)))
							->where($db->quoteName('id') . ' = ' . (int) $row->id)
					)->execute();
				}
			}
		}

		return $count;
	}

	/**
	 * Recalcule le titre complet et l'alias des objets (d'après leur version en cours).
	 *
	 * @return int  Nombre de titres modifiés
	 */
	private function refreshTitles(int $collection, array $itemIds): int
	{
		if (!$itemIds) {
			return 0;
		}

		$db = $this->getDatabase();

		// Seuls les objets qui ont une version en cours ont un titre à recalculer
		$db->setQuery(
			$db->getQuery(true)
				->select(['i.id', 'i.fulltitle', 'i.alias'])
				->from($db->quoteName('#__collector_items', 'i'))
				->join('INNER', $db->quoteName('#__collector_items_history_' . $collection, 'h') . ' ON h.item = i.id AND h.state = 1')
				->where('i.id IN (' . implode(',', array_map('intval', $itemIds)) . ')')
		);

		$changed = 0;

		foreach ($db->loadObjectList() ?: [] as $item) {
			$table = new CollectorItemsTable($db);
			$table->loadVersion($collection, (int) $item->id);
			$table->check();

			if ($table->fulltitle === $item->fulltitle && $table->alias === $item->alias) {
				continue;
			}

			$db->setQuery(
				$db->getQuery(true)
					->update($db->quoteName('#__collector_items'))
					->set($db->quoteName('fulltitle') . ' = ' . $db->quote((string) $table->fulltitle))
					->set($db->quoteName('alias') . ' = ' . $db->quote((string) $table->alias))
					->where($db->quoteName('id') . ' = ' . (int) $item->id)
			)->execute();

			$changed++;
		}

		return $changed;
	}

	/**
	 * Colonnes des champs define de la liste, par collection.
	 *
	 * @return array<int, string[]>
	 */
	private function getColumnsByCollection(int $defined): array
	{
		$cols = [];

		foreach ($this->getDefineFields($defined) as $field) {
			$cols[(int) $field->collection][] = $field->tablecolumn;
		}

		return $cols;
	}

	private function loadValue(int $id): ?object
	{
		$db = $this->getDatabase();
		$db->setQuery(
			$db->getQuery(true)
				->select(['id', 'defined', 'parent_id', 'level', 'content', 'lft', 'rgt'])
				->from($db->quoteName('#__collector_defined_content'))
				->where($db->quoteName('id') . ' = ' . $id)
		);

		return $db->loadObject() ?: null;
	}

	/**
	 * @return object[]
	 */
	private function loadChildren(int $id): array
	{
		$db = $this->getDatabase();
		$db->setQuery(
			$db->getQuery(true)
				->select(['id', 'content'])
				->from($db->quoteName('#__collector_defined_content'))
				->where($db->quoteName('parent_id') . ' = ' . $db->quote((string) $id))
				->order($db->quoteName('lft') . ' ASC')
		);

		return $db->loadObjectList() ?: [];
	}

	/**
	 * Libellé complet d'une valeur : « Parent › Enfant ».
	 */
	private function labelPath(object $value): string
	{
		$labels = [$value->content];
		$node   = $value;
		$guard  = 0;

		while ((int) $node->parent_id > 1 && $guard++ < 20) {
			$node = $this->loadValue((int) $node->parent_id);

			if (!$node) {
				break;
			}

			array_unshift($labels, $node->content);
		}

		return implode(' › ', $labels);
	}

	/**
	 * Couples fusionnés en plus de la valeur choisie (enfants homonymes).
	 *
	 * @return array<int, array{from:object, to:object, usage:array}>
	 */
	private function labelPairs(int $defined, array $pairs): array
	{
		$result = [];
		$extra  = array_slice($pairs, 1);

		if (!$extra) {
			return $result;
		}

		$usage = $this->getUsage($defined, array_column($extra, 0));

		foreach ($extra as [$from, $to]) {
			$fromValue = $this->loadValue($from);
			$toValue   = $this->loadValue($to);

			if ($fromValue && $toValue) {
				$result[] = [
					'from'  => $fromValue->content,
					'to'    => $this->labelPath($toValue),
					'items' => $usage[$from]['items'] ?? 0,
				];
			}
		}

		return $result;
	}

	/**
	 * @return string[]
	 */
	private function labelValues(array $ids): array
	{
		$labels = [];

		foreach ($ids as $id) {
			if ($value = $this->loadValue((int) $id)) {
				$labels[] = $value->content;
			}
		}

		return $labels;
	}

	private function normalize(string $value): string
	{
		return mb_strtolower(trim(preg_replace('/\s+/u', ' ', $value)));
	}

	private function isId($value): bool
	{
		return (string) (int) $value === trim((string) $value) && (int) $value > 0;
	}

	private function tableExists(string $table): bool
	{
		$db = $this->getDatabase();

		if ($this->tables === null) {
			$this->tables = array_fill_keys(array_map('strtolower', $db->getTableList()), true);
		}

		return isset($this->tables[strtolower($db->replacePrefix($table))]);
	}
}
