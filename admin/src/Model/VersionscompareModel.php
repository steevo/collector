<?php
/**
 * @package    Collector
 * @copyright  Copyright (C) 2010 - 2020 Philippe Ousset. All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace Steevo\Component\Collector\Administrator\Model;

defined('_JEXEC') or die;

use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\Model\BaseDatabaseModel;
use Joomla\Registry\Registry;
use Steevo\Component\Collector\Administrator\Classes\CollectorField;
use Steevo\Component\Collector\Administrator\Table\CollectorItemsTable;

/**
 * Modèle de comparaison de deux versions d'un item.
 *
 * Délègue entièrement :
 *  - la résolution des valeurs aux setQuery() des CollectorField
 *  - le formatage des valeurs à displayComparison() des CollectorField
 *
 * Fournit également les valeurs brutes (v1_raw / v2_raw) pour le formulaire
 * de validation, afin de stocker les IDs réels (et non les libellés résolus
 * par JOIN pour les types define et item).
 *
 * Note : étend BaseModel et non ItemModel.
 * ItemModel implémente ItemModelInterface qui déclare getItem() comme méthode
 * abstraite : toute sous-classe concrète devrait l'implémenter. Comme ce modèle
 * n'expose pas d'item Joomla standard, BaseModel est le bon niveau d'héritage.
 */
class VersionscompareModel extends BaseDatabaseModel
{
	/**
	 * Métadonnées (date, auteur, état, modification) indexées par version id.
	 * Renseigné en sous-produit de getComparison().
	 *
	 * @var array<int, object>
	 */
	protected array $versionMeta = [];

	// ── Lecture ───────────────────────────────────────────────────────────────

	/**
	 * Construit le tableau de comparaison côte à côte.
	 *
	 * Chaque élément contient :
	 *   - label    : libellé du champ
	 *   - type     : type du champ (string)
	 *   - column   : nom de la colonne en base
	 *   - field    : instance CollectorField (la vue appelle ->displayComparison())
	 *   - v1       : valeur résolue de la version A (après JOINs de setQuery)
	 *   - v2       : valeur résolue de la version B
	 *   - v1_raw   : valeur brute de la version A (pour les hidden fields du formulaire)
	 *   - v2_raw   : valeur brute de la version B
	 *   - changed  : bool — les deux valeurs résolues diffèrent-elles ?
	 */
	public function getComparison(int $collection, int $v1, int $v2): array
	{
		$db = $this->getDbo();

		// ── 1. Définitions des champs avec le type en clair ───────────────────
		$query = $db->getQuery(true)
			->select(['f.id', 'f.field', 'f.tablecolumn', 'f.attribs', 'f.filter', 't.type AS type'])
			->from($db->quoteName('#__collector_fields', 'f'))
			->join('LEFT', $db->quoteName('#__collector_fields_type', 't') . ' ON t.id = f.type')
			->where($db->quoteName('f.collection') . ' = ' . $collection)
			->order($db->quoteName('f.ordering') . ' ASC');

		$db->setQuery($query);
		$fieldDefs = $db->loadObjectList();

		if (empty($fieldDefs)) {
			return [];
		}

		// ── 2. Instanciation des CollectorField ───────────────────────────────
		$fieldInstances = [];
		foreach ($fieldDefs as $def) {
			$registry = new Registry();
			$registry->loadString($def->attribs ?? '');
			$def->attribs = $registry->toArray();
			$fieldInstances[] = CollectorField::getInstance($collection, $def);
		}

		// ── 3. Requête principale avec JOINs (valeurs résolues pour l'affichage)
		$query = $db->getQuery(true)
			->select(['h.id', 'h.modified', 'h.modified_by',
			          'ua.name AS author_name', 'h.modification', 'h.state'])
			->from($db->quoteName('#__collector_items_history_' . $collection, 'h'))
			->join('LEFT', $db->quoteName('#__users', 'ua') . ' ON ua.id = h.modified_by')
			->whereIn($db->quoteName('h.id'), [$v1, $v2]);

		foreach ($fieldInstances as $fieldInstance) {
			$fieldInstance->setQuery($query);
		}

		$db->setQuery($query);
		$rows = $db->loadObjectList('id');

		if (!isset($rows[$v1]) || !isset($rows[$v2])) {
			$this->setError(Text::_('COM_COLLECTOR_ITEM_HISTORY_VERSION_NOT_FOUND'));
			return [];
		}

		$this->versionMeta[$v1] = $rows[$v1];
		$this->versionMeta[$v2] = $rows[$v2];

		// ── 4. Requête brute (sans JOINs) pour les valeurs du formulaire ──────
		//    Les types define et item stockent un ID en BDD ; setQuery le résout
		//    en libellé via JOIN. Pour réinsérer la bonne valeur lors de la
		//    validation, on a besoin de l'ID réel, pas du libellé.
		$rawQuery = $db->getQuery(true)
			->select('*')
			->from($db->quoteName('#__collector_items_history_' . $collection))
			->whereIn($db->quoteName('id'), [$v1, $v2]);

		$db->setQuery($rawQuery);
		$rawRows = $db->loadObjectList('id');

		// ── 5. Version en attente comparée à la version en cours : rapportée sur celle-ci.
		//    Les champs que son auteur n'a pas touchés, modifiés depuis sa saisie, ne sont pas
		//    proposés (ce serait un retour en arrière) ; un champ modifié des deux côtés est signalé.
		$rebase = ['rebased' => [], 'conflicts' => []];

		if ((int) $rows[$v1]->state === 1 && (int) $rows[$v2]->state === 0) {
			$table             = new CollectorItemsTable($db);
			$table->collection = $collection;
			$rebase            = $table->rebasePendingVersion($v2);
		}

		// ── 6. Construction du tableau de comparaison ─────────────────────────
		$comparison = [];

		foreach ($fieldInstances as $fieldInstance) {
			$col  = $fieldInstance->_field->tablecolumn;
			$type = $fieldInstance->getFieldType();
			$val1 = $rows[$v1]->$col ?? '';
			$val2 = $rows[$v2]->$col ?? '';
			$raw1 = isset($rawRows[$v1]) ? (string) ($rawRows[$v1]->$col ?? '') : '';
			$raw2 = isset($rawRows[$v2]) ? (string) ($rawRows[$v2]->$col ?? '') : '';

			if (in_array($col, $rebase['rebased'], true)) {
				$val2 = $val1;
				$raw2 = $raw1;
			}

			$comparison[] = [
				'label'    => $fieldInstance->getFieldName(),
				'type'     => $type,
				'column'   => $col,
				'field'    => $fieldInstance,
				'v1'       => $val1,
				'v2'       => $val2,
				'v1_raw'   => $raw1,
				'v2_raw'   => $raw2,
				'changed'  => $fieldInstance->normalizeForComparison($raw1) !== $fieldInstance->normalizeForComparison($raw2),
				'conflict' => in_array($col, $rebase['conflicts'], true),
			];
		}

		return $comparison;
	}

