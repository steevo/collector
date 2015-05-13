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
 * Field Line class
 *
 * @package	Collector
 */
class CollectorField_Line extends CollectorField
{
	/**
	 * type
	 * 
	 * @var string
	 */
	public $type = 'line';
	
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
		if ( $this->_item == 0 ) {
			$default = $this->_field->attribs['template'];
		} else {
			$default = "";
		}
		$attributes = array(
			'size'			=> "60",
			'maxlength'		=> "254",
			'default'		=> $default
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
		if ($value == '') {
			return false;
		}
		
		$emailMode = $this->_field->attribs['emailMode'];
		$weblinkMode = $this->_field->attribs['weblinkMode'];
		
		if ( ( $emailMode == 1 ) && ( JMailHelper::isEmailAddress($value) ) )
		{
			$return = JHTML::_('email.cloak', $value);
		}
		else if ( ( $weblinkMode == 1 ) && ( preg_match("=www.=", $value) ) )
		{
			$url = trim($value);
			// check en tete
			if ( !preg_match("=://=", $url) ) {
				$url = "http://".$url;
			}

			$motif='#^^http://([a-zA-Z0-9-]+.)?([a-zA-Z0-9-]+.)?[a-zA-Z0-9-]+\.[a-zA-Z]{2,4}(:[0-9]+)?(/[a-zA-Z0-9-]*)?(.[a-zA-Z0-9]{1,4})?$#';
			if ( preg_match($motif,$url) ) {
				$return = '<a href="'.$url.'">'.$value.'</a>';
			} else {
				$return = htmlspecialchars($value, ENT_QUOTES);
			}
		}
		else
		{
			$return = htmlspecialchars($value, ENT_QUOTES);
		}
		return $return;
	}
}

require_once(JPATH_ROOT.'/libraries/joomla/form/fields/text.php');

/**
 * Form Field class for the Collector.
 *
 * @package		Collector
 */
class JFormFieldCollectorLine extends JFormFieldText
{
	protected $type = 'CollectorLine';
}