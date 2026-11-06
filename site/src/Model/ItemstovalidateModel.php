<?php
/**
 * @package    Collector
 * @copyright  Copyright (C) 2010 - 2020 Philippe Ousset. All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace Steevo\Component\Collector\Site\Model;

defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\MVC\Model\ListModel;
use Joomla\Registry\Registry;
use Steevo\Component\Collector\Administrator\Classes\CollectorField;

/**
 * Modèle listant les items d'une collection qui ont des versions à valider.
 * Filtre : i.to_validate > 0.
 */
class ItemstovalidateModel extends ListModel
{
	/** @var object|null Collection courante */
	private ?object $_collection = null;

	/** @var array Instances CollectorField (champs de titre) */
	private array $_fields = [];

	public function __construct($config = [])
	{
		if (empty($config['filter_fields'])) {
			$config['filter_fields'] = ['i.ordering', 'i.alias', 'i.created', 'i.to_validate'];
		}
		parent::__construct($config);
	}

	protected function populateState($ordering = null, $direction = null)
	{
		$app = Factory::getApplication();
		$this->setState('collection.id', $app->input->getInt('id'));
		parent::populateState('i.ordering', 'asc');
	}

	protected function getListQuery()
	{
		$db         = $this->getDbo();
		$collection = $this->getCollection();
		$fields     = $this->getTitleFields();

		$query = $db->getQuery(true)
			->select('i.id, i.alias, i.fulltitle, i.collection, i.to_validate, i.ordering, i.state')
			->from($db->quoteName('#__collector_items', 'i'))
			->join('LEFT', $db->quoteName('#__collector_items_history_' . $collection->id, 'h') . ' ON h.item = i.id AND h.state = 1')
			->where($db->quoteName('i.collection') . ' = ' . (int) $collection->id)
			->where($db->quoteName('i.to_validate') . ' > 0')
			->where($db->quoteName('i.state') . ' >= 0');

		foreach ($fields as $field) {
			$field->setQuery($query);
		}

		$query->order('i.ordering ASC');

		return $query;
	}

	/**
	 * Retourne la collection courante (meta : id, name, custom, alias).
	 */
	public function getCollection(): ?object
	{
		if ($this->_collection !== null) {
			return $this->_collection;
		}

		$db           = $this->getDbo();
		$collectionId = (int) $this->getState('collection.id');

		$query = $db->getQuery(true)
			->select('c.id, c.name, c.alias, c.custom')
			->from($db->quoteName('#__collector', 'c'))
			->where($db->quoteName('c.id') . ' = ' . $collectionId)
			->where($db->quoteName('c.state') . ' = 1');

		$db->setQuery($query);
		$this->_collection = $db->loadObject();

		return $this->_collection;
	}

	/**
	 * Champs utilisés pour construire le titre de l'item dans la liste.
	 * Respecte le paramètre «custom» de la collection.
	 */
	public function getTitleFields(): array
	{
		if (!empty($this->_fields)) {
			return $this->_fields;
		}

		$collection = $this->getCollection();
		if (!$collection) {
			return [];
		}

		$db    = $this->getDbo();
		$query = $db->getQuery(true)
			->select(['f.id', 'f.field', 'f.tablecolumn', 'f.attribs', 't.type AS type'])
			->from($db->quoteName('#__collector_fields', 'f'))
			->join('LEFT', $db->quoteName('#__collector_fields_type', 't') . ' ON t.id = f.type')
			->where($db->quoteName('f.collection') . ' = ' . (int) $collection->id)
			->order($db->quoteName('f.ordering') . ' ASC');

		// Filtre sur les champs de titre (custom) si défini
		if (!empty($collection->custom) && $collection->custom !== '0') {
			$ids = array_map('intval', explode('/', $collection->custom));
			$query->whereIn($db->quoteName('f.id'), $ids);
		} else {
			$query->where($db->quoteName('f.home') . ' = 1');
		}

		$db->setQuery($query);
		$fieldDefs = $db->loadObjectList();

		foreach ($fieldDefs as $def) {
			$registry = new Registry();
			$registry->loadString($def->attribs ?? '');
			$def->attribs        = $registry->toArray();
			$this->_fields[] = CollectorField::getInstance($collection->id, $def);
		}

		return $this->_fields;
	}
}