	/**
	 * Retourne les métadonnées d'une version.
	 * Disponible après un appel à getComparison().
	 */
	public function getVersionMeta(int $versionId): ?object
	{
		return $this->versionMeta[$versionId] ?? null;
	}

	// ── Validation ────────────────────────────────────────────────────────────

	/**
	 * Valide la version en attente (v2) avec les valeurs choisies champ par champ, ou la refuse.
	 *
	 * - au moins une modification acceptée (« Oui ») : les valeurs choisies (v2_raw pour les « Oui »,
	 *   v1_raw pour les « Non ») sont enregistrées dans v2, qui devient la version en cours
	 *   (CollectorItemsTable::validatePendingVersion : ordre des versions en attente, titre et alias
	 *   recalculés, proposition publiée) ;
	 * - aucune modification acceptée : v2 est refusée, avec la note saisie (CollectorItemsTable::refuseVersion).
	 *
	 * @param  int    $collection
	 * @param  int    $item
	 * @param  int    $v2            Id de la version à valider
	 * @param  array  $fieldValues   Tableau [column => chosen_raw_value]
	 * @param  int    $acceptedCount Nombre de modifications acceptées (Oui)
	 */
	public function validateVersion(
		int $collection,
		int $item,
		int $v2,
		array $fieldValues,
		int $acceptedCount = 0
	): bool {
		$db = $this->getDbo();

		// ── Whitelist des colonnes pour éviter l'injection via les clés POST ──
		$query = $db->getQuery(true)
			->select('tablecolumn')
			->from($db->quoteName('#__collector_fields'))
			->where($db->quoteName('collection') . ' = ' . $collection);

		$db->setQuery($query);
		$validColumns = $db->loadColumn();
		$safeValues   = array_intersect_key($fieldValues, array_flip($validColumns));

		$table             = new CollectorItemsTable($db);
		$table->collection = $collection;
		$table->load($item);

		$result = ($acceptedCount === 0)
			? $table->refuseVersion($v2, (string) ($fieldValues['refuse_note'] ?? ''))
			: $table->validatePendingVersion($v2, $safeValues);

		if (!$result) {
			$this->setError($table->getError());
		}

		return $result;
	}
}
