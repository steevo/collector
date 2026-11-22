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
use Steevo\Component\Collector\Administrator\Classes\ImportReader;

/**
 * Tableau de bord de Collector : chiffres clés, collections, objets à valider, import en cours,
 * objets les plus présents dans les listes des membres, activité récente et santé.
 */
class CollectorModel extends BaseDatabaseModel
{
	/** Collections (avec leurs compteurs), chargées une fois */
	protected $collectionsStats;

	/**
	 * Collections : objets (total, publiés), objets à valider dont propositions (aucune version validée)
	 *
	 * @return  object[]
	 */
	public function getCollectionsStats(): array
	{
		if ($this->collectionsStats !== null) {
			return $this->collectionsStats;
		}

		$db          = $this->getDatabase();
		$collections = $db->setQuery('SELECT id, name, alias, state FROM #__collector ORDER BY ordering, name')->loadObjectList() ?: [];

		foreach ($collections as $c) {
			$id      = (int) $c->id;
			$history = $db->quoteName('#__collector_items_history_' . $id);
			$counts  = $db->setQuery(
				'SELECT COUNT(*) AS total, SUM(i.state = 1) AS published, SUM(i.to_validate > 0) AS to_validate,'
				. ' SUM(i.to_validate > 0 AND NOT EXISTS (SELECT 1 FROM ' . $history . ' h WHERE h.item = i.id AND h.state = 1)) AS proposals'
				. ' FROM #__collector_items i WHERE i.collection = ' . $id . ' AND i.state >= 0'
			)->loadObject();

			$c->total       = (int) ($counts->total ?? 0);
			$c->published   = (int) ($counts->published ?? 0);
			$c->to_validate = (int) ($counts->to_validate ?? 0);
			$c->proposals   = (int) ($counts->proposals ?? 0);
		}

		return $this->collectionsStats = $collections;
	}

	/**
	 * Objets à valider, toutes collections : les plus récents d'abord
	 *
	 * @return  object[]  item, collection, collection_name, fulltitle, proposal (bool), modified, author
	 */
	public function getPendingItems(int $limit = 8): array
	{
		$db    = $this->getDatabase();
		$parts = [];

		foreach ($this->getCollectionsStats() as $c) {
			if (!$c->to_validate) {
				continue;
			}

			$id      = (int) $c->id;
			$history = $db->quoteName('#__collector_items_history_' . $id);
			$parts[] = 'SELECT i.id AS item, ' . $id . ' AS collection, ' . $db->quote($c->name) . ' AS collection_name, i.fulltitle,'
				. ' NOT EXISTS (SELECT 1 FROM ' . $history . ' a WHERE a.item = i.id AND a.state = 1) AS proposal,'
				. ' p.modified, u.name AS author'
				. ' FROM #__collector_items i'
				. ' JOIN ' . $history . ' p ON p.id = (SELECT MAX(p2.id) FROM ' . $history . ' p2 WHERE p2.item = i.id AND p2.state = 0)'
				. ' LEFT JOIN #__users u ON u.id = p.modified_by'
				. ' WHERE i.collection = ' . $id . ' AND i.to_validate > 0 AND i.state >= 0';
		}

		if (!$parts) {
			return [];
		}

		return $db->setQuery('(' . implode(') UNION ALL (', $parts) . ') ORDER BY modified DESC', 0, $limit)->loadObjectList() ?: [];
	}

	/**
	 * Imports non terminés (repris là où ils se sont arrêtés)
	 *
	 * Un import réel terminé n'est pas listé (auparavant, sa session
	 * n'était pas supprimée en fin d'import).
	 *
	 * @return  object[]  collection, collection_name, file, current_row, total_rows, imported, merged, skipped,
	 *                    author, updated, step (1 à 5), dry_run, finished, done (objets traités), total (objets du fichier)
	 */
	public function getImportSessions(): array
	{
		$db = $this->getDatabase();

		$sessions = $db->setQuery(
			'SELECT s.collection, s.created_by, c.name AS collection_name, s.file, s.first_line, s.current_row, s.total_rows,'
			. ' s.imported, s.merged, s.skipped, s.mapping, u.name AS author, s.updated'
			. ' FROM #__collector_import_sessions s'
			. ' LEFT JOIN #__collector c ON c.id = s.collection'
			. ' LEFT JOIN #__users u ON u.id = s.created_by'
			. ' ORDER BY s.updated DESC'
		)->loadObjectList() ?: [];

		$result = [];

		foreach ($sessions as $import) {
			$payload = json_decode((string) $import->mapping, true);
			unset($import->mapping);

			$import->step     = max(1, min(5, (int) ($payload['step'] ?? 2)));
			$import->dry_run  = !empty($payload['dry_run']);
			$import->finished = $import->step >= 5 && (int) $import->current_row > (int) $import->total_rows;

			if ($import->finished && !$import->dry_run) {
				continue;
			}

			// Objets traités / objets du fichier (lignes vides exclues), comme sur la page d'import
			$firstLine = max(1, (int) $import->first_line);
			$nextRow   = $import->step >= 5 ? max((int) $import->current_row, $firstLine) : $firstLine;
			$rows      = ImportReader::rows(ImportReader::dir() . basename((string) $import->file));

			if ($rows) {
				$dataRows      = array_filter(array_keys($rows), fn ($n) => $n >= $firstLine);
				$import->total = count($dataRows);
				$import->done  = count(array_filter($dataRows, fn ($n) => $n < $nextRow));
			} else {
				$import->total = max(0, (int) $import->total_rows - $firstLine + 1);
				$import->done  = min($import->total, max(0, $nextRow - $firstLine));
			}

			$result[] = $import;
		}

		return $result;
	}

