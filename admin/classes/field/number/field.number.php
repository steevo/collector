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
 * Field Number class
 *
 * @package	Collector
 */
class CollectorField_Number extends CollectorField
{
	/**
	 * type
	 * 
	 * @var string
	 */
	public $type = 'number';
	
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
			'size'			=> "60",
			'maxlength'		=> "254",
			'default'		=> '',
			'template'		=> $this->_field->attribs['template']
		);
		
		return parent::getFieldAttributes($attributes);
	}
	
	/**
	 * Method to add order by clause
	 *
	 * Can be overloaded/supplemented by the child class
	 */
	function getOrderBy()
	{
		$orderBy = 'CAST(h.'.$this->_field->tablecolumn.' AS DECIMAL(18,5))';
		return $orderBy;
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
		if ( $value != '' )
		{
			$template = $this->_field->attribs['template'];
			if ( $template == '' )
			{
				$template = '%%';
			}

			$return = JString::str_ireplace( '%%', $value, $template );
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
		return htmlspecialchars($value, ENT_QUOTES);
	}
}

require_once(JPATH_ROOT.'/libraries/joomla/form/fields/text.php');

/**
 * Form Field class for the Collector.
 *
 * @package		Collector
 */
class JFormFieldCollectorNumber extends JFormFieldText
{
	protected $type = 'CollectorNumber';
	
	protected function getInput()
	{
		$input = parent::getInput();
		
		$html = array();
		
		$template = $this->element['template'] == '' ? '%%' : $this->element['template'];
		$template = explode('%%',$template);
		if ( $template[0] != '' )
		{
			$margin = '5px 5px 5px 0';
		}
		else
		{
			$margin = '5px 0';
		}
		
		$html[] = '<div class="fltlft" style="display:inline; margin: '.$margin.';" >'.$template[0].'</div>';
		$html[] = $input;
		$html[] = '<div class="fltlft" style="display:inline; margin: 5px 0;" >'.$template[1].'</div>';
		
		return implode($html);
	}
}