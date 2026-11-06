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

namespace Steevo\Component\Collector\Administrator\Classes\Field\Text;

use Steevo\Component\Collector\Administrator\Classes\CollectorField;
use Joomla\CMS\Form\Field\EditorField;

defined('_JEXEC') or die;

/**
 * Field Text class
 *
 * @package	Collector
 */
class CollectorFieldText extends CollectorField
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
		// echo $form->getField($this->_field->tablecolumn)->save();
		return;
	}
	
	/**
	 * Gets the action to do on submit
	 *
	 * @return string
	 */
	function onRegisterField(&$form)
	{
		// echo $form->getField('template','attribs-text')->save();
		return;
	}
	
	/**
	 * Affichage pour le contexte de comparaison de versions.
	 *
	 * Le champ text est un éditeur WYSIWYG — sa valeur contient du HTML.
	 * Pour le diff, on compare les textes après strip_tags() afin d'obtenir
	 * un diff sémantique lisible plutôt qu'un diff de balises HTML.
	 */
	function displayComparison($value, $otherValue = null, $side = 'v1')
	{
		if ($value === '' || $value === null) {
			return false;
		}
 
		if ($otherValue !== null && (string)$value !== (string)$otherValue) {
			// Diff sur le texte brut (sans balises) — lisible et sans artefacts
			$plainA = htmlspecialchars(strip_tags((string)$value),      ENT_QUOTES);
			$plainB = htmlspecialchars(strip_tags((string)$otherValue),  ENT_QUOTES);
			return '<div class="diff-word">' . self::wordDiffHtml($plainA, $plainB, $side) . '</div>';
		}
 
		// Valeurs identiques : affichage HTML formaté du contenu de l'éditeur
		$display = $this->display($value, false, new \Joomla\Registry\Registry());
		return ($display !== false && $display !== '') ? $display : htmlspecialchars((string)$value, ENT_QUOTES);
	}
	
	public function normalizeForComparison(string $value): string
	{
		return trim(strip_tags($value));
	}
}

