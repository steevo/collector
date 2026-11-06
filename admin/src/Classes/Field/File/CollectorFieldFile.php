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

namespace Steevo\Component\Collector\Administrator\Classes\Field\File;

use Steevo\Component\Collector\Administrator\Classes\CollectorField;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Language\Text;

defined('_JEXEC') or die;

/**
 * Field File class
 *
 * @package	Collector
 */
class CollectorFieldFile extends CollectorField
{
	/**
	 * type
	 * 
	 * @var string
	 */
	public $type = 'file';
	
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
		$query->select('jd'.$this->_field->id.'.file_id AS `'.$this->_field->tablecolumn.'`');
		$query->join('LEFT', '#__jdownloads_files AS jd'.$this->_field->id.' ON jd'.$this->_field->id.'.file_id = h.'.$this->_field->tablecolumn);
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
		$db = Factory::getDbo();
		$text = $db->quote('%' . $db->escape($search_all_value, true) . '%', false);
		$where = 'LOWER(jd'.$this->_field->id.'.file_title) LIKE LOWER(' . $text . ')';
		return $where;
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
		$db = Factory::getDbo();
		if ( $value == '' ) {
			$default = $this->_field->attribs['default'];
			if ( $default != '' ) {
				$value = $default;
			}
		}
		if ( $value == '' ) {
			return false;
		} else {
			$listing_template = $this->_field->attribs['listing_template'];
			$detail_template = $this->_field->attribs['detail_template'];
			
			if (( $listing && ( $listing_template == 0 ) ) || ( !$listing && ( $detail_template == 0 ) )) {
				$return = HTMLHelper::_('content.prepare', '{jd_file file==' .$value. '}');
			} else if ( $listing && ( $listing_template == 1 ) ) {
				$query = $db->getQuery(true);
	
				// Check file name in jdownloads.
				$query->select('file_title, file_pic');
				$query->from('#__jdownloads_files');
				$query->where('file_id = "'.$value.'"');
				$db->setQuery($query);
				$db->execute();
				$file = $db->loadAssoc();
				$return = '<img src="'.JURI::base().'components/com_jdownloads/assets/images/jdownloads/fileimages/'.$file['file_pic'].'" style="vertical-align:middle;" border="0" width="32" height="32" alt="" title="" />';
				$return .= $file['file_title'];
			} else if (( $listing && ( $listing_template == 2 ) ) || ( !$listing && ( $detail_template == 2 ) )) {
				$query = $db->getQuery(true);
	
				// Check file name in jdownloads.
				$query->select('file_title, file_alias, file_pic');
				$query->from('#__jdownloads_files');
				$query->where('file_id = "'.$value.'"');
				$db->setQuery($query);
				$db->execute();
				$file = $db->loadAssoc();
				$url = JURI::base().'index.php?option=com_jdownloads&view=download&id='.$value.':'.$file['file_alias'];
				$return = '<img src="'.JURI::base().'components/com_jdownloads/assets/images/jdownloads/fileimages/'.$file['file_pic'].'" style="vertical-align:middle;" border="0" width="32" height="32" alt="" title="" />';
				$return .= '<a href="'.$url.'">'.$file['file_title'].'</a>';
			}
		}
		
		return parent::display($return,$listing,$params,$itemId);
	}
}

