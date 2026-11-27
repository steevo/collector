<?php
/**
 * Joomla! 3.0 component Collector
 *
 * @package 	Collector
 * @copyright   Copyright (C) 2010 - 2020 Philippe Ousset. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 *
 * Collector is a Multi Purpose Listing Tool.
 * Originaly developped to list Collections
 * it can be used for several purpose.
 */

namespace Steevo\Component\Collector\Administrator\Model;

defined('_JEXEC') or die;

use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\Factory;
use Joomla\CMS\Language\Associations;
use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\Factory\MVCFactoryInterface;
use Joomla\CMS\MVC\Model\ListModel;
use Joomla\Database\ParameterType;

/**
 * Itemversions model
 * @package	Collector
 */
class ItemversionsModel extends ListModel
{
	/**
	 * Collection Id
	 * @var int
	 */
	var $_collection;

	/**
	 * Item Id
	 * @var int
	 */
	var $_item;

	/**
	 * Constructor.
	 *
	 * @param	array	An optional associative array of configuration settings.
	 * @see		JController
	 * @since	1.6
	 */
	public function __construct($config = array())
	{
		if (empty($config['filter_fields'])) {
			$config['filter_fields'] = array(
				'id', 'h.id',
				'item', 'h.alias',
				'state', 'h.state',
				'modified', 'h.modified',
				'modified_by', 'h.modified_by', 'author_id',
				'modification', 'h.modification',
			);
		}

		parent::__construct($config);
	}
	
	/**
	 * Method to auto-populate the model state.
	 *
	 * Note. Calling getState in this method will result in recursion.
	 *
	 * @return	void
	 * @since	1.6
	 */
	protected function populateState($ordering = null, $direction = null)
	{
		// Initialise variables.
		$app = Factory::getApplication();

		$collection = $this->getCollection();
		
		if ($collection != $app->getUserState('com_collector.itemversions.filter.collection'))
		{
			$app->setUserState($this->context.'.collection', $collection);
			$app->input->set('limitstart', 0);
		}

		$this->setState('filter.collection', $collection);
		
		$item = $this->getItem();
		
		if ($item != $app->getUserState('com_collector.itemversions.filter.item'))
		{
			$app->setUserState($this->context.'.item', $item);
			$app->input->set('limitstart', 0);
		}
		
		$this->setState('filter.item', $item);
		
		$search = $this->getUserStateFromRequest($this->context.'.filter.search', 'filter_search');
		$this->setState('filter.search', $search);

		// List state information.
		parent::populateState('h.modified', 'desc');
	}
	
	/**
	 * Build an SQL query to load the list data.
	 *
	 * @return	JDatabaseQuery
	 */
	protected function getListQuery()
	{
		// Get collection
		$collection = $this->getState('filter.collection');
		$item = $this->getState('filter.item');
		
		// Create a new query object.
		$db = $this->getDbo();
		$query = $db->getQuery(true);
		
		// Select the required fields from the table.
		$query->select(
			$this->getState(
				'list.select',
				'h.id, h.item, h.state, h.modified, h.modified_by' .
				', h.modification, h.refuse_note'
			)
		);
		$query->from('#__collector_items_history_'.$collection.' AS h');
		
		// Join over the users for the author.
		$query->select('ua.name AS author_name');
		$query->join('LEFT', '#__users AS ua ON ua.id = h.modified_by');
		
		// Filter by item
		$query->where('h.item = '.$item);
		
		// Filter by search in title.
		$search = $this->getState('filter.search');
		if (!empty($search)) {
			if (stripos($search, 'id:') === 0) {
				$query->where('h.id = '.(int) substr($search, 3));
			}
			else if (stripos($search, 'author:') === 0) {
				$search = $db->Quote('%'.$db->escape(substr($search, 7), true).'%');
				$query->where('(ua.name LIKE '.$search.' OR ua.username LIKE '.$search.')');
			}
			else {
				$search = $db->Quote('%'.$db->escape($search, true).'%');
				$query->where('(h.modification LIKE '.$search.')');
			}
		}
		
		// Add the list ordering clause.
		$orderCol	= $this->state->get('list.ordering');
		$orderDirn	= $this->state->get('list.direction');
		$query->order($db->escape($orderCol.' '.$orderDirn));
		
		return $query;
	}
	
