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

namespace Steevo\Component\Collector\Administrator\Classes\Field\Number;

use Steevo\Component\Collector\Administrator\Classes\CollectorField;
use Joomla\CMS\Factory;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Language\Text;
use Joomla\String\StringHelper;
use Steevo\Component\Collector\Administrator\Classes\Field\Number\Searchmethod;

defined('_JEXEC') or die;

/**
 * Field Number class
 *
 * @package	Collector
 */
class CollectorFieldNumber extends CollectorField
{
	/**
	 * type
	 * 
	 * @var string
	 */
	public $type = 'number';
	
	/**
	 * type
	 * 
	 * @var string
	 */
	public $_minValue = '';
	
	/**
	 * type
	 * 
	 * @var string
	 */
	public $_maxValue = '';
	
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
	 * Gets the action to do on submit
	 *
	 * @return string
	 */
	function resetSearchArea($params)
	{
		$filtered = $this->isFiltered($params);
		
		// Get min and max values
		$minmaxValued = $this->getMinMaxValued();
		$minmaxFiltered = explode ('|',$this->valueFilterMenu);
		if ( $filtered && ($minmaxFiltered[0]!='') ) {
			$minValue = $minmaxFiltered[0];
		} else {
			$minValue = $minmaxValued[0];
		}
		if ( $filtered && ($minmaxFiltered[1]!='') ) {
			$maxValue = $minmaxFiltered[1];
		} else {
			$maxValue = $minmaxValued[1];
		}
		$return = array();
		$return[] = "jQuery( '#filterfield_".$this->_field->tablecolumn."_min' ).val( ".$minValue." );";
		$return[] = "jQuery( '#filterfield_".$this->_field->tablecolumn."_max' ).val( ".$maxValue." );";
		$return[] = "jQuery( '#slider-range-".$this->_field->tablecolumn."' ).slider( 'values', 0 , ".$minValue." );";
		$return[] = "jQuery( '#slider-range-".$this->_field->tablecolumn."' ).slider( 'values', 1 , ".$maxValue." );";
		$return[] = "jQuery( '#filterfield_".$this->_field->tablecolumn."' ).val( '|' );";
		
		return implode($return);
	}
	
	/**
	 * Gets the action to do on submit search
	 *
	 * @return string
	 */
	function submitSearchArea()
	{
		$return = "jQuery( '#filterfield_".$this->_field->tablecolumn."' ).val( jQuery( '#filterfield_".$this->_field->tablecolumn."_min' ).val() + '|' + jQuery( '#filterfield_".$this->_field->tablecolumn."_max' ).val() );";
		
		return $return;
	}
	
	/**
	 * Gets the javascript for menu creation
	 *
	 * @return string
	 */
	function getMenuJs()
	{
		// Get min and max values
		$minmaxValued = $this->getMinMaxValued();
		$minValue = $minmaxValued[0];
		$maxValue = $minmaxValued[1];
		
		$js = "
		populateHidden = function() {
			var min = jQuery( '#filterfield_".$this->_field->tablecolumn."_min' ).val();
			if ((min < ".$minValue.")&&(min != '')) {
				min = ".$minValue.";
				jQuery( '#filterfield_".$this->_field->tablecolumn."_min' ).val(min);
			}
			var max = jQuery( '#filterfield_".$this->_field->tablecolumn."_max' ).val();
			if ((max > ".$maxValue.")&&(max != '')) {
				max = ".$maxValue.";
				jQuery( '#filterfield_".$this->_field->tablecolumn."_max' ).val(max);
			}
			jQuery( '#filterfield_".$this->_field->tablecolumn."' ).val(min+'|'+max);
		};";
		
		return $js;
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
			// 'template'		=> $this->_field->attribs['template'],
			// 'search_method'	=> $this->_field->attribs['search_method'],
			// 'decimal'		=> $this->_field->attribs['decimal']
		);
		
		return parent::getFieldAttributes($attributes);
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
		$filtered = $this->isFiltered($params);
		
