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

/**
 * Field Item class
 *
 * @package	Collector
 */
class CollectorField_Item extends CollectorField
{
	/**
	 * type
	 * 
	 * @var string
	 */
	public $type = 'item';
	
	/**
	 * Object constructor to set field
	 *
	 * Can be overloaded/supplemented by the child class
	 *
	 * @access	protected
	 * @param	int								$collection	Collection Id
	 * @param	object TableCollector_fields	$field		TableCollector_fields object
	 * @param	int								$item		Item Id
	 */
	function __construct( $collection, $field, $item = 0 )
	{
		// Initialisation
		$this->_collection = $collection;
		$this->_item = $item;
		$this->_field = $field;
	}
	
	/**
	 * Gets the field attributes for the form definition
	 *
	 * @return string
	 */
	function getFieldAttributes($attributes = array())
	{
		$attributes = array(
			'collection'	=> $this->_field->attribs['collection'],
			'default'		=> ''
		);
		
		return parent::getFieldAttributes($attributes);
	}
	
	/**
	 * Method to add field to query
	 *
	 * Can be overloaded/supplemented by the child class
	 *
	 * @param	JDatabaseQuery object		$query
	 */
	function setQuery(&$query)
	{
		$query->select('o'.$this->_field->id.'.fulltitle AS `'.$this->_field->tablecolumn.'`');
		$query->join('LEFT', '#__collector_items AS o'.$this->_field->id.' ON o'.$this->_field->id.'.id = h.'.$this->_field->tablecolumn);
		return;
	}
	
	/**
	 * Method to add where clause to query on search value
	 *
	 * Can be overloaded/supplemented by the child class
	 *
	 * @param	JDatabaseQuery object		$query
	 * @param	string						$search_all_value
	 */
	function getSearchWhereClause(&$query,$search_all_value)
	{
		$db = JFactory::getDbo();
		$text = $db->quote('%' . $db->escape($search_all_value, true) . '%', false);
		$where = 'LOWER(o'.$this->_field->id.'.fulltitle LIKE LOWER(' . $text . ')';
		return $where;
	}
	
	/**
	 * Method to add where clause to query on filter value
	 *
	 * Can be overloaded/supplemented by the child class
	 *
	 * @param	JDatabaseQuery object		$query
	 * @param	mixed						$value
	 */
	function setFilterWhereClause(&$query,$value)
	{
		if ( $value != '' )
		{
			$query->where('h.'.$this->_field->tablecolumn.' = "'.$value.'"');
			return true;
		}
		return false;
	}
	
	/**
	 * Method to add order by clause
	 *
	 * Can be overloaded/supplemented by the child class
	 */
	function getOrderBy()
	{
		$orderBy = 'o'.$this->_field->id.'.fulltitle';
		return $orderBy;
	}
	
