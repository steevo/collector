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

// Check to ensure this file is included in Joomla!
defined('_JEXEC') or die( 'Restricted access' );

jimport('joomla.form.formfield');
jimport('joomla.database.database');
jimport('joomla.database.table');
require_once JPATH_LIBRARIES . '/joomla/form/fields/radio.php';

/**
 * Field abstract class
 *
 * @package	Collector
 * @abstract
 */
class CollectorField
{
	/**
	 * type
	 * 
	 * @var string
	 */
	public $type = null;
	
	/**
	 * Collection Id
	 * 
	 * @var int
	 */
	var $_collection;
	
	/**
	 * TableCollector_fields object
	 * 
	 * @var object TableCollector_fields
	 */
	var $_field;
	
	/**
	 * Item Id
	 * 
	 * @var int
	 */
	var $_item;
	
	/**
	 * Value filtered in menu parameters
	 * 
	 * @var mixed
	 */
	var $valueFilterMenu = null;
	
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
		if (is_object ($field))
		{
			$this->_field = $field;
		}
		else
		{
			$this->_field = JTable::getInstance('Collector_fields_type','Table');
			$this->_field->load($field);
		}
	}
	
	/**
	 * Returns a reference to the a user collection, always creating it
	 *
	 * @param	int								$collection	Collection Id
	 * @param	object TableCollector_fields	$field		TableCollector_fields object
	 * @param	int								$item		Item Id
	 * @return	object CollectorField						Reference to a field class
	 */
	public static function getInstance( $collection, $field, $item = 0 )
	{
		// Get a handle to the Joomla! application object
		$application = JFactory::getApplication();
		
		if (!is_object ($field))
		{
			$fieldType = JTable::getInstance('Collector_fields_type','Table');
			$fieldType->load($field);
		
		}
		else
		{
			$fieldType = $field;
		}

		$fieldClass = 'CollectorField_'.ucfirst($fieldType->type);

		if (!class_exists( $fieldClass ))
		{
			jimport('joomla.filesystem.file');
			$path = JPATH_ROOT.'/administrator/components/com_collector/classes/field/'.strtolower($fieldType->type).'/field.'.strtolower($fieldType->type).'.php';
			if( JFile::exists($path) )
			{
				require_once $path;

				if (!class_exists( $fieldClass ))
				{
					return $application->enqueueMessage( 'Field class ' . $fieldClass . ' not found in file.', 'error' );
				}
			}
			else
			{
				return $application->enqueueMessage( 'Field type ' . $fieldType->type . ' not supported. File '.$path.' not found.', 'error' );
			}
		}

		$instance = new $fieldClass($collection,$field,$item);

		// load language
		$extension = 'com_collector';		
		$basePath = JPATH_ROOT.'/administrator/components/com_collector/classes/field/'.strtolower($fieldType->type);
		
		$language = JFactory::getLanguage();
		$language->load($extension, $basePath);
		
		return $instance;
	}
	
	/**
	 * Gets the field attributes for the form definition
	 *
	 * @return array
	 */
	function getFieldAttributes( $attributes = array() )
	{
		$standardAttributes = array(
			'name'			=> $this->_field->tablecolumn,
			'type'			=> "Collector".ucfirst($this->type),
			'label'			=> $this->_field->field,
			'description'	=> $this->_field->description
		);
		if ( $this->_field->required == 1 ) {
			$required = array('required' => "true");
			$standardAttributes = array_merge($standardAttributes, $required);
		}
		
		$result = array_merge($standardAttributes, $attributes);
		return $result;
	}
	
	/**
	 * Gets the action to do on submit
	 *
	 * @return string
	 */
	function onSubmit(&$form)
	{
		return;
	}
	
	/**
	 * Gets the action to do on submit
	 *
	 * @return string
	 */
	function onRegisterField(&$form)
	{
		return;
	}
	
	/**
	 * Gets the action to do on reset search
	 *
	 * @param	JRegistry object	$params
	 *
	 * @return string
	 */
	function resetSearchArea($params)
	{
		return "jQuery( '#filterfield_".$this->_field->tablecolumn."' ).val( '' );";
	}
	
	/**
	 * Gets the action to do on submit search
	 *
	 * @return string
	 */
	function submitSearchArea()
	{
		return;
	}
	
	/**
	 * Gets the javascript for menu creation
	 *
	 * @return string
	 */
	function getMenuJs()
	{
		return;
	}
	
	/**
	 * Gets the type of field
	 *
	 * @return string
	 */
	function getFieldType()
	{
		return $this->_field->type;
	}
	
	/**
	 * Gets the name of field
	 *
	 * @return string
	 */
	function getFieldName()
	{
		return $this->_field->field;
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
		$query->select('h.'.$this->_field->tablecolumn);
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
	function getSearchWhereClause(&$query, $search_all_value)
	{
		$db = JFactory::getDbo();
		$text = $db->quote('%' . $db->escape($search_all_value, true) . '%', false);
		$where = 'LOWER(h.' . $this->_field->tablecolumn . ') LIKE LOWER(' . $text . ')';
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
		return false;
	}
	
	/**
	 * Method to add order by clause
	 *
	 * Can be overloaded/supplemented by the child class
	 */
	function getOrderBy()
	{
		$orderBy = 'h.'.$this->_field->tablecolumn;
		return $orderBy;
	}
	
	/**
	 * Method to get specific attributes
	 *
	 * @param	SimpleXMLElement object		$formXML
	 */
	function getFieldsetAttribs(&$formXML)
	{
		$path = JPath::find(JPATH_ROOT.'/administrator/components/com_collector/classes/field/'.strtolower($this->type), 'field.xml');
		$fieldXML = JFactory::getXML($path);
		
		$formFieldset = $formXML->xpath('/form');
		
		$attributes = array(
			'name'			=> 'attribs-'.strtolower($this->type)
		);
		$child = $formFieldset[0]->addChild('fields');
		foreach ($attributes as $key => $value)
		{
			$child->addAttribute($key,$value);
		}
		
		$fieldset = $fieldXML->xpath('/install/config/fields');
		
		$this->_insertXML($fieldset[0],$child);
		
		return;
	}
	
	/**
	 * Method to copy xml
	 *
	 * Copy all children from source to destination
	 *
	 * @param	SimpleXMLElement object		$source
	 * @param	SimpleXMLElement object		$destination
	 */
	private function _insertXML(&$source,&$destination)
	{
		$childs = $source->children();
		
		foreach ( $childs as $child )
		{
			$name = $child->getName();
			$attributes = $child->attributes();
			$value = '';
			if ($name == 'option')
			{
				$option = $child->asXML();
				$match = array();
				$pattern  = "#<option.*>([A-Z_]+)</option>#";
				if(preg_match($pattern, $option, $match)) {
					$value = $match[1];
				}
			}
			
			$newfield = $destination->addChild($name,$value);
			foreach ($attributes as $key => $value)
			{
				$newfield->addAttribute($key,$value);
			}
			
			if (version_compare(PHP_VERSION, '5.3.0') >= 0) {
				// php >= 5.3
				if ( $child->count() > 0 )
				{
					$this->_insertXML($child,$newfield);
				}
			} else {
				// php < 5.3
				if ( count($child->children()) > 0 )
				{
					$this->_insertXML($child,$newfield);
				}
			}
		}
		
		return;
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
		if ($value == '') {
			return false;
		}
		$return = $value;
		return $return;
	}
	
	/**
	 * Method to display field for edition
	 *
	 * Can be overloaded/supplemented by the child class
	 *
	 * @param	string		$value	Field value
	 */
	function displayEdition($value)
	{
		echo $value;
		return;
	}
	
	/**
	 * Method to display filter in search area
	 *
	 * Can be overloaded/supplemented by the child class
	 *
	 * @param	JRegistry object		$params
	 * @param	string		$value	Field value
	 */
	function displayFilter($params,$value,$menu=false)
	{
		return false;
	}
	
	/**
	 * Method to display value in fulltitle
	 *
	 * Can be overloaded/supplemented by the child class
	 *
	 * @param	string		$value	Field value
	 */
	function displayInTitle($value)
	{
		return $value;
	}
	
	
	/**
	 * Method to display value in fulltitle
	 *
	 * Can be overloaded/supplemented by the child class
	 *
	 * @param	string		$value	Field value
	 */
	function getImportedValue($value)
	{
		return $value;
	}
	
	/**
	 * Method to rebuild fulltitle
	 *
	 * Can be overloaded/supplemented by the child class
	 *
	 * @param	string		$value	Field value
	 */
	function rebuild($value)
	{
		return $value;
	}
	
	/**
	 * Method to display filter in search area
	 *
	 * Can be overloaded/supplemented by the child class
	 *
	 * @param	JRegistry object	$params
	 * @param	string				$value	Field value
	 */
	function displayRequired($params,$value)
	{
		// Create the yes/no button.
				// class="btn-group btn-group-yesno"
		$xml = '<field
				name="filterfield_' .$this->_field->tablecolumn. '"
				type="radio"
				class="btn-group btn-group-yesno"
				default="1"
				label="'.$this->_field->field.'"
				description="'.$this->_field->description.'">
					<option value="0">JNO</option>
					<option value="1">JYES</option>
			</field>';
		
		$fieldXML = JFactory::getXML($xml,false);
		
		$field = new JFormFieldRadio;
		$field->setup($fieldXML,$value,'jform[params][required]');
		
		$html[] = $field->getControlGroup();
		
		return implode($html);
	}
	
	/**
	 * Method to know if field is filtered in menu parameters
	 *
	 * Can be overloaded/supplemented by the child class
	 *
	 * @param	JRegistry object		$params
	 */
	function isFiltered($params)
	{
		$filter = $params->get('filter');
		if (is_array($filter)) {
			$filter = JArrayHelper::toObject($filter);
		}
		
		$nameFilterCollection = 'filterfield_'.$this->_field->tablecolumn;
		if (isset($filter->$nameFilterCollection)) {
			$this->valueFilterMenu = $filter->$nameFilterCollection;
		} else {
			$this->valueFilterMenu = 0;
		}
		
		if ($this->valueFilterMenu == 0) {
			return false;
		} else {
			return true;
		}
		return false;
	}
	
	/**
	 * Method to use ajax
	 *
	 * Can be overloaded/supplemented by the child class
	 */
	function ajax()
	{
		return;
	}
}