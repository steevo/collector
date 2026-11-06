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
use Steevo\Component\Collector\Administrator\Table\CollectorItemsHistoryTable;

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
			$this->setError('COM_COLLECTOR_ITEM_HISTORY_VERSION_NOT_FOUND');
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

		// ── 5. Construction du tableau de comparaison ─────────────────────────
		$comparison = [];

		foreach ($fieldInstances as $fieldInstance) {
			$col  = $fieldInstance->_field->tablecolumn;
			$type = $fieldInstance->getFieldType();
			$val1 = $rows[$v1]->$col ?? '';
			$val2 = $rows[$v2]->$col ?? '';

			$comparison[] = [
				'label'   => $fieldInstance->getFieldName(),
				'type'    => $type,
				'column'  => $col,
				'field'   => $fieldInstance,
				'v1'      => $val1,
				'v2'      => $val2,
				'v1_raw'  => isset($rawRows[$v1]) ? (string) ($rawRows[$v1]->$col ?? '') : '',
				'v2_raw'  => isset($rawRows[$v2]) ? (string) ($rawRows[$v2]->$col ?? '') : '',
				'changed' => $fieldInstance->normalizeForComparison((string)($rawRows[$v1]->$col ?? ''))
					!== $fieldInstance->normalizeForComparison((string)($rawRows[$v2]->$col ?? '')),
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
	 * Fusionne les valeurs choisies dans la version à valider (v2) et l'active.
	 * Si aucune modification n'est acceptée ($acceptedCount === 0), archive
	 * simplement v2 sans la modifier ni l'activer.
	 *
	 * Deux chemins :
	 *
	 * A) acceptedCount > 0 (au moins un "Oui") :
	 *    - Charge v2 depuis l'historique
	 *    - Applique les valeurs choisies (v2_raw pour les "Oui", v1_raw pour les "Non")
	 *    - Appelle store() → archive toutes les versions de l'item, active v2
	 *
	 * B) acceptedCount === 0 (aucun "Oui") :
	 *    - Archive v2 directement (state=2) sans toucher ses données ni activer quoi que ce soit
	 *    - v1 reste active (state=1)
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

		// ── Cas B : aucune modification acceptée → archive v2 directement ────
		if ($acceptedCount === 0) {
			$query = $db->getQuery(true)
				->update($db->quoteName('#__collector_items_history_' . $collection))
				->set($db->quoteName('state') . ' = 2')
				->set($db->quoteName('refuse_note') . ' = ' . $db->quote($fieldValues['refuse_note'] ?? ''))
				->where($db->quoteName('id') . ' = ' . $v2);

			$db->setQuery($query);

			if (!$db->execute()) {
				$this->setError($db->getErrorMsg());
				return false;
			}

			// Si save_history=0, supprimer toutes les versions archivées ← ajouter
			$params = ComponentHelper::getParams('com_collector');
			if (!$params->get('save_history', 1)) {
				$db->setQuery(
					$db->getQuery(true)
					   ->delete($db->quoteName('#__collector_items_history_' . $collection))
					   ->where($db->quoteName('item')  . ' = ' . $item)
					   ->where($db->quoteName('state') . ' = 2')
				)->execute();
			}
			
			$this->decrementToValidate($db, $item);
			return true;
		}

		// ── Cas A : au moins une modification acceptée → fusion + activation ──

		// Sauvegarder la date originale de v2 avant le store
		$db->setQuery(
			$db->getQuery(true)
			   ->select('modified, modified_by')
			   ->from($db->quoteName('#__collector_items_history_' . $collection))
			   ->where($db->quoteName('id') . ' = ' . $v2)
		);
		$originalMeta = $db->loadObject();

		// Charger v2 depuis la table d'historique
		$historyTable = new CollectorItemsHistoryTable($db, $collection);
		$historyTable->initVersion($collection);
		$historyTable->load($v2);

		if (!$historyTable->id) {
			$this->setError(Text::_('COM_COLLECTOR_ITEM_HISTORY_VERSION_NOT_FOUND'));
			return false;
		}

		// Appliquer les valeurs choisies :
		//   - "Non" : safeValues[$col] = v1_raw → écrase la valeur de v2 par celle de v1
		//   - "Oui" : safeValues[$col] = v2_raw → identique à ce qui est déjà dans v2
		foreach ($safeValues as $col => $val) {
			$historyTable->$col = $val;
		}

		// CRITIQUE : state doit être 1 avant store() pour que
		// CollectorItemsHistoryTable::store() archive v1 (state=1→2)
		// et active v2 (state=0→1). Sans cette ligne, v2 reste state=0
		// après store() et v1 reste active : la validation ne s'applique pas.
		$historyTable->state = 1;
		
		// store() : UPDATE state=2 WHERE item=X (archive tout)
		//           puis SET state=1 sur ce record (active v2)
		if (!$historyTable->store()) {
			$this->setError($historyTable->getError());
			return false;
		}

		// Restaurer la date et l'auteur originaux de v2
		if ($originalMeta) {
			$db->setQuery(
				$db->getQuery(true)
				   ->update($db->quoteName('#__collector_items_history_' . $collection))
				   ->set($db->quoteName('modified')    . ' = ' . $db->quote($originalMeta->modified))
				   ->set($db->quoteName('modified_by') . ' = ' . (int) $originalMeta->modified_by)
				   ->where($db->quoteName('id')        . ' = ' . $v2)
			)->execute();
		}
		// Restaurer modified dans #__collector_items
		if ($originalMeta) {
			$db->setQuery(
				$db->getQuery(true)
				   ->update($db->quoteName('#__collector_items'))
				   ->set($db->quoteName('modified')    . ' = ' . $db->quote($originalMeta->modified))
				   ->set($db->quoteName('modified_by') . ' = ' . (int) $originalMeta->modified_by)
				   ->where($db->quoteName('id')        . ' = ' . $item)
			)->execute();
		}

		// Si save_history=0, supprimer toutes les versions archivées
		$params = ComponentHelper::getParams('com_collector');
		if (!$params->get('save_history', 1)) {
			$db->setQuery(
				$db->getQuery(true)
				   ->delete($db->quoteName('#__collector_items_history_' . $collection))
				   ->where($db->quoteName('item')  . ' = ' . $item)
				   ->where($db->quoteName('state') . ' = 2')
			)->execute();
		}

		$this->decrementToValidate($db, $item);
		return true;
	}

	// ── Utilitaires ───────────────────────────────────────────────────────────

	/**
	 * Décrémente i.to_validate de 1, avec plancher à 0.
	 * Appelé après chaque validation (acceptée ou archivée).
	 */
	private function decrementToValidate(\Joomla\Database\DatabaseInterface $db, int $item): void
	{
		$query = $db->getQuery(true)
			->update($db->quoteName('#__collector_items'))
			->set($db->quoteName('to_validate') . ' = GREATEST(0, ' . $db->quoteName('to_validate') . ' - 1)')
			->where($db->quoteName('id') . ' = ' . $item);
 
		$db->setQuery($query);
		$db->execute();
	}
}