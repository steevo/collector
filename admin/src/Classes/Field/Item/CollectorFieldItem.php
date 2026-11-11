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

namespace Steevo\Component\Collector\Administrator\Classes\Field\Item;

use Steevo\Component\Collector\Administrator\Classes\CollectorField;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Router\Route;
use Joomla\CMS\Factory;
use Joomla\Database\DatabaseInterface;

defined('_JEXEC') or die;

/**
 * Field Item class
 *
 * @package	Collector
 */
class CollectorFieldItem extends CollectorField
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
		$db = Factory::getContainer()->get(DatabaseInterface::class);
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
	 * @param	JRegistry object			$params
	 */
	function setFilterWhereClause(&$query,$value,$params)
	{
		if ( $value != '' )
		{
			$query->where('h.' . $this->_field->tablecolumn . ' = ' . (int) $value);
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
		$application	= Factory::getApplication();
		$html = array();
		$attr = '';
		
		$user	= Factory::getApplication()->getIdentity();
		$groups	= implode(',', $user->getAuthorisedViewLevels());
		
		if ($menu) {
			$prefix = 'jform[params][filter][';
			$suffix = ']';
		} else {
			$prefix = '';
			$suffix = '';
		}
		
		$db = Factory::getContainer()->get(DatabaseInterface::class);
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
			$application->enqueueMessage($e->getMessage(),'warning');
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
				$nowDate	= $db->quote(Factory::getDate()->toSql());

				$query->where('(i.publish_up IS NULL OR i.publish_up = '.$nullDate.' OR i.publish_up <= '.$nowDate.')')
					->where('(i.publish_down IS NULL OR i.publish_down = '.$nullDate.' OR i.publish_down >= '.$nowDate.')')
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
				$application->enqueueMessage($e->getMessage(),'warning');
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
		$attr .= ' class="' . ($menu ? 'form-select d-inline-block w-auto' : 'inputbox') . $required . '"';
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
			if ($menu) {
				$html[] = '</td><td>';
			}
			$html[] = HTMLHelper::_('select.genericlist', $options, $prefix.'filterfield_'.$this->_field->tablecolumn.$suffix, trim($attr), 'value', 'text', $value);
		}
		else
		{
			$html[] = '<input type=hidden name=\''.$prefix.'filter_field_'.$this->_field->tablecolumn.$suffix.'\' value=\''.$value.'\' />';
		}

		return $menu ? '<td>' . implode($html) . '</td>' : implode($html);
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
	function display($value,$listing=true,$params=array(),$itemId=0)
	{
		$db = Factory::getContainer()->get(DatabaseInterface::class);
		
		$query = 'SELECT id, fulltitle';
		$query .= ' FROM #__collector_items';
		$query .= ' WHERE fulltitle = "'.$value.'"';

		$db->setQuery( $query );

		$result = $db->loadObject();

		if ( $result != null )
		{
			$return = $result->fulltitle;
			
			if ( ( ($this->_field->attribs['show_fieldlink'] ?? 0) == 1 ) && ( $this->_field->filter == 1 ) )
			{
				$link= 'index.php?option=com_collector&view=collection&id='.$this->_field->collection.'&filterfield_'.$this->_field->tablecolumn.'='.$result->id;
				$return = '<a href="'.Route::_($link).'">'.$return.'</a>';
			}
			else if ( ($this->_field->attribs['show_fieldlink'] ?? 0) == 2 )
			{
				$link= 'index.php?option=com_collector&view=item&collection='.$this->_field->attribs['collection'].'&id='.$result->id;
				$return = '<a href="'.Route::_($link).'">'.$return.'</a>';
			}
		}
		else
		{
			$return = false;
		}
		
		return parent::display($return,$listing,$params,$itemId);
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
		$db = Factory::getContainer()->get(DatabaseInterface::class);
		
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

