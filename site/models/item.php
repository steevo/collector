<?php
/**
 * Joomla! 3.0 component Collector
 *
 * @package 	Collector
 * @copyright   Copyright (C) 2010 - 2015 Philippe Ousset. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 *
 * Collector is a Multi Purpose Listing Tool.
 * Originaly developped to list Collections
 * it can be used for several purpose.
 */

// Check to ensure this file is included in Joomla!
defined('_JEXEC') or die( 'Restricted access' );

require_once(JPATH_ROOT.'/administrator/components/com_collector/classes/field.php');

/**
 * Filelist model
 * @package	Collector
 */
class CollectorModelItem extends JModelItem
{
	/**
	 * Method to auto-populate the model state.
	 *
	 * Note. Calling getState in this method will result in recursion.
	 *
	 * @since	1.6
	 */
	protected function populateState()
	{
		$app = JFactory::getApplication();

		// Load state from the request.
		$pkc = $app->input->getInt('collection');
		$pki = $app->input->getInt('id');
		$this->setState('collection.id', $pkc);
		$this->setState('item.id', $pki);

		// Load the parameters. Merge Global and Menu Item params into new object
		$params = $app->getParams();
		$menuParams = new JRegistry;

		if ($menu = $app->getMenu()->getActive()) {
			$menuParams->loadString($menu->params);
		}

		$mergedParams = clone $menuParams;
		$mergedParams->merge($params);

		$this->setState('params', $mergedParams);

		// Create a new query object.
		$db		= $this->getDbo();
		$query	= $db->getQuery(true);
		
		$itemid = $app->input->getInt('collection', 0) . ':' . $app->input->getInt('Itemid', 0);
		
		$search_all_value = $app->getUserStateFromRequest('com_collector.collection.'.$itemid.'.filter_search_all', 'filter_search_all', '');
		$this->setState('filter.filter_search_all', $search_all_value);
		
		$orderCol = $app->getUserStateFromRequest('com_collector.collection.' . $itemid . '.filter_order', 'filter_order', 'i.ordering', 'string');
		$this->setState('list.ordering', $orderCol);

		$listOrder = $app->getUserStateFromRequest('com_collector.collection.' . $itemid . '.filter_order_Dir',	'filter_order_Dir', '', 'cmd');
		if (!in_array(strtoupper($listOrder), array('ASC', 'DESC', ''))) {
			$listOrder = 'ASC';
		}
		$this->setState('list.direction', $listOrder);
		
		$limit = $app->getUserStateFromRequest('com_collector.collection.' . $itemid . '.limit', 'limit', $params->get('display_num')?$params->get('display_num'):$app->getCfg('list_limit'));
		$this->setState('list.limit', $limit);
	}

	/**
	 * Method to load default collection Id in <var>_collection</var>
	 *
	 * @access	private
	 */
	public function getCollection()
	{
		$user		= JFactory::getUser();
		
		if ( empty($this->_collection) )
		{
			$collection = $this->getState('collection.id');
			
			if ($collection == '')
			{
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
					$collection = $row[0]->id;
				}
			}
			
			// Create a new query object.
			$db = JFactory::getDBO();
			$query = $db->getQuery(true);
			
			// Select the required fields from the table.
			$query->select('c.*, u.name AS author');
			$query->from('#__collector AS c');
			$query->join('LEFT', '#__users AS u ON u.id = c.created_by');
			// Filter by start and end dates.
			$nullDate = $db->Quote($db->getNullDate());
			$nowDate = $db->Quote(JFactory::getDate()->toSql());

			$query->where('((c.created_by = '.$user->id.') OR (c.state = 1 AND ( c.publish_up = '.$nullDate.' OR c.publish_up <= '.$nowDate.') AND ( c.publish_down = '.$nullDate.' OR c.publish_down >= '.$nowDate.' )))');
			
			// Add the filder on ID
			$query->where('c.id = '.$collection);
			
			$db->setQuery( $query );
			
			$this->_collection = $db->loadObject();
			
			if ($this->_collection->custom == 0)
			{
				$query = $db->getQuery(true);
				$query->select('id');
				$query->from('#__collector_fields');
				$query->where('collection = '.$collection);
				$query->where('home = 1');
				
				$db->setQuery( $query );
				
				$this->_collection->custom = $db->loadResult();
			}
			 
			$this->_collection->custom = explode('/',$this->_collection->custom);
		}
		
