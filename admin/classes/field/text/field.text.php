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
 * Field Text class
 *
 * @package	Collector
 */
class CollectorField_Text extends CollectorField
{
	/**
	 * type
	 * 
	 * @var string
	 */
	public $type = 'text';
	
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
			'buttons'		=> true,
			'hide'			=> "pagebreak,readmore",
			'class'			=> "inputbox",
			'filter'		=> "safehtml",
			'default'		=> $default
		);
		
		return parent::getFieldAttributes($attributes);
	}
	
	/**
	 * Gets the action to do on submit
	 *
	 * @return string
	 */
	function onSubmit(&$form)
	{
		echo $form->getField($this->_field->tablecolumn)->save();
		return;
	}
	
	/**
	 * Gets the action to do on submit
	 *
	 * @return string
	 */
	function onRegisterField(&$form)
	{
		echo $form->getField('template','attribs-text')->save();
		return;
	}
	
	
}

require_once(JPATH_ROOT.'/libraries/cms/form/field/editor.php');

/**
 * Form Field class for the Collector.
 *
 * @package		Collector
 */
class JFormFieldCollectorText extends JFormFieldEditor
{
	public $type = 'CollectorText';
	
	/**
	 * Method to get the field label markup.
	 *
	 * @return  string  The field label markup.
	 *
	 * @since   11.1
	 */
	protected function getLabel()
	{
		// Initialise variables.
		$label = '';
		
		$label .= '<div class="clr"></div>';
		
		$label .= parent::getLabel();
		
		$label .= '<div class="clr"></div>';
		
		return $label;
	}
}