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
 * Menu Controller
 */
class CollectorControllerMenu extends JControllerLegacy
{
	/**
	 * Method to load filter fields in menu edition
	 */
	function listFilter()
	{
		$app = JFactory::getApplication();
		$db = JFactory::getDBO(); 
		$collection = $app->input->getVar( 'collection', '', '', 'int');
		$menuId = $app->input->getVar( 'itemId', '', '', 'int');
		$menuParams = new JRegistry;
		
		if ( $menuId != 0 )
		{
			$query = 'SELECT params';
			$query .= ' FROM `#__menu`';
			$query .= ' WHERE id = \''.$menuId.'\'';
			
			$db->setQuery( $query );
			
			$params = $db->loadResult();
			$menuParams->loadString($params);
			if ( $menuParams != null )
			{
				$filter = $menuParams->get('filter');
			}
		}
		
		if ( $collection == 0 )
		{
			$code[] = JText::_('COM_COLLECTOR_SELECT_COLLECTION');
		}
		else
		{
			$query = 'SELECT f.*, t.type AS type';
			$query .= ' FROM `#__collector_fields` AS f';
			$query .= ' LEFT JOIN `#__collector_fields_type` AS t ON f.type = t.id';
			$query .= ' WHERE collection = \''.$collection.'\'';
			$query .= ' AND filterable = 1';
			
			$db->setQuery( $query );
			
			$fields = $db->loadObjectList();
			
			if ($fields == null)
			{
				$code[] = JText::_('COM_COLLECTOR_NO_FILTER_AVAILABLE');
			}
			else
			{
				foreach ($fields as $field)
				{
					$registry = new JRegistry;
					$registry->loadString($field->attribs);
					$field->attribs = $registry->toArray();
					$_field = CollectorField::getInstance( $collection, $field );
					
					$paramName = 'filterfield_'.$field->tablecolumn;
					if (isset($filter->$paramName)) {
						$default = $filter->$paramName;
					} else {
						$default = 0;
					}
					
					$code[] = $_field->displayFilter($menuParams,$default,true);
				}
			}
		}
		
		echo '<table><tr>'.implode('<tr/><tr>',$code).'</tr></table>';
		return;
	}
	
	/**
	 * Method to load filter fields in menu edition
	 */
	function listRequired()
	{
		$app = JFactory::getApplication();
		$db = JFactory::getDBO(); 
		$collection = $app->input->getVar( 'collection', '', '', 'int');
		$menuId = $app->input->getVar( 'itemId', '', '', 'int');
		$menuParams = new JRegistry;
		
		if ( $menuId != 0 )
		{
			$query = 'SELECT params';
			$query .= ' FROM `#__menu`';
			$query .= ' WHERE id = \''.$menuId.'\'';
			
			$db->setQuery( $query );
			
			$params = $db->loadResult();
			$menuParams->loadString($params);
			if ( $menuParams != null )
			{
				$filter = $menuParams->get('required');
			}
		}
		
		if ( $collection == 0 )
		{
			$code[] = JText::_('COM_COLLECTOR_SELECT_COLLECTION');
		}
		else
		{
			$query = 'SELECT f.*, t.type AS type';
			$query .= ' FROM `#__collector_fields` AS f';
			$query .= ' LEFT JOIN `#__collector_fields_type` AS t ON f.type = t.id';
			$query .= ' WHERE collection = \''.$collection.'\'';
			$query .= ' AND filterable = 1';
			
			$db->setQuery( $query );
			
			$fields = $db->loadObjectList();
			
			if ($fields == null)
			{
				$code[] = JText::_('COM_COLLECTOR_NO_FILTER_AVAILABLE');
			}
			else
			{
				foreach ($fields as $field)
				{
					$registry = new JRegistry;
					$registry->loadString($field->attribs);
					$field->attribs = $registry->toArray();
					$_field = CollectorField::getInstance( $collection, $field );
					
					$paramName = 'filterfield_'.$field->tablecolumn;
					if (isset($filter->$paramName)) {
						$default = $filter->$paramName;
					} else {
						$default = 0;
					}
					
					$code[] = $_field->displayRequired($menuParams,$default);
				}
			}
		}
		
		echo implode('<br/>',$code);
		return;
	}
	
