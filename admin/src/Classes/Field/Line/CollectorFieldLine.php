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

namespace Steevo\Component\Collector\Administrator\Classes\Field\Line;

use Steevo\Component\Collector\Administrator\Classes\CollectorField;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Mail\MailHelper;

defined('_JEXEC') or die;

/**
 * Field Line class
 *
 * @package	Collector
 */
class CollectorFieldLine extends CollectorField
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
	function display($value,$listing=true,$params=array(),$itemId=0)
	{
		if ($value == '') {
			return false;
		}
		
		$emailMode = $this->_field->attribs['emailMode'];
		$weblinkMode = $this->_field->attribs['weblinkMode'];
		
		if ( ( $emailMode == 1 ) && ( MailHelper::isEmailAddress($value) ) )
		{
			$return = HTMLHelper::_('email.cloak', $value);
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
		return parent::display($return,$listing,$params,$itemId);
	}
	
	/**
	 * Affichage pour le contexte de comparaison de versions.
	 *
	 * Correctif par rapport à l'ancienne approche dans HtmlView :
	 * le diff est réalisé sur les valeurs BRUTES (htmlspecialchars) et non sur la
	 * sortie de display() qui peut contenir du HTML (email masqué, lien hypertexte).
	 * Comparer du HTML tokenisé par \s+ produit des résultats incohérents.
	 */
	function displayComparison($value, $otherValue = null, $side = 'v1')
	{
		if ($value === '' || $value === null) {
			return false;
		}
 
		if ($otherValue !== null && (string)$value !== (string)$otherValue) {
			// Diff sur les valeurs brutes échappées — évite les artefacts HTML
			return '<div class="diff-word">'
				. self::wordDiffHtml(
					htmlspecialchars((string)$value,  ENT_QUOTES),
					htmlspecialchars((string)$otherValue, ENT_QUOTES),
					$side
				)
				. '</div>';
		}
 
		// Valeurs identiques : affichage formaté normal (liens, emails masqués…)
		$display = $this->display($value, false, new \Joomla\Registry\Registry());
		return ($display !== false && $display !== '') ? $display : htmlspecialchars((string)$value, ENT_QUOTES);
	}
}