		return $this->_collection;
	}
	
	/**
	 * Method to load fields informations
	 *
	 * @access	private
	 * @return	mixed			Array of fields objects. False if no fields loaded.
	 */
	public function getFields()
	{
		if ( empty($this->_fields) )
		{
			if ( $this->_collection->id == '0' )
			{
				return false;
			}
			else
			{
				$collection = $this->_collection->id;
			}
			
			$db		= $this->getDbo();
			$query	= $db->getQuery(true);
			
			$user		= JFactory::getUser();
			$aid		= (int) $user->get('aid', 0);
			
			$jnow		= JFactory::getDate();
			$now		= $jnow->toSql();
			$nullDate	= $db->getNullDate();
			
			$query->select('f.*, u.name AS author');
			$query->from('#__collector_fields AS f');
			
			// Join over the type.
			$query->select('t.type AS type');
			$query->join('LEFT', '#__collector_fields_type AS t ON t.id = f.type');
			
			$query->join('LEFT', '#__users AS u ON u.id = f.created_by');
			$query->where('collection = ' . $collection);
			
			$query->where('( f.created_by = ' . (int) $user->id . ' OR ( f.state = 1 AND ( f.publish_up = '.$db->Quote($nullDate).' OR f.publish_up <= '.$db->Quote($now).' ) AND ( f.publish_down = '.$db->Quote($nullDate).' OR f.publish_down >= '.$db->Quote($now).' ) ) )');
			
			// Filter by access level.
			if ($access = $this->getState('filter.access')) {
				$user	= JFactory::getUser();
				$groups	= implode(',', $user->getAuthorisedViewLevels());
				$query->where('f.access IN ('.$groups.')');
			}
			$query->order('ordering');
			
			$db->setQuery($query);
			$fields = $db->loadObjectList();
			
			if ( ! $fields ) {
				return false;
			}
			
			foreach ($fields as $field)
			{
				$registry = new JRegistry;
				$registry->loadString($field->attribs);
				$field->attribs = $registry->toArray();
				$this->_fields[] = CollectorField::getInstance( $this->_collection->id, $field );
				
				// custom title
				if ( ($key = array_search($field->id, $this->_collection->custom))!==false )
				{
					$this->_collection->custom[$key] = $field->tablecolumn;
				}
			}
		}
		return $this->_fields;
	}
	
	/**
	 * Method to get an ojbect.
	 *
	 * @param	integer	The id of the object to get.
	 *
	 * @return	mixed	Object on success, false on failure.
	 */
	public function getItem($id = null)
	{
		// Get collection
		$collection	= $this->getCollection();
		$fields		= $this->getFields();

		$user	= JFactory::getUser();
		
		if (empty($this->_item))
		{
			if (empty($id)) {
				$id = (int) $this->getState('item.id');
			}
			
			try
			{
				// Create a new query object.
				$db			= $this->getDbo();
				$query		= $db->getQuery(true);
				
				// Select the required fields from the table.
				$query->select(
					$this->getState(
						'item.select',
						'i.id, i.asset_id, i.alias, i.fulltitle, i.collection, i.ordering, i.state, i.hits, '.
						'i.created, i.created_by, i.created_by_alias, ' .
						'i.access, i.publish_up, i.publish_down, i.checked_out, i.checked_out_time, ' .
						// use created if modified is 0
						'CASE WHEN i.modified = ' . $db->quote($db->getNullDate()) . ' THEN i.created ELSE i.modified END as modified, ' .
							'i.modified_by'
					)
				);
				$query->from('#__collector_items AS i');
				
				// Join over the values.
				$query->select('h.metakey, h.metadesc, h.metadata')
					->join('LEFT', '#__collector_items_history_'.$collection->id.' AS h ON h.item = i.id');
				foreach($fields as $field)
				{
					$field->setQuery($query);
				}
				
				// Join on category table.
				$query->select('c.access AS collection_access')
					->join('LEFT', '#__collector AS c on c.id = i.collection');
				
				// Join over the users for the author and modified_by names.
				$query->select("CASE WHEN i.created_by_alias > ' ' THEN i.created_by_alias ELSE ua.name END AS author");
				$query->join('LEFT', '#__users AS ua ON ua.id = i.created_by');
				$query->select("uam.name as modified_by_name");
				$query->join('LEFT', '#__users AS uam ON uam.id = i.modified_by');
				
				
				$query->where('h.state = 1');
				$query->where('i.collection = '.$collection->id);
				$query->where('i.id = '.$id);
				
				if ((!$user->authorise('core.edit.state', 'com_collector')) && (!$user->authorise('core.edit', 'com_collector'))) {
					// Filter by start and end dates.
					$nullDate = $db->quote($db->getNullDate());
					$date = JFactory::getDate();

					$nowDate = $db->quote($date->toSql());

					$query->where('(i.publish_up = ' . $nullDate . ' OR i.publish_up <= ' . $nowDate . ')')
						->where('(i.publish_down = ' . $nullDate . ' OR i.publish_down >= ' . $nowDate . ')');
				}
				
				$db->setQuery($query);

				$data = $db->loadObject();
				
				if (empty($data))
				{
					return JError::raiseError(404, JText::_('COM_COLLECTOR_ERROR_ITEM_NOT_FOUND'));
				}
				
				$data->params = clone $this->getState('params');
				
				$registry = new JRegistry;
				$registry->loadString($data->metadata);
				$data->metadata = $registry;
				
				// Technically guest could edit an article, but lets not check that to improve performance a little.
				$userId = $user->get('id');
				$assetCol = 'com_collector.collection.' . $data->collection;
				$assetItem = 'com_collector.item.' . $data->id;

				// Check general edit permission first.
				if ($user->authorise('core.edit', $assetItem))
				{
					$data->params->set('access-edit', true);
				}
				elseif ($user->authorise('core.edit', $assetItem) === null)
				{
					if ($user->authorise('core.edit', $assetCol))
					{
						$data->params->set('access-edit', true);
					}
					elseif ($user->authorise('core.edit', $assetCol) === null)
					{
						if ($user->authorise('core.edit', 'com_collector'))
						{
							$item->params->set('access-edit', true);
						}
					}
				}

				// Now check if edit.own is available.
				elseif (!empty($userId) && $user->authorise('core.edit.own', $assetItem))
				{
					// Check for a valid user and that they are the owner.
					if ($userId == $data->created_by)
					{
						$data->params->set('access-edit', true);
					}
				}
				elseif (!empty($userId) && ($user->authorise('core.edit.own', $assetItem) === null))
				{
					if ($user->authorise('core.edit.own', $assetCol))
					{
						$data->params->set('access-edit', true);
					}
					elseif ($user->authorise('core.edit.own', $assetCol) === null)
					{
						if ($user->authorise('core.edit.own', 'com_collector'))
						{
							$item->params->set('access-edit', true);
						}
					}
				}

				// Compute view access permissions.
				$user = JFactory::getUser();
				$groups = $user->getAuthorisedViewLevels();

				$data->params->set('access-view', in_array($data->access, $groups) && in_array($data->collection_access, $groups));
				
				// Get item title.
				$data->titleItem = '';
				foreach($collection->custom as $custom)
				{
					if ($data->titleItem != '')
					{
						$data->titleItem .= ' ';
					}
					$data->titleItem .= $data->$custom;
				}
				
				$this->_item = $data;
			}
			catch (Exception $e)
			{
				if ($e->getCode() == 404)
				{
					// Need to go thru the error handler to allow Redirect to work.
					JError::raiseError(404, $e->getMessage());
				}
				else
				{
					$this->setError($e);
					$this->_item = false;
				}
			}
		}
		
		return $this->_item;
	}
	
	/**
	 * Increment the hit counter for the article.
	 *
	 * @param	int		Optional primary key of the article to increment.
	 *
	 * @return	boolean	True if successful; false otherwise and internal error set.
	 */
	public function hit($pk = 0)
	{
		$input = JFactory::getApplication()->input;
		$hitcount = $input->getInt('hitcount', 1);

		if ($hitcount)
		{
			// Initialise variables.
			$pk = (!empty($pk)) ? $pk : (int) $this->getState('item.id');
			$db = $this->getDbo();

			$db->setQuery(
					'UPDATE #__collector_items' .
					' SET hits = hits + 1' .
					' WHERE id = '.(int) $pk
			);

			if (!$db->execute()) {
					$this->setError($db->getErrorMsg());
					return false;
			}
		}

		return true;
	}

	/**
	 * Method to get navigation
	 * Put values in <var>_prev</var> and <var>_next</var>
	 *
	 * @access	public
	 * @return	void
	 */
	function getPrevNext()
	{
		$db			= $this->getDbo();
		$nullDate	= $db->getNullDate();
		$jnow		= JFactory::getDate();
		$now		= $jnow->toSql();
		$query		= $db->getQuery(true);
		
		$user	= JFactory::getUser();
		$groups	= implode(',', $user->getAuthorisedViewLevels());
		
		$app		= JFactory::getApplication();
		$collection	= $this->getCollection();
		$fields		= $this->getFields();
		$params		= $app->getParams();
		
		$this->_prev_id = 0;
		$this->_next_id = 0;

		$query->select('i.id, i.alias, i.fulltitle');
		$query->from('#__collector_items AS i');
		
		// Join over the values.
		$query->join('LEFT', '#__collector_items_history_'.$collection->id.' AS h ON h.item = i.id');
		foreach($fields as $field)
		{
			$field->setQuery($query);
		}
		
		// Join over the users for the author and modified_by names.
		$query->select("CASE WHEN i.created_by_alias > ' ' THEN i.created_by_alias ELSE ua.name END AS author");
		$query->join('LEFT', '#__users AS ua ON ua.id = i.created_by');
		$query->join('LEFT', '#__users AS uam ON uam.id = i.modified_by');
		
		// Filter by item state.
		if ($this->getState('filter.published') == 1)
		{
			$query->where('i.state = 1');
		}
		
		// Filter by history state.
		$query->where('h.state = 1');
		
		// Filter by collection.
		$query->where('i.collection = '.$collection->id);
		
		// Filter by access level.
		$query->where('i.access IN ('.$groups.')');
		
		// Filter by start and end dates.
		if ((!$user->authorise('core.edit.state', 'com_collector.collection.'.(int) $collection->id)) &&  (!$user->authorise('core.edit', 'com_collector.collection.'.(int) $collection->id))){
			$nullDate	= $db->quote($db->getNullDate());
			$nowDate	= $db->quote(JFactory::getDate()->toSql());

			$query->where('(i.publish_up = '.$nullDate.' OR i.publish_up <= '.$nowDate.')')
				->where('(i.publish_down = '.$nullDate.' OR i.publish_down >= '.$nowDate.')');
		}
		
		$whereSearch = array();
		
		$itemid = $app->input->getInt('collection', 0) . ':' . $app->input->getInt('Itemid', 0);
		
		$filter = $params->get('filter');
		
		foreach($fields as $field)
		{
			if ( $field->_field->filter == 1 )
			{
				$nameFilterCollection = 'filterfield_'.$field->_field->tablecolumn;
				if (isset($filter[$nameFilterCollection])) {
					$valueFilterMenu = $filter[$nameFilterCollection];
				} else {
					$valueFilterMenu = '';
				}

				$filtervalue = $app->getUserStateFromRequest('com_collector.collection.' . $itemid . '.'.$nameFilterCollection, $nameFilterCollection, $valueFilterMenu);
				
				$field->setFilterWhereClause($query,$filtervalue);
			}
			
			$search_all_value = $this->getState('filter.filter_search_all');
			if ( $search_all_value != '' )
			{
				$whereSearch[] = $field->getSearchWhereClause($query,$search_all_value);
			}
		}
		
		if (count($whereSearch) > 0 ) {
			$query->where('('.implode(' OR ', $whereSearch).')');
		}
		
		$orderby = $this->getState('list.ordering').' '.$this->getState('list.direction');
		$query->order($orderby);
		
		$db->setQuery($query);
		
		$items = $db->loadRowList();

		$total = count($items);

		$id = $this->getState('item.id');
		
		for($i=0;$i<count($items);$i++)
		{
			if ($items[$i][0] == $id)
			{
				$key = $i;
				break;
			}
		}
		
		if ( $key != 0 )
		{
			$this->_prev_id = $items[$key - 1][0].':'.$items[$key - 1][1];
			$this->_prev_title = $items[$key - 1][2];
		}
		if ( $key != $total - 1 )
		{
			$this->_next_id = $items[$key + 1][0].':'.$items[$key + 1][1];
			$this->_next_title = $items[$key + 1][2];
		}
		
		$limit = $this->getState('list.limit');
		
		$this->_limitstart = $limit ? ((int)($key/$limit))*$limit : 0;
	}
	
	/**
	 * Method to get html navigation
	 * Put data in <var>_navigation</var>
	 *
	 * @access	public
	 * @return	string Navigation html
	 */
	function getNavigation()
	{
		$app = JFactory::getApplication();
		
		// Load the content if it doesn't already exist
		if (empty($this->_navigation))
		{
			$this->_navigation = '';
			
			$this->getPrevNext();
			
			$prev = $this->_prev_id;
			$next = $this->_next_id;
			$img_base = './components/com_collector/assets/images/';
			$link_base = 'index.php';
			$link_back = $link_base;

			$option = $app->input->getVar('option', 0, 'get');
			$collection = $app->input->getVar('collection', 0, 'get');
			$item = $app->input->getVar('id', 0, 'get');
			$Itemid = $app->input->getVar('Itemid', 0, 'get');

			$link_base .= '?option='.$option.'&view=item&collection='.$collection.'&Itemid='.$Itemid;
			$link_back .= '?option='.$option.'&view=collection&id='.$collection.'&limitstart='.$this->_limitstart.'&Itemid='.$Itemid;

			if ($prev == 0)
			{
				$this->_navigation .= '<img src="'.$img_base.'empty.png" />';
			}
			else
			{
				$this->_navigation .= '<a title="'.$this->_prev_title.'" href="'.JRoute::_($link_base.'&id='. $prev) .'" ><img src="'.$img_base.'leftarrow.png" /></a>';
			}

			$this->_navigation .= '<a title="'.JText::_("COM_COLLECTOR_RETURN_TO_LIST").'" href="'.JRoute::_($link_back).'" ><img src="'.$img_base.'uparrow.png" /></a>';

			if ($next == 0)
			{
				$this->_navigation .= '<img src="'.$img_base.'empty.png" />';
			}
			else
			{
				$this->_navigation .= '<a title="'.$this->_next_title.'" href="'.JRoute::_($link_base.'&id='. $next) .'" ><img src="'.$img_base.'rightarrow.png" /></a>';
			}
		}
		return $this->_navigation;
	}
}