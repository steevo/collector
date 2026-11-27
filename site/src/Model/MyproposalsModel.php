<?php
/**
 * @package    Collector
 * @copyright  Copyright (C) 2010 - 2026 Philippe Ousset. All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace Steevo\Component\Collector\Site\Model;

defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\MVC\Model\ListModel;

/**
 * « Mes propositions » : versions enregistrées par l'utilisateur connecté, dans toutes les collections
 * (en attente, validées, refusées avec le motif), les plus récentes d'abord.
 */
class MyproposalsModel extends ListModel
{
	public function __construct($config = [])
	{
		if (empty($config['filter_fields'])) {
			$config['filter_fields'] = ['state'];
		}

		parent::__construct($config);
	}

	protected function populateState($ordering = null, $direction = null)
	{
		$app = Factory::getApplication();

		// Filtre d'état : « pending » (en attente), « validated », « refused », vide pour tout
		$this->setState('filter.state', $app->getUserStateFromRequest($this->context . '.filter.state', 'filter_state', '', 'cmd'));
		$this->setState('params', $app->getParams());

		parent::populateState('p.modified', 'DESC');

		// Ordre fixe : les plus récentes d'abord
		$this->setState('list.ordering', 'p.modified');
		$this->setState('list.direction', 'DESC');
	}

	protected function getStoreId($id = '')
	{
		$id .= ':' . $this->getState('filter.state');

		return parent::getStoreId($id);
	}

	/**
	 * Une requête par collection (une table de versions chacune), réunies
	 */
	protected function getListQuery()
	{
		$db     = $this->getDbo();
		$userId = (int) Factory::getApplication()->getIdentity()->id;
		$states = match ($this->getState('filter.state')) {
			'pending'   => '0',
			'validated' => '1, 2',
			'refused'   => '3',
			default     => '0, 1, 2, 3',
		};

		$collections = $db->setQuery(
			$db->getQuery(true)
				->select('id')
				->from($db->quoteName('#__collector'))
				->where($db->quoteName('state') . ' = 1')
		)->loadColumn() ?: [];

		$parts = [];

		foreach ($collections as $collection) {
			$history = $db->quoteName('#__collector_items_history_' . (int) $collection);
			$parts[] = 'SELECT ' . (int) $collection . ' AS collection, h.id AS version_id, h.item, h.state, h.modified,'
				. ' h.modification, h.refuse_note,'
				. ' (SELECT a.id FROM ' . $history . ' AS a WHERE a.item = h.item AND a.state = 1 LIMIT 1) AS active_id'
				. ' FROM ' . $history . ' AS h'
				. ' WHERE h.modified_by = ' . $userId . ' AND h.state IN (' . $states . ')';
		}

		$query = $db->getQuery(true);

		if (!$userId || !$parts) {
			// Rien à lister
			return $query->select('1')->from($db->quoteName('#__collector'))->where('0 = 1');
		}

		$query->select('p.*, i.fulltitle, i.state AS item_state, c.name AS collection_name')
			->from('(' . implode(' UNION ALL ', $parts) . ') AS p')
			->join('INNER', $db->quoteName('#__collector_items', 'i') . ' ON i.id = p.item')
			->join('INNER', $db->quoteName('#__collector', 'c') . ' ON c.id = p.collection')
			->order('p.modified DESC, p.version_id DESC');

		return $query;
	}
}
