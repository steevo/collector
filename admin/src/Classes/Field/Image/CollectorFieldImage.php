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

namespace Steevo\Component\Collector\Administrator\Classes\Field\Image;

use Steevo\Component\Collector\Administrator\Classes\CollectorField;
use Joomla\CMS\Factory;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Language\Text;

defined('_JEXEC') or die;

/**
 * Field Image class
 *
 * @package	Collector
 */
class CollectorFieldImage extends CollectorField
{
	/**
	 * type
	 * 
	 * @var string
	 */
	public $type = 'image';
	
	/**
	 * make link
	 * 
	 * @var boolean
	 */
	public $makeLink = false;
	
	/**
	 * type
	 * 
	 * @var string
	 */
	public $directory = '';
	
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
		
		$this->directory = 'images/collector/collection/'.$collection;
	}
	
	/**
	 * Gets the field attributes for the form definition
	 *
	 * @return string
	 */
	function getFieldAttributes($attributes = array())
	{
		$attributes = array(
			'directory'		=> $this->directory,
			'size'			=> "60"
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
		// image de substitution
		// la valeur par défaut est enregistrée au même format que les valeurs : "chemin|desc|titre|..."
		$imagedefault = HTMLHelper::cleanImageURL(explode('|', (string) ($this->_field->attribs['default'] ?? ''))[0]);
		$imagedefaultUrl = $imagedefault->url;
		
		$url = explode('|',$value);
		if (( $url[0] == '' )&&(!$params->get('show_emptyfield'))) {
			return false;
		}
		if ( $value == '' ) {
			$value = '||||||';
		}
		$imageInfos = explode('|',$value);
		$imageInfos = HTMLHelper::cleanImageURL($imageInfos[0]);
		$imageUrl = $imageInfos->url;
		$imagePath = $imageUrl;
		$imageName = basename(JPATH_SITE.'/'.$imagePath);
		// Champs créés avant la 0.9.0 : leurs attributs ne contiennent pas ces réglages, on reprend les valeurs par défaut de field.xml
		$attribs = (array) $this->_field->attribs + array(
			'imgdesc'          => '',
			'imgtitle'         => '',
			'maxwidthlisting'  => 100,
			'maxheightlisting' => 80,
			'maxwidth'         => 500,
			'maxheight'        => 400,
		);
		$imageDesc = $attribs['imgdesc'];
		$imageTitle = $attribs['imgtitle'];
		if ($listing == true)
		{
			$largeurmax = (int) $attribs['maxwidthlisting'] ?: 100;
			$hauteurmax = (int) $attribs['maxheightlisting'] ?: 80;
		}
		else
		{
			$largeurmax = (int) $attribs['maxwidth'] ?: 500;
			$hauteurmax = (int) $attribs['maxheight'] ?: 400;
		}
		
		$image = '';

		if ($imageUrl == '')
		{
			$image=JPATH_SITE.'/'.$imagedefaultUrl; // adresse de l'image
			// $image=JPATH_SITE.'/components/com_collector/assets/images/camera.png'; // adresse de l'image
			$URLimage='./'.$imagedefaultUrl; // adresse de l'image
		}
		else
		{
			$image=JPATH_SITE.'/'.$imageUrl; // adresse de l'image
			if (!file_exists($image)) {
				$image=JPATH_SITE.'/'.$imagedefaultUrl; // adresse de l'image
				$URLimage='./'.$imagedefaultUrl; // adresse de l'image
			} else {
				$URLimage='./'.$imageUrl; // adresse de l'image
			}
		}

		$taille = is_file($image) ? getimagesize($image) : false;
		// image illisible ou absente : on affiche le cadre aux dimensions maximales
		$largeur = $taille ? $taille[0] : $largeurmax;
		$hauteur = $taille ? $taille[1] : $hauteurmax;
		
		if ( ($hauteur/$hauteurmax) < ($largeur/$largeurmax) )
		{
			if ($largeur < $largeurmax)
			{
				$size= 'width:'.$largeur.'px;';
			}
			else
			{
				$size= 'width:'.$largeurmax.'px;';
			}
		}
		else
		{
			if ($hauteur < $hauteurmax)
			{
				$size= 'height:'.$hauteur.'px;';
			}
			else
			{
				$size= 'height:'.$hauteurmax.'px;';
			}
		}
		
		$valign = 'vertical-align:middle;';
		$style = ' style="' . $size . $valign . '"';
		
		// $return = '<a data-bs-toggle="modal" data-bs-target="#'.$this->_item.'-'.$this->_field->id.'"  />';
		$return = '';
		$return .= '<img data-bs-toggle="modal" data-bs-target="#'.$itemId.'-'.$this->_field->id.'" alt="'.$imageDesc.'" title="'.$imageTitle.'" align="middle" border="1" src="'.$URLimage.'" '.$style.' />';
		$return .= '<div id="'.$itemId.'-'.$this->_field->id.'" class="modal fade" tabindex="-1" aria-labelledby="'.$itemId.'-'.$this->_field->id.'_image" aria-hidden="true">
			<div class="modal-dialog">
				<div class="modal-content">
					<div class="modal-body">
						<img alt="'.$imageDesc.'" title="'.$imageTitle.'" align="middle" border="1" src="'.$URLimage.'" />
					</div>
				</div>
			</div>
		</div>';
		return parent::display($return,$listing,$params,$itemId);
	}
	
	/**
	 * Affichage pour le contexte de comparaison de versions.
	 *
	 * Correctifs par rapport à display() :
	 *  1. URL absolue (évite le chemin relatif ./ cassé depuis la popup admin)
	 *  2. ID de modal unique par colonne (v1/v2) pour éviter la collision
	 *  3. Dimensions CSS (max-width/max-height) sans getimagesize() fragile
	 *  4. Badge "modifié" si les deux valeurs diffèrent
	 */
	function displayComparison($value, $otherValue = null, $side = 'v1')
	{
		if ($value === '' || $value === null) {
			return false;
		}
 
		$parts   = explode('|', (string) $value);
		$rawPath = trim($parts[0]);
 
		if ($rawPath === '') {
			return false;
		}
 
		// URL absolue : corrige le chemin relatif depuis la popup en espace admin
		$imageUrl = \Joomla\CMS\Uri\Uri::root() . ltrim($rawPath, '/');
 
		$imageDesc  = htmlspecialchars($this->_field->attribs['imgdesc']  ?? '');
		$imageTitle = htmlspecialchars($this->_field->attribs['imgtitle'] ?? '');
		$maxW       = (int) ($this->_field->attribs['maxwidthlisting']  ?? 120);
		$maxH       = (int) ($this->_field->attribs['maxheightlisting'] ?? 90);
 
		// ID unique par colonne (side) + champ pour éviter la collision des deux modales
		$modalId = 'compare-img-' . htmlspecialchars($side) . '-' . (int) $this->_field->id;
 
		$changed = ($otherValue !== null && (string)$value !== (string)$otherValue);
 
		// Vignette cliquable
		$html  = '<div class="compare-img-wrapper" style="position:relative;display:inline-block;">';
		$html .= '<img src="' . htmlspecialchars($imageUrl) . '"'
		       . ' alt="' . $imageDesc . '" title="' . $imageTitle . '"'
		       . ' style="max-width:' . $maxW . 'px;max-height:' . $maxH . 'px;'
		       . 'object-fit:contain;cursor:pointer;border:1px solid #dee2e6;border-radius:4px;"'
		       . ' data-bs-toggle="modal" data-bs-target="#' . $modalId . '"'
		       . ' />';
		if ($changed) {
			$html .= '<span style="position:absolute;top:4px;right:4px;font-size:.65rem;'
			       . 'background:#fd7e14;color:#fff;border-radius:3px;padding:1px 5px;font-weight:600;">'
			       . \Joomla\CMS\Language\Text::_('COM_COLLECTOR_ITEM_HISTORY_COMPARE_CHANGED')
			       . '</span>';
		}
		$html .= '</div>';
 
		// Modale plein format Bootstrap 5
		$html .= '<div id="' . $modalId . '" class="modal fade" tabindex="-1" aria-hidden="true">'
		       . '<div class="modal-dialog modal-lg modal-dialog-centered">'
		       . '<div class="modal-content">'
		       . '<div class="modal-header">'
		       . '<button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>'
		       . '</div>'
		       . '<div class="modal-body text-center">'
		       . '<img src="' . htmlspecialchars($imageUrl) . '" alt="' . $imageDesc . '" class="img-fluid" />'
		       . '</div></div></div></div>';
 
		return $html;
	}
	
	/**
	 * Method to display value in fulltitle
	 *
	 * Can be overloaded/supplemented by the child class
	 *
	 * @param	string		$value	Field value
	 */
	function getImportedValue($value, array $mapping = [])
	{	
		return $value;
	}
	
	public function normalizeForComparison(string $value): string
	{
		// "||||||" (uniquement des pipes) est équivalent à vide
		$stripped = str_replace('|', '', $value);
		return $stripped === '' ? '' : $value;
	}
}

