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

/**
 * Field Date class
 *
 * @package	Collector
 */
class CollectorField_Date extends CollectorField
{
	/**
	 * type
	 * 
	 * @var string
	 */
	public $type = 'date';
	
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
			'class'			=> "inputbox",
			'size'			=> "22",
			// 'format'		=> $this->_field->attribs['format'],
			// 'format'		=> '%Y-%m-%d %H:%M:%S',
			'format'		=> '%Y-%m-%d',
			'filter'		=> "user_utc"
		);
		
		return parent::getFieldAttributes($attributes);
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
		$format = $this->_field->attribs['format'];
		if ( $format == '' )
		{
			$format = '%Y-%m-%d';
		}
		if ($value != 0) {
			$format = str_replace('%','',$format);
			if (DateTime::createFromFormat('Y-m-d H:i:s', $value) === FALSE) {
				// it's not a datetime format
				$value = DateTime::createFromFormat($format, $value)->format('Y-m-d H:i:s');
			}
			$return = JHTML::_( 'date', $value, $format);
		}
		else
		{
			return false;
		}
		return $return;
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
		$format = $this->_field->attribs['format'];
		if ( $format == '' )
		{
			$format = '%Y-%m-%d';
		}
		if ($value != 0) {
			$format = str_replace('%','',$format);
			if (DateTime::createFromFormat('Y-m-d H:i:s', $value) === FALSE) {
				// it's not a datetime format
				$value = DateTime::createFromFormat($format, $value)->format('Y-m-d H:i:s');
			}
			return JHTML::_( 'date', $value, $format);
		}
		
		return;
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
		$format = $this->_field->attribs['format'];
		if ( $format == '' )
		{
			$format = '%Y-%m-%d';
		}
		if ($value != 0) {
			$format = str_replace('%','',$format);
			if (DateTime::createFromFormat('Y-m-d H:i:s', $value) === FALSE) {
				// it's not a datetime format
				$value = DateTime::createFromFormat($format, $value)->format('Y-m-d H:i:s');
			}
		}
		return $value;
	}
	
	/**
	 * Method to display value in fulltitle
	 *
	 * Can be overloaded/supplemented by the child class
	 *
	 * @param	string		$value	Field value
	 */
	function rebuild($value)
	{
		$format = $this->_field->attribs['format'];
		if ( $format == '' )
		{
			$format = '%Y-%m-%d';
		}
		if ($value != 0) {
			$format = str_replace('%','',$format);
			if (DateTime::createFromFormat('Y-m-d H:i:s', $value) === FALSE) {
				// it's not a datetime format
				$value = DateTime::createFromFormat($format, $value)->format('Y-m-d H:i:s');
			}
			return JHTML::_( 'date', $value, $format);
		}
		
		return;
	}
}

require_once(JPATH_ROOT.'/libraries/joomla/form/fields/calendar.php');

/**
 * Form Field class for the Collector.
 *
 * @package		Collector
 */
class JFormFieldCollectorDate extends JFormFieldCalendar
{
	protected $type = 'CollectorDate';
	
	protected function getInput()
	{
		
		if ($this->value != 0) {
			$format = str_replace('%','',$this->format);
			
			if (DateTime::createFromFormat('Y-m-d H:i:s', $this->value) === FALSE) {
				// it's not a datetime format
				$this->value = DateTime::createFromFormat($format, $this->value)->format('Y-m-d H:i:s');
			}
		}
		
		return parent::getInput();
	}
}