	/**
	 * Method to load items list in menu edition
	 */
	function listItems()
	{
		$app = JFactory::getApplication();
		$db = JFactory::getDBO(); 
		// recuperation du titre personnalise pour la collection
		$collection = $app->input->getVar( 'collection', '', '', 'int');
		$custom = array();
		$fieldCustom = array();
		$items = array();
		
		if ( $collection == 0 )
		{
			$code = JText::_('COM_COLLECTOR_SELECT_COLLECTION');
		}
		else
		{
			$menuId = $app->input->getVar( 'itemId', '', '', 'int');
			$menuparams = null;
			$default = 0;

			if ( $menuId != 0 )
			{
				$query = 'SELECT link';
				$query .= ' FROM `#__menu`';
				$query .= ' WHERE id = \''.$menuId.'\'';
				
				$db->setQuery( $query );
				
				$params = $db->loadResult();
				$paramsArray = explode("&", $params);
				
				foreach ( $paramsArray AS $param )
				{
					$param = explode("=", $param);
					$menuparams[$param[0]] = $param[1];
				}
			}
			
			if ( ( $menuparams != null ) AND ( isset($menuparams['id']) ) )
			{
				$default = $menuparams['id'];
			}

			$query = 'SELECT custom';
			$query .= ' FROM `#__collector`';
			$query .= ' WHERE id = \'' . $collection . '\'';

			$db->setQuery( $query );

			$row = $db->loadObjectList();

			if ( $row[0]->custom == "0" )
			{
				$query = 'SELECT id';
				$query .= ' FROM `#__collector_fields`';
				$query .= ' WHERE collection = "' . $collection . '"';
				$query .= ' AND home = "1"';

				$db->setQuery( $query );

				$row = $db->loadObjectList();

				$custom[] = $row[0]->id;
			}
			else
			{
				$custom = explode( '/', $row[0]->custom );
			}

			$query = 'SELECT f.*, u.name AS author, u.usertype, t.type AS type';
			$query .= ' FROM `#__collector_fields` AS f';
			$query .= ' LEFT JOIN #__collector_fields_type AS t ON t.id = f.type';
			$query .= ' LEFT JOIN #__users AS u ON u.id = f.created_by';
			$query .= ' WHERE collection = \'' . $collection . '\'';

			$db->setQuery( $query );

			$results = $db->loadObjectList();

			$i = 0;

			foreach ( $custom as $field )
			{
				for ($j = 0; $j < count($results); $j ++)
				{
					if ( $results[$j]->id == $field )
					{
						$registry = new JRegistry;
						$registry->loadString($results[$j]->attribs);
						$results[$j]->attribs = $registry->toArray();
						$fieldCustom[] = CollectorField::getInstance( $collection, $results[$j] );
					}
				}
				$i++;
			}

			// recherche des objets de la collection
			$query	= $db->getQuery(true);
			$query->select('c.id');
			$query->from('#__collector_items AS c');
			$query->join('LEFT', '#__collector_items_history_'.$collection.' AS h ON h.item = c.id');
			
			foreach ( $fieldCustom as $field )
			{
				$field->setQuery($query);
			}
			
			$query->where('c.collection = ' . $collection);
			$query->where('h.state = 1');

			$query->order('c.ordering');

			$db->setQuery( $query );

			$results = $db->loadObjectList();

			foreach ( $results as $row )
			{
				$item = new stdClass;
				$item->value = $row->id;

				$item->text = '';
				
				foreach ( $fieldCustom as $field )
				{
					$nameField = $field->_field->tablecolumn;
					$item->text .= $row->$nameField;
					$item->text .= ' ';
				}

				$items[] = $item;
			}
			
			$init[0] = array('value' => '', 'text' => ' - ' . JText::_('COM_COLLECTOR_SELECT_AN_ITEM').' - ');
			$select = $init;
			foreach ( $items as $key => $value )
			{
				$select[$key+1]=$value;
			}
			
			$code = JHTML::_('select.genericlist', $select, 'jform[request][id]', ' class=”inputbox” size="1" ', 'value', 'text', $default, 'jform_request_id');
		}
		
		echo $code;
		return;
	}

}