	/**
	 * Method to display filter in search area
	 *
	 * Can be overloaded/supplemented by the child class
	 *
	 * @param	JRegistry object		$params
	 * @param	mixed					$value
	 * @param	boolean					$menu
	 */
	function displayFilter($params,$value,$menu=false)
	{
		// Initiliase variables.
		$app	= JFactory::getApplication();
		$html = array();
		$attr = '';
		
		$user	= JFactory::getUser();
		$groups	= implode(',', $user->getAuthorisedViewLevels());
		
		if ($menu) {
			$prefix = 'jform[params][filter][';
			$suffix = ']';
		} else {
			$prefix = '';
			$suffix = '';
		}
		
		$db = JFactory::getDbo();
		$query = $db->getQuery(true)
			->select('c.id AS value, c.fulltitle AS text')
			->from('#__collector_items AS c');

		$collection = $this->_field->attribs['collection'];
		
		$query->where('c.collection = ' . $db->quote($collection));
		
		$query->order('text ASC');

		// Get the options.
		$db->setQuery($query);
		
		try
		{
			$options = $db->loadObjectList();
		}
		catch (RuntimeException $e)
		{
			JError::raiseWarning(500, $e->getMessage());
		}
		
		// Filter unselected items
		if ( ($this->_field->attribs['hide_unselected']) && ($menu == false) ) {
			$query = $db->getQuery(true)
				->select('h.' . $this->_field->tablecolumn . ' AS value')
				->from('#__collector_items_history_' . $this->_field->collection . ' AS h')
				->join('LEFT', $db->quoteName('#__collector_items') . ' AS i ON h.item = i.id');

			$query->where('i.access IN ('.$groups.')');
			// Filter by start and end dates.
			if ((!$user->authorise('core.edit.state', 'com_collector.collection.'.(int) $this->_field->collection)) &&  (!$user->authorise('core.edit', 'com_collector.collection.'.(int) $this->_field->collection))){
				$nullDate	= $db->quote($db->getNullDate());
				$nowDate	= $db->quote(JFactory::getDate()->toSql());

				$query->where('(i.publish_up = '.$nullDate.' OR i.publish_up <= '.$nowDate.')')
					->where('(i.publish_down = '.$nullDate.' OR i.publish_down >= '.$nowDate.')')
					->where('i.state = 1');
			}
			$query->group('value');

			// Get the options.
			$db->setQuery($query);
			
			try
			{
				$optionsSelected = $db->loadObjectList('value');
			}
			catch (RuntimeException $e)
			{
				JError::raiseWarning(500, $e->getMessage());
			}
			
			$tempDelete = array();
			
			$old_level = 0;
			$tempDelete[$old_level] = array();
			
			for ($i = 0, $n = count($options); $i < $n; $i++)
			{
				if (!array_key_exists($options[$i]->value, $optionsSelected))
				{
					unset($options[$i]);
				}
			}
		}
		
		array_unshift($options,array('value' => '', 'text' => ''));
		
		// Initialize some field attributes.
		if ( (!$params->get('show_entire_listing')) && ($menu == false) ) {
			$requiredparams = $params->get('required');
			$required = $requiredparams['filterfield_'.$this->_field->tablecolumn]?' required':'';
		} else {
			$required = '';
		}
		$attr .= ' class="inputbox'.$required.'"';
		$attr .= ' size="1"';

		if ( $this->valueFilterMenu == null ) {
			$this->isFiltered($params);
		}
		
		if ( ($menu == true) || (!$this->valueFilterMenu) || ( $this->valueFilterMenu && (!$params->get('hide_filter'))) )
		{
			if ( (!$params->get('show_entire_listing')) && ($menu == false) ) {
				$requiredparams = $params->get('required');
				$required = $requiredparams['filterfield_'.$this->_field->tablecolumn]?'<span class="star">&nbsp;*</span>&nbsp;&nbsp;':'&nbsp;&nbsp;';
			} else {
				$required = '&nbsp;&nbsp;';
			}
			// Create a regular list.
			$html[] = $this->_field->field . $required;
			$html[] = JHtml::_('select.genericlist', $options, $prefix.'filterfield_'.$this->_field->tablecolumn.$suffix, trim($attr), 'value', 'text', $value);
		}
		else
		{
			$html[] = '<input type=hidden name=\''.$prefix.'filter_field_'.$this->_field->tablecolumn.$suffix.'\' value=\''.$value.'\' />';
		}

		return implode($html);
	}
	
	/**
	 * Method to display field
	 *
	 * Can be overloaded/supplemented by the child class
	 *
	 * @param	string					$value		Field value
	 * @param	boolean					$listing	
	 * @param	JRegistry object		$params
	 */
	function display($value,$listing=true,$params=array())
	{
		$db = JFactory::getDBO();
		
		$query = 'SELECT id, fulltitle';
		$query .= ' FROM #__collector_items';
		$query .= ' WHERE fulltitle = "'.$value.'"';

		$db->setQuery( $query );

		$result = $db->loadObject();

		if ( $result != null )
		{
			$return = $result->fulltitle;
			
			if ( ( $this->_field->attribs['show_fieldlink'] == 1 ) && ( $this->_field->filter == 1 ) )
			{
				$link= 'index.php?option=com_collector&view=collection&id='.$this->_field->collection.'&filterfield_'.$this->_field->tablecolumn.'='.$result->id;
				$return = '<a href="'.JRoute::_($link).'">'.$return.'</a>';
			}
			else if ( $this->_field->attribs['show_fieldlink'] == 2 )
			{
				$link= 'index.php?option=com_collector&view=item&collection='.$this->_field->attribs['collection'].'&id='.$result->id;
				$return = '<a href="'.JRoute::_($link).'">'.$return.'</a>';
			}
		}
		else
		{
			$return = false;
		}
		
		return $return;
	}
	