	/**
	 * Date du dernier import terminé (hors simulations), null s'il n'y en a jamais eu
	 */
	public function getLastImportDate(): ?string
	{
		$date = $this->getDatabase()->setQuery('SELECT MAX(created) FROM #__collector_import_history WHERE dry_run = 0')->loadResult();

		return $date && $date !== '0000-00-00 00:00:00' ? $date : null;
	}

	/**
	 * Listes des membres : nombre de membres ayant au moins un objet, et d'objets dans les listes
	 */
	public function getMembersStats(): object
	{
		return $this->getDatabase()->setQuery(
			'SELECT COUNT(DISTINCT ul.user) AS members, COUNT(ui.id) AS items'
			. ' FROM #__collector_usersitems ui JOIN #__collector_userlist ul ON ul.id = ui.userlist'
		)->loadObject() ?: (object) ['members' => 0, 'items' => 0];
	}

	/**
	 * Objets les plus présents dans un type de liste des membres (0 souhaits, 1 échanges, 2 collection),
	 * classés par nombre de membres
	 *
	 * @return  object[]  item, collection, fulltitle, members
	 */
	public function getTopUserlistItems(int $type, int $limit = 5): array
	{
		return $this->getDatabase()->setQuery(
			'SELECT i.id AS item, i.collection, i.fulltitle, COUNT(DISTINCT ul.user) AS members'
			. ' FROM #__collector_usersitems ui'
			. ' JOIN #__collector_userlist ul ON ul.id = ui.userlist'
			. ' JOIN #__collector_userslists uls ON uls.id = ul.userslist AND uls.type = ' . (int) $type
			. ' JOIN #__collector_items i ON i.id = ui.itemid AND i.state = 1'
			. ' GROUP BY i.id, i.collection, i.fulltitle'
			. ' ORDER BY members DESC, i.fulltitle ASC',
			0,
			$limit
		)->loadObjectList() ?: [];
	}

	/**
	 * Activité récente : versions enregistrées (validées, en attente, archivées) et ajouts aux listes
	 * des membres, les plus récentes d'abord
	 *
	 * @return  object[]  kind (version|userlist), state, list_type, item, collection, fulltitle, author, date
	 */
	public function getRecentActivity(int $limit = 8): array
	{
		$db    = $this->getDatabase();
		$parts = [];

		foreach ($this->getCollectionsStats() as $c) {
			$id      = (int) $c->id;
			$history = $db->quoteName('#__collector_items_history_' . $id);
			$parts[] = '(SELECT ' . $db->quote('version') . ' AS kind, h.state, NULL AS list_type, i.id AS item, ' . $id . ' AS collection,'
				. ' i.fulltitle, u.name AS author, h.modified AS date'
				. ' FROM ' . $history . ' h JOIN #__collector_items i ON i.id = h.item LEFT JOIN #__users u ON u.id = h.modified_by'
				. ' WHERE h.modified > ' . $db->quote('1000-01-01') . ' ORDER BY h.modified DESC LIMIT ' . (int) $limit . ')';
		}

		$parts[] = '(SELECT ' . $db->quote('userlist') . ' AS kind, NULL AS state, uls.type AS list_type, i.id AS item, i.collection,'
			. ' i.fulltitle, u.name AS author, ui.created AS date'
			. ' FROM #__collector_usersitems ui JOIN #__collector_userlist ul ON ul.id = ui.userlist'
			. ' JOIN #__collector_userslists uls ON uls.id = ul.userslist JOIN #__collector_items i ON i.id = ui.itemid'
			. ' LEFT JOIN #__users u ON u.id = ul.user'
			. ' WHERE ui.created > ' . $db->quote('1000-01-01') . ' ORDER BY ui.created DESC LIMIT ' . (int) $limit . ')';

		return $db->setQuery(implode(' UNION ALL ', $parts) . ' ORDER BY date DESC', 0, $limit)->loadObjectList() ?: [];
	}

	/**
	 * Santé : objets dont le compteur « à valider » ne correspond pas aux versions en attente
	 * (l'outil de recalcul de la page Outils les corrige)
	 */
	public function getToValidateMismatches(): int
	{
		$tools = new ToolsModel();
		$count = 0;

		foreach ($this->getCollectionsStats() as $c) {
			$count += $tools->countToValidateMismatch((int) $c->id);
		}

		return $count;
	}

	/**
	 * Version installée (manifeste)
	 */
	public function getInstalledVersion(): string
	{
		$file = JPATH_ADMINISTRATOR . '/components/com_collector/collector.xml';
		$xml  = is_readable($file) ? @simplexml_load_file($file) : false;

		return $xml ? (string) $xml->version : Text::_('COM_COLLECTOR_UNKNOWN');
	}

	/**
	 * Dernière version publiée sur steevo.fr. Délai de 3 s et mémorisation 12 h dans la session :
	 * le tableau de bord ne doit pas attendre (ni échouer) si le site ne répond pas.
	 */
	public function getLastVersion(): string
	{
		$session = Factory::getApplication()->getSession();
		$cached  = $session->get('com_collector.last_version');

		if (\is_array($cached) && ($cached['time'] ?? 0) > time() - 43200) {
			return (string) $cached['version'];
		}

		$version = Text::_('COM_COLLECTOR_UNKNOWN');
		$context = stream_context_create(['http' => ['timeout' => 3], 'https' => ['timeout' => 3]]);
		$content = @file_get_contents('https://www.steevo.fr/update/com_collector.xml', false, $context);
		$xml     = $content ? @simplexml_load_string($content) : false;

		if ($xml && $xml->update && $xml->update->count()) {
			$version = (string) $xml->update[$xml->update->count() - 1]->version;
		}

		$session->set('com_collector.last_version', ['version' => $version, 'time' => time()]);

		return $version;
	}
}