		if ((( $value != '' ) && ( $value != '|' )) || ( $filtered ))
		{
			// Get min and max values
			$minmaxFiltered = explode ('|',$this->valueFilterMenu);
			$minmax = explode ('|',$value);
			$min = $minmax[0];
			$max = $minmax[1];
			
			if ( ($min == '') && $filtered && ($minmaxFiltered[0]!='') ) {
				$min = $minmaxFiltered[0];
			}
			if ( ($max == '') && $filtered && ($minmaxFiltered[1]!='') ) {
				$max = $minmaxFiltered[1];
			}
			
			if ( $min != '' )
			{
				$query->where('CAST(h.'.$this->_field->tablecolumn.' AS DECIMAL(18,5)) >= "'.$min.'"');
			}
			if ( $max != '' )
			{
				$query->where('CAST(h.'.$this->_field->tablecolumn.' AS DECIMAL(18,5)) <= "'.$max.'"');
			}
			$query->where('h.'.$this->_field->tablecolumn.' != ""');
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
		$orderBy = 'CAST(h.'.$this->_field->tablecolumn.' AS DECIMAL(18,5))';
		return $orderBy;
	}
	
	/**
	 * Method to get the min and max values
	 */
	function getMinMaxValued()
	{
		if ($this->_minValue == '')
		{
			// Get min and max values
			$db = Factory::getDBO();
			$query = $db->getQuery(true);
			$query->select('MIN(CAST(h.'.$this->_field->tablecolumn.' AS DECIMAL(18,'.$this->_field->attribs['decimal'].')))');
			$query->from('#__collector_items_history_'.$this->_collection.' as h');
			$query->where('h.state = 1');
			$query->where('h.'.$this->_field->tablecolumn.' != ""');
			$db->setQuery( $query );
			$this->_minValue = $db->loadResult();
			$query = $db->getQuery(true);
			$query->select('MAX(CAST(h.'.$this->_field->tablecolumn.' AS DECIMAL(18,'.$this->_field->attribs['decimal'].')))');
			$query->from('#__collector_items_history_'.$this->_collection.' as h');
			$query->where('h.state = 1');
			$query->where('h.'.$this->_field->tablecolumn.' != ""');
			$db->setQuery( $query );
			$this->_maxValue = $db->loadResult();
		}
		
		return array($this->_minValue,$this->_maxValue);
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
		$wa = Factory::getApplication()->getDocument()->getWebAssetManager();
		$wa->useScript('jquery');

		HTMLHelper::script('administrator/components/com_collector/classes/field/number/bootstrap-slider/dist/bootstrap-slider.min.js');
		HTMLHelper::stylesheet('administrator/components/com_collector/classes/field/number/bootstrap-slider/dist/css/bootstrap-slider.min.css');
		
		// Get min and max values
		$minmaxValued = $this->getMinMaxValued();
		$minValue = $minmaxValued[0];
		$maxValue = $minmaxValued[1];
		
		// Initiliase variables.
		$app	= Factory::getApplication();
		$html = array();
		$attr = '';
		if (($value == '')||($value == '0'))
		{
			$value = '|';
		}
		$minmax = explode ('|',$value);
		$min = $minmax[0];
		$max = $minmax[1];
		
		$user	= Factory::getApplication()->getIdentity();
		$groups	= implode(',', $user->getAuthorisedViewLevels());
		
		if ($menu) {
			$prefix = 'jform[params][filter][';
			$suffix = ']';
		} else {
			$prefix = '';
			$suffix = '';
		}
		
		if ( ( $this->_field->attribs['search_method'] == 0 ) || ($menu == true) ) {
		
			// Initialize some field attributes.
			if ( (!$params->get('show_entire_listing')) && ($menu == false) ) {
				$requiredparams = $params->get('required');
				$required = $requiredparams['filterfield_'.$this->_field->tablecolumn]?' required':'';
			} else {
				$required = '';
			}
			$attr .= ' class="inputbox'.$required.'"';
			$attr .= ' size="1"';

			$filtered = $this->isFiltered($params);
			
			if ( ($menu == true) || (!$filtered) || ( $filtered && (!$params->get('hide_filter'))) )
			{
				if ( (!$params->get('show_entire_listing')) && ($menu == false) ) {
					$requiredparams = $params->get('required');
					$required = $requiredparams['filterfield_'.$this->_field->tablecolumn]?'<span class="star">&nbsp;*</span>&nbsp;&nbsp;':'&nbsp;&nbsp;';
				} else {
					$required = '&nbsp;&nbsp;';
				}
				
				$minmaxFiltered = explode ('|',$this->valueFilterMenu);
				if ( $min == '' ) {
					if ( $filtered && ($minmaxFiltered[0]!='') ) {
						$min = $minmaxFiltered[0];
					}
				}
				if ( $max == '' ) {
					if ( $filtered && ($minmaxFiltered[1]!='') ) {
						$max = $minmaxFiltered[1];
					}
				}
				
				// Create filter
				$html[] = $this->_field->field . $required;
				if ($menu == true) {
					$html[] = '</td><td>';
				}
				$html[] = Text::_('COM_COLLECTOR_NUMBER_FIELD_SEARCH_FROM');
				$html[] = '<input type=text style=\'width:'.$this->_field->attribs['textboxwidth'].'px;\' onchange=\'populateHidden();\' id=\'filterfield_'.$this->_field->tablecolumn.'_min\' name=\''.$prefix.'filterfield_'.$this->_field->tablecolumn.'_min'.$suffix.'\' '.trim($attr).' value=\''.$min.'\' placeholder=\''.$minValue.'\'/>';
				$html[] = Text::_('COM_COLLECTOR_NUMBER_FIELD_SEARCH_TO');
				$html[] = '<input type=text style=\'width:'.$this->_field->attribs['textboxwidth'].'px;\' onchange=\'populateHidden();\' id=\'filterfield_'.$this->_field->tablecolumn.'_max\' name=\''.$prefix.'filterfield_'.$this->_field->tablecolumn.'_max'.$suffix.'\' '.trim($attr).' value=\''.$max.'\' placeholder=\''.$maxValue.'\'/>';
				$html[] = '<input type=hidden id=\'filterfield_'.$this->_field->tablecolumn.'\' name=\''.$prefix.'filterfield_'.$this->_field->tablecolumn.$suffix.'\' value=\''.$value.'\' />';
			}
			else
			{
				$html[] = '<input type=hidden id=\'filter_field_'.$this->_field->tablecolumn.'\' name=\''.$prefix.'filter_field_'.$this->_field->tablecolumn.$suffix.'\' value=\''.$value.'\' />';
			}

			if ($menu == true) {
				$return = '<td>'.implode($html).'</td>';
			} else {
				$return = implode($html);
			}
		} else {
			$filtered = $this->isFiltered($params);
			if ( (!$filtered) || ( $filtered && (!$params->get('hide_filter'))) )
			{
				if ( (!$params->get('show_entire_listing')) ) {
					$requiredparams = $params->get('required');
					$required = $requiredparams['filterfield_'.$this->_field->tablecolumn]?'<span class="star">&nbsp;*</span>&nbsp;&nbsp;':'&nbsp;&nbsp;';
				} else {
					$required = '&nbsp;&nbsp;';
				}
				
				$minmaxFiltered = explode ('|',$this->valueFilterMenu);
				if ( $min == '' ) {
					if ( $filtered && ($minmaxFiltered[0]!='') ) {
						$min = $minmaxFiltered[0];
					} else {
						$min = $minValue;
					}
				}
				if ( $max == '' ) {
					if ( $filtered && ($minmaxFiltered[1]!='') ) {
						$max = $minmaxFiltered[1];
					} else {
						$max = $maxValue;
					}
				}
				
				$doc = Factory::getDocument();

				$js1 = "
				jQuery(document).ready(function(){
					jQuery.ui.slider.prototype.widgetEventPrefix = 'slider';
					jQuery( '#slider-range-".$this->_field->tablecolumn."' ).slider({
						animated: true,
						range: true,
						step: ".$this->_field->attribs['sliderstep'].",
						min: ".$minValue.",
						max: ".$maxValue.",
						values: [ ".$min.", ".$max." ],
						slide: function( event, ui ) {
							jQuery( '#filterfield_".$this->_field->tablecolumn."_min' ).val( ui.values[ 0 ] );
							jQuery( '#filterfield_".$this->_field->tablecolumn."_max' ).val( ui.values[ 1 ] );
						}
					});
					jQuery( '#filterfield_".$this->_field->tablecolumn."_min' ).val( jQuery( '#slider-range-".$this->_field->tablecolumn."' ).slider( 'values', 0 ) );
					jQuery( '#filterfield_".$this->_field->tablecolumn."_max' ).val( jQuery( '#slider-range-".$this->_field->tablecolumn."' ).slider( 'values', 1 ) );
				})";
				$doc->addScriptDeclaration($js1);
				$js2 = "
				populateSlider = function() {
					var min = jQuery( '#filterfield_".$this->_field->tablecolumn."_min' ).val();
					if (min < ".$minValue.") {
						min = ".$minValue.";
						jQuery( '#filterfield_".$this->_field->tablecolumn."_min' ).val(min);
					}
					var max = jQuery( '#filterfield_".$this->_field->tablecolumn."_max' ).val();
					if (max > ".$maxValue.") {
						max = ".$maxValue.";
						jQuery( '#filterfield_".$this->_field->tablecolumn."_max' ).val(max);
					}
					jQuery( '#slider-range-".$this->_field->tablecolumn."' ).slider( 'values', 0 , min );
					jQuery( '#slider-range-".$this->_field->tablecolumn."' ).slider( 'values', 1 , max );
				};";
				$doc->addScriptDeclaration($js2);
				
				if ($this->_field->attribs['textboxeditable']) {
					$readonly = 'onchange="populateSlider();"';
				} else {
					$readonly = 'readonly';
				}
				
				$html[] = $this->_field->field . $required;
				$html[] = '<input id="filterfield_'.$this->_field->tablecolumn.'_min" type=text '.$readonly.' class="search-element" style=\'width:'.$this->_field->attribs['textboxwidth'].'px;\' name=\'filterfield_'.$this->_field->tablecolumn.'_min\' '.trim($attr).' value=\''.$min.'\'/>';
				$html[] = '<div id="slider-range-'.$this->_field->tablecolumn.'" class="search-element" style="vertical-align:middle;width:'.$this->_field->attribs['sliderwidth'].'px;"></div>';
				$html[] = '<input id="filterfield_'.$this->_field->tablecolumn.'_max" type=text '.$readonly.' class="search-element" style=\'width:'.$this->_field->attribs['textboxwidth'].'px;\' name=\'filterfield_'.$this->_field->tablecolumn.'_max\' '.trim($attr).' value=\''.$max.'\'/>';
				$html[] = '<input type=hidden id=\'filterfield_'.$this->_field->tablecolumn.'\' name=\'filterfield_'.$this->_field->tablecolumn.'\' value=\''.$value.'\' />';
			}
			else
			{
				$html[] = '<input type=hidden id=\'filter_field_'.$this->_field->tablecolumn.'\' name=\'filter_field_'.$this->_field->tablecolumn.'\' value=\''.$value.'\' />';
			}
			
			$return = implode($html);
		}
		
		return $return;
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
		if ( $value != '' )
		{
			$template = $this->_field->attribs['template'];
			if ( $template == '' )
			{
				$template = '%%';
			}

			$return = StringHelper::str_ireplace( '%%', $value, $template );
			
			if (!isset($params['compare_view'])) {
				if ($params['compare_view'] != true) {
					if ( $this->_field->attribs['show_fieldlink'] && ( $this->_field->filter == 1 ) )
					{
						$link= 'index.php?option=com_collector&view=collection&id='.$this->_field->collection.'&filterfield_'.$this->_field->tablecolumn.'='.$value.'|'.$value;
						$return = '<a href="'.JRoute::_($link).'">'.$return.'</a>';
					}
				}
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
	 * @param	string					$value		Field value
	 */
	function displayInTitle($value)
	{
		return htmlspecialchars($value, ENT_QUOTES);
	}
	
	/**
	 * Method to rebuild fulltitle
	 *
	 * Can be overloaded/supplemented by the child class
	 *
	 * @param	string					$value		Field value
	 */
	function rebuild($value)
	{
		return htmlspecialchars($value, ENT_QUOTES);
	}
	
	/**
	 * Affichage pour le contexte de comparaison de versions.
	 *
	 * Appelle display() (qui applique le template de l'unité, ex: "42 km")
	 * puis ajoute une flèche ↑/↓ colorée si les deux valeurs numériques diffèrent.
	 */
	function displayComparison($value, $otherValue = null, $side = 'v1')
	{
		$display = $this->display($value, false, new \Joomla\Registry\Registry());
		if ($display === false || $display === null || $display === '') {
			return false;
		}
 
		$arrow = '';
		$cls   = '';
 
		if ($otherValue !== null
			&& is_numeric($value)
			&& is_numeric($otherValue)
			&& (float)$value !== (float)$otherValue
		) {
			$isHigher = (float)$value > (float)$otherValue;
			$cls      = $isHigher ? ' up' : ' down';
			$label    = $isHigher
				? \Joomla\CMS\Language\Text::_('COM_COLLECTOR_ITEM_HISTORY_COMPARE_HIGHER')
				: \Joomla\CMS\Language\Text::_('COM_COLLECTOR_ITEM_HISTORY_COMPARE_LOWER');
			$glyph    = $isHigher ? '↑' : '↓';
			$arrow    = ' <span class="diff-arrow" title="' . htmlspecialchars($label) . '">' . $glyph . '</span>';
		}
 
		return '<span class="value-number' . $cls . '">' . $display . $arrow . '</span>';
	}
}