	/**
	 * Method to display value in fulltitle
	 *
	 * Can be overloaded/supplemented by the child class
	 *
	 * @param	JRegistry object		$params
	 */
	function displayInTitle($value)
	{
		$db = JFactory::getDBO();
		
		$query = 'SELECT fulltitle';
		$query .= ' FROM #__collector_items';
		$query .= ' WHERE id = "'.$value.'"';

		$db->setQuery( $query );

		$result = $db->loadObject();

		if ( $result != null )
		{
			$return = $result->fulltitle;
		} else {
			$return = '';
		}
		return $return;
	}
}

require_once(JPATH_ROOT.'/libraries/joomla/form/fields/list.php');

/**
 * Form Field class for the Collector.
 *
 * @package		Collector
 */
class JFormFieldCollectorItem extends JFormFieldList
{
	protected $type = 'CollectorItem';
	
	protected function getOptions()
	{
		// Get item title
		$collection = $this->element['collection'];
		
		// Create a new query object.
		$db = JFactory::getDBO();
		$query = $db->getQuery(true);
		
		// Select the custom title for items.
		$query->select('custom');
		$query->from('#__collector');
		$query->where('id = '.$collection);
		$db->setQuery( $query );
		
		$custom = $db->loadResult();
		
		// Create a new query object.
		$query = $db->getQuery(true);
		
		// Select the required fields from the table.
		$query->select('f.id, f.field, f.tablecolumn, f.attribs');
		$query->from('#__collector_fields as f');
		
		// Join over the type.
		$query->select('t.type AS type');
		$query->join('LEFT', '#__collector_fields_type AS t ON t.id = f.type');
		
		// Filter by collection
		$query->where('f.collection = '.$collection);
		
		// Filter by title
		if ( $custom != '0' )
		{
			$custom = explode('/',$custom);
			$query->where('f.id IN("'.implode('","',$custom).'")');
		}
		else
		{
			$query->where('home = 1');
		}
		
		$db->setQuery( $query );
		
		$fields = $db->loadObjectList();
		
		// Reorder
		$fieldsReordered = $fields;
		if ( $custom != '0' )
		{
			$custom = array_flip($custom);
			foreach( $fields as $field )
			{
				$fieldsReordered[$custom[$field->id]] = $field;
			}
		}
		
		foreach ($fieldsReordered as $field)
		{
			$fieldObjects[] = CollectorField::getInstance( $collection, $field );
		}
		
		// Create a new query object.
		$query = $db->getQuery(true);
		
		// Select the required fields from the table.
		$query->select( 'i.id as value, i.fulltitle as text' );
		$query->from('#__collector_items AS i');
		
		// Join over the values.
		$query->join('LEFT', '#__collector_items_history_'.$collection.' AS h ON h.item = i.id');
		foreach($fieldObjects as $field)
		{
			$field->setQuery($query);
		}
		
		// Filter by collection
		$query->where('i.collection = '.$collection);
		
		// Filter by history
		$query->where('h.state = 1');
		
		$db->setQuery( $query );
		
		$results = $db->loadObjectList();
		//Check for database error
		if (!$db->execute())
		{
			return JError::raiseWarning( 500, $db->getErrorMsg() );
		}
		
		array_unshift($results,array('value' => 0, 'text' => ''));
		
		return $results;
	}
}