	/**
	 * Method to load default collection Id in <var>_collection</var>
	 *
	 * @access	private
	 */
	public function getCollection()
	{
		$app = Factory::getApplication();
		if ( $this->_collection == '' )
		{
			$this->_collection = $app->input->getString('collection', null);
			
			if (!$this->_collection) {
				$this->_collection = (int) $this->getState('filter.collection', 0);
			}
			
			if (!$this->_collection) {
				$this->_collection = (int) $app->getUserState('com_collector.current_collection', 0);
			}
			
			if (!$this->_collection) {
				$query = 'SELECT id';
				$query .= ' FROM `#__collector`';
				$query .= ' WHERE home = 1';
				
				$row = $this->_getList( $query );
				
				if ( !$row )
				{
					/**
					 * tag à defaut d'une collection
					 */
					$query = 'UPDATE `#__collector` SET home = 1 LIMIT 1;';
					$this->_db->setQuery( $query );
					$this->_db->execute();
					
					$query = 'SELECT id';
					$query .= ' FROM `#__collector`';
					$query .= ' WHERE home = 1';
					
					$row = $this->_getList( $query );
				}
				if ( $row == null )
				{
					return false;
				}
				else
				{
					$this->_collection = $row[0]->id;
				}
			}
		}
		
		return $this->_collection;
	}

	/**
	 * Method to load default collection Id in <var>_collection</var>
	 *
	 * @access	private
	 */
	public function getItem()
	{
		$app = Factory::getApplication();
		if ( $this->_item == '' )
		{
			$this->_item = $app->input->getString('item', null);
		}
		
		return $this->_item;
	}
	
	/**
	 * Method to delete item version
	 *
	 * @access	public
	 * @return	string	Message to display
	 */
	function delete($item,$cid)
	{
		$row = $this->getTable('CollectorItems');
		$row->collection = $this->getCollection();
		
		$row->load($item);

		foreach ($cid as $id)
		{
			if (!($row->deleteVersion($id)))
			{
				Factory::getApplication()->enqueueMessage($row->getError(),'error');
				return false;
			}
		}
		
		return true;
	}
	
	/**
	 * Valide une version en attente (voir CollectorItemsTable::validatePendingVersion)
	 */
	public function validate(int $itemId, int $versionId): bool
	{
		$row             = $this->getTable('CollectorItems');
		$row->collection = $this->getCollection();
		$row->load($itemId);

		if (!$row->validatePendingVersion($versionId)) {
			$this->setError($row->getError());
			return false;
		}

		return true;
	}

	/**
	 * Refuse une version en attente (voir CollectorItemsTable::refuseVersion)
	 */
	public function refuse(int $itemId, int $versionId, string $note = ''): bool
	{
		$row             = $this->getTable('CollectorItems');
		$row->collection = $this->getCollection();
		$row->load($itemId);

		if (!$row->refuseVersion($versionId, $note)) {
			$this->setError($row->getError());
			return false;
		}

		return true;
	}

	/**
	 * Rétablit une ancienne version (voir CollectorItemsTable::restoreVersion)
	 */
	function restore($item, $cid)
	{
		$row             = $this->getTable('CollectorItems');
		$row->collection = $this->getCollection();
		$row->load($item);

		if (!$row->restoreVersion((int) $cid[0])) {
			$this->setError($row->getError());
			return false;
		}

		return true;
	}
	
	/**
	 * Retourne l'ID de la version active (state=1) d'un item.
	 */
	public function getActiveVersionId(): int
	{
		$itemId     = (int) $this->getState('filter.item');
		$collection = (int) $this->getState('filter.collection');
		
		$db = $this->getDbo();
		$db->setQuery(
			$db->getQuery(true)
			   ->select('id')
			   ->from($db->quoteName('#__collector_items_history_' . $collection))
			   ->where($db->quoteName('item')  . ' = ' . $itemId)
			   ->where($db->quoteName('state') . ' = 1')
			   ->setLimit(1)
		);
		return (int) $db->loadResult();
	}

	/**
	 * Retourne l'ID de la plus ancienne version en attente (state=0) d'un item.
	 */
	public function getOldestPendingVersionId(): int
	{
		$itemId     = (int) $this->getState('filter.item');
		$collection = (int) $this->getState('filter.collection');

		$db = $this->getDbo();
		$db->setQuery(
			$db->getQuery(true)
			   ->select('MIN(id)')
			   ->from($db->quoteName('#__collector_items_history_' . $collection))
			   ->where($db->quoteName('item')  . ' = ' . $itemId)
			   ->where($db->quoteName('state') . ' = 0')
		);
		return (int) $db->loadResult();
	}
}
