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

use Joomla\CMS\Factory;
use Joomla\CMS\MVC\Model\ListModel;

/**
 * Userslists model
 * @package	Collector
 */
class UserslistsModel extends ListModel
{
	/**
	 * Collection Id
	 * @var int
	 */
	var $_collection = null;

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
				'id', 'cu.id',
				'collection', 'cu.collection',
				'name', 'cu.name',
				'type', 'cu.type',
				'ordering', 'cu.ordering',
				'state', 'cu.state',
				'access', 'cu.access', 'access_level',
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
		
		$collectionFromInput  = $app->input->getInt('collection', 0);
		$collectionFromState  = (int) $app->getUserState($this->context . '.collection', 0);
		$collectionFromShared = (int) $app->getUserState('com_collector.current_collection', 0);

		if ($collectionFromInput > 0) {
			$collectionFromInput = $collectionFromInput;
		} elseif ($collectionFromShared > 0) {
			$collectionFromInput = $collectionFromShared;  // ? shared prime sur contexte local
		} elseif ($collectionFromState > 0) {
			$collectionFromInput = $collectionFromState;
		}

		if ($collectionFromInput !== $collectionFromState) {
			$app->setUserState($this->context . '.collection', $collectionFromInput);
			$app->input->set('limitstart', 0);
		}
		$app->setUserState('com_collector.current_collection', $collectionFromInput);
		$this->setState('filter.collection', $collectionFromInput);
	
		// List state information.
		parent::populateState('cu.ordering', 'asc');
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
		
		// Create a new query object.
		$db = $this->getDbo();
		$query = $db->getQuery(true);
		$user = Factory::getApplication()->getIdentity();
		
		// Select the required fields from the table.
		$query->select(
			$this->getState(
				'list.select',
				'cu.id, cu.collection, cu.name, cu.type, cu.ordering, cu.state, cu.access'
			)
		);
		$query->from('#__collector_userslists AS cu');
		
		// Join over the asset groups.
		$query->select('ag.title AS access_level');
		$query->join('LEFT', '#__viewlevels AS ag ON ag.id = cu.access');
		
		// Filter by collection
		$query->where('cu.collection = ' . $collection);
		
		// Implement View Level Access
		if (!$user->authorise('core.admin'))
		{
			$groups = implode(',', $user->getAuthorisedViewLevels());
			$query->where('cu.access IN (' . $groups . ')');
		}
		
		// Add the list ordering clause.
		$orderCol	= $this->state->get('list.ordering', 'cu.ordering');
		$orderDirn	= $this->state->get('list.direction', 'asc');
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
		if ($this->_collection == '')
		{
			$this->_collection = (string) $app->input->getInt('collection', 0);
			
			if ((int) $this->_collection <= 0) {
				$fromShared = (int) $app->getUserState('com_collector.current_collection', 0);
				if ($fromShared > 0) {
					$this->_collection = (string) $fromShared;
				}
			}
		
			if ((int) $this->_collection <= 0) {
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
					$db = $this->getDbo();
					$db->setQuery( $query );
					$db->execute();
					
					$query = 'SELECT id';
					$query .= ' FROM `#__collector`';
					$query .= ' WHERE home = 1';
					
					$collection = (int) $db->loadResult();
				}
				if ( !$collection )
				{
					return false;
				}
				else
				{
					$this->_collection = $collection;
				}
			}
		}
		
		return $this->_collection;
	}
}
