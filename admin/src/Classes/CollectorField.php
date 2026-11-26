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

namespace Steevo\Component\Collector\Administrator\Classes;
// Check to ensure this file is included in Joomla!
defined('_JEXEC') or die( 'Restricted access' );

use Joomla\CMS\Application\CMSApplication;
use Joomla\CMS\Factory;
use Joomla\Filesystem\File;
use Joomla\Filesystem\Path;
use Joomla\CMS\Form\Field\RadioField;
use Joomla\CMS\Form\Form;
use Joomla\Form\Formfield;
use Joomla\Service\Provider\Database;
use Joomla\Table\Table;

use Joomla\Utilities\ArrayHelper;

use Steevo\Component\Collector\Administrator\Classes\Field\Date\CollectorFieldDate;
use Steevo\Component\Collector\Administrator\Classes\Field\Define\CollectorFieldDefine;
use Steevo\Component\Collector\Administrator\Classes\Field\File\CollectorFieldFile;
use Steevo\Component\Collector\Administrator\Classes\Field\Image\CollectorFieldImage;
use Steevo\Component\Collector\Administrator\Classes\Field\Item\CollectorFieldItem;
use Steevo\Component\Collector\Administrator\Classes\Field\Line\CollectorFieldLine;
use Steevo\Component\Collector\Administrator\Classes\Field\Number\CollectorFieldNumber;
use Steevo\Component\Collector\Administrator\Classes\Field\Text\CollectorFieldText;

use SimpleXMLElement;
use Joomla\Database\DatabaseInterface;

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
	 * make link
	 * 
	 * @var boolean
	 */
	public $makeLink = true;
	
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
			$this->_field = Table::getInstance('Collector_fields_type','Table');
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
		$application = Factory::getApplication();
		
		if (!is_object ($field))
		{
			$fieldType = Table::getInstance('Collector_fields_type','Table');
			$fieldType->load($field);
		
		}
		else
		{
			$fieldType = $field;
		}

		$fieldClass = 'Steevo\Component\Collector\Administrator\Classes\Field\\'.ucfirst($fieldType->type).'\CollectorField'.ucfirst($fieldType->type);

		/*if (!class_exists( $fieldClass ))
		{
			$path = JPATH_ROOT.'/administrator/components/com_collector/src/Classes/Field/'.ucfirst(strtolower($fieldType->type)).'/CollectorField'.ucfirst(strtolower($fieldType->type)).'.php';
			if( File::exists($path) )
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
		}*/

		$instance = new $fieldClass($collection,$field,$item);

		// Collection du champ : certaines vues chargent les champs sans cette colonne (comparaison des
		// versions), alors que les types de champs s'en servent (liens de filtre, requêtes).
		// Ici plutôt que dans le constructeur : chaque type de champ a le sien.
		if (is_object($instance->_field) && empty($instance->_field->collection))
		{
			$instance->_field->collection = (int) $collection;
		}

		// load language
		$extension = 'com_collector';		
		$basePath = JPATH_ADMINISTRATOR . '/components/com_collector/src/Classes/Field/' . ucfirst(strtolower($fieldType->type));
		
		$language = Factory::getApplication()->getLanguage();
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
			// 'type'			=> "Steevo\\Component\\Collector\\Administrator\\Classes\\Field\\".ucfirst($this->type)."\\".ucfirst($this->type),
			'type'			=> 'Collector'.ucfirst($this->type),
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
		$db = Factory::getContainer()->get(DatabaseInterface::class);
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
		$path = Path::find(JPATH_ROOT.'/administrator/components/com_collector/src/Classes/Field/'.ucfirst(strtolower($this->type)), 'field.xml');
		$fieldXML = simplexml_load_file($path);
		
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
	function display($value,$listing=true,$params=array(),$itemId=0)
	{
		$return = '';
		
		if ($value != '') {
			$return = $value;
		}
		
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
	function getImportedValue($value, array $mapping = [])
	{
		return $value;
	}

	/**
	 * Valeur importée d'une cellule au format date du tableur.
	 *
	 * Le tableur stocke un nombre (37682) qu'il affiche en date (02/03/2003). Par défaut le
	 * champ prend le nombre saisi : une référence dans une cellule mise en date par erreur.
	 * Un champ de type date prend la date.
	 *
	 * Can be overloaded/supplemented by the child class
	 *
	 * @param	string	$date		Date de la cellule (« 2003-03-02 00:00:00 »)
	 * @param	string	$number		Nombre contenu dans la cellule (« 37682 »)
	 * @param	array	$mapping	Correspondances de valeurs du champ
	 */
	function getImportedSpreadsheetDate(string $date, string $number, array $mapping = [])
	{
		return $this->getImportedValue($number, $mapping);
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
		
		$fieldXML = new SimpleXMLElement($xml);
		
		$field = new RadioField;
		$field->setup($fieldXML,$value,'jform[params][required]');
		
		$html[] = $field->renderField();

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
		if ($params && ($params->get('filter'))) {
			$filter = $params->get('filter');
			if (is_array($filter)) {
				$filter = ArrayHelper::toObject($filter);
			}
			$this->valueFilterMenu = '';
			
			$nameFilterCollection = 'filterfield_'.$this->_field->tablecolumn;
			if (isset($filter->$nameFilterCollection)) {
				if ($filter->$nameFilterCollection != '') {
					$this->valueFilterMenu = $filter->$nameFilterCollection;
				}
			}
		} else {
			return false;
		}
		
		if ($this->valueFilterMenu == '') {
			return false;
		} else {
			return true;
		}
		return false;
	}
	
	/**
	 * Affichage pour le contexte de comparaison de versions.
	 *
	 * Comportement par défaut : délègue à display() sans mode listing.
	 * Les sous-classes surchargent cette méthode pour ajouter leurs décorateurs
	 * de diff (mot à mot, flèche, vignette…) sans que la vue n'ait à connaître
	 * le type du champ.
	 *
	 * @param  mixed       $value       Valeur de la version courante (résolue par setQuery)
	 * @param  mixed|null  $otherValue  Valeur de l'autre version, null si hors contexte diff
	 * @param  string      $side        'v1' (version A) ou 'v2' (version B)
	 * @return string|false  HTML prêt à l'emploi, false si valeur vide
	 */
	function displayComparison($value, $otherValue = null, $side = 'v1')
	{
		$params = new \Joomla\Registry\Registry();
		$params['compare_view'] = true;
		return $this->display($value, false, $params);
	}
 
	/**
	 * Diff mot à mot entre deux chaînes.
	 *
	 * Colonne A (v1) : les tokens absents de B sont enveloppés en <del>.
	 * Colonne B (v2) : les tokens absents de A sont enveloppés en <ins>.
	 *
	 * Les chaînes doivent être pré-échappées (htmlspecialchars) avant l'appel
	 * pour que les balises <del>/<ins> soient du HTML propre.
	 */
	protected static function wordDiffHtml(string $current, string $other, string $side): string
	{
		$tokA = preg_split('/(\s+)/', $current, -1, PREG_SPLIT_DELIM_CAPTURE) ?: [];
		$tokB = preg_split('/(\s+)/', $other,   -1, PREG_SPLIT_DELIM_CAPTURE) ?: [];
 
		$diff = self::computeWordDiff($tokA, $tokB);
		$html = '';
 
		foreach ($diff as [$op, $token]) {
			if ($op === 'keep') {
				$html .= $token;
			} elseif ($op === 'del') {
				// 'del' = présent dans $current (ce côté-ci), absent de $other.
				// En v1 : le token existait en v1 mais a disparu en v2 → <del>.
				// En v2 : $current est v2, donc 'del' signifie présent en v2 mais
				//          absent de v1 → c'est un ajout dans v2 → <ins>.
				$html .= $side === 'v1'
					? '<del>' . $token . '</del>'
					: '<ins>' . $token . '</ins>';
			}
			// 'ins' = présent dans $other, absent de $current → ne jamais afficher
			// dans cette colonne (ce token appartient à l'autre version).
		}
 
		return $html ?: $current;
	}
 
	/**
	 * Algorithme LCS → tableau de [opération, token], opération ∈ {keep, del, ins}.
	 * Limité à 200 tokens pour rester en O(n²) raisonnable sur de longs textes.
	 */
	protected static function computeWordDiff(array $a, array $b): array
	{
		$a = array_slice($a, 0, 200);
		$b = array_slice($b, 0, 200);
		$m = count($a);
		$n = count($b);
 
		$lcs = array_fill(0, $m + 1, array_fill(0, $n + 1, 0));
		for ($i = 1; $i <= $m; $i++) {
			for ($j = 1; $j <= $n; $j++) {
				$lcs[$i][$j] = ($a[$i - 1] === $b[$j - 1])
					? $lcs[$i - 1][$j - 1] + 1
					: max($lcs[$i - 1][$j], $lcs[$i][$j - 1]);
			}
		}
 
		$result = [];
		$i = $m;
		$j = $n;
		while ($i > 0 || $j > 0) {
			if ($i > 0 && $j > 0 && $a[$i - 1] === $b[$j - 1]) {
				array_unshift($result, ['keep', $a[$i - 1]]);
				$i--;
				$j--;
			} elseif ($j > 0 && ($i === 0 || $lcs[$i][$j - 1] >= $lcs[$i - 1][$j])) {
				array_unshift($result, ['ins', $b[$j - 1]]);
				$j--;
			} else {
				array_unshift($result, ['del', $a[$i - 1]]);
				$i--;
			}
		}
 
		return $result;
	}
	
	/**
	 * Retourne la configuration de mapping d'import pour ce type de champ.
	 * Retourne null pour les types simples (aucun mapping spécifique nécessaire).
	 * Surchargé dans CollectorFieldDefine pour retourner les options de la liste.
	 *
	 * Format retourné (si non null) :
	 *   ['type'    => 'define',
	 *    'col'     => 'tablecolumn',
	 *    'options' => [['id'=>1,'content'=>'Foo'], ...]]
	 *
	 * @return array|null
	 */
	public function getImportMapping(): ?array
	{
		return null;
	}
	
	/**
	 * Retourne le JS utilitaire nécessaire au rendu de renderImportMapping().
	 * La classe de base retourne une chaîne vide.
	 * Chaque type surcharge si besoin.
	 */
	public function getImportMappingJs(): string
	{
		return '';
	}
	
	/**
	 * Retourne le HTML du bloc de mapping pour l'étape 3 de l'import.
	 * La classe de base retourne une chaîne vide (pas de mapping nécessaire).
	 * Chaque type de champ surcharge cette méthode pour rendre son propre UI.
	 *
	 * @param  array  $savedCol    Mapping sauvegardé pour cette colonne
	 * @param  array  $fileValues  Valeurs uniques trouvées dans le fichier
	 * @return string              HTML du corps de la carte (sans wrapper card)
	 */
	public function renderImportMapping(array $savedCol, array $fileValues): string
	{
		return '';
	}

	/**
	 * Fusionne le mapping d'une colonne (formulaire de l'étape 3, ou session pendant l'import)
	 * avec celui de la configuration enregistrée, pour ne pas perdre ce qui y est déjà résolu.
	 * La classe de base garde le mapping tel quel. Chaque type surcharge si besoin.
	 *
	 * @param  array  $current   Mapping de la colonne à enregistrer
	 * @param  array  $existing  Mapping de la colonne dans la configuration enregistrée
	 * @param  bool   $fromForm  true : choix faits à l'étape 3
	 * @return array             Mapping de la colonne à enregistrer
	 */
	public function mergeImportMapping(array $current, array $existing, bool $fromForm): array
	{
		return $current;
	}
	
	/**
	 * Appelée après import pour mettre à jour le mapping avec les valeurs
	 * créées en base lors de cet import.
	 * Retourne le tableau $colMapping mis à jour, ou null si pas de changement.
	 */
	public function refreshImportMapping(array $colMapping): ?array
	{
		return null; // pas de mise à jour par défaut
	}
	
	/**
	 * Retourne la condition SQL WHERE pour la recherche de doublons.
	 * La classe de base fait une égalité stricte.
	 */
	public function getSearchCondition(string $tableAlias, string $value, object $db): string
	{
		$col = $db->quoteName($tableAlias . '.' . $this->_field->tablecolumn);

		// La base peut contenir le texte avec des codes HTML (« Maitre &amp; 1 », « &eacute; »)
		$plain    = html_entity_decode($value, ENT_QUOTES | ENT_HTML5, 'UTF-8');
		$variants = array_unique([
			$value,
			$plain,
			htmlspecialchars($plain, ENT_QUOTES, 'UTF-8'),
			htmlentities($plain, ENT_QUOTES | ENT_HTML401, 'UTF-8'),
		]);

		return $col . ' IN (' . implode(', ', array_map([$db, 'quote'], $variants)) . ')';
	}
	
	/**
	 * Normalise une valeur pour la comparaison dans isIdentical().
	 * La classe de base retourne la valeur telle quelle.
	 * Les types qui stockent dans un format spécifique surchargent cette méthode.
	 */
	public function normalizeForComparison(string $value): string
	{
		// « &amp; » et « & », « &eacute; » et « é » : même texte
		return trim(html_entity_decode($value, ENT_QUOTES | ENT_HTML5, 'UTF-8'));
	}
	
	// ── Import : valeurs créées, recherche et ressemblance ─────────────────────
	// Chaque type de champ adapte ces règles en redéfinissant les méthodes ci-dessous :
	// le cœur de l'import (contrôleur, modèle, ImportSimilarity) n'en connaît aucun.

	/** Préfixe d'une valeur « à créer » renvoyée pendant une simulation */
	public const NEW_PREFIX = 'new:';

	/** Simulation en cours : un type de champ qui crée des valeurs à l'import n'en crée aucune */
	public static bool $deferCreation = false;

	/** @var array<int, array{defined:int, id:int, content:string}> Valeurs créées par l'import depuis le dernier relevé */
	public static array $createdValues = [];

	/** Valeur absente (rien de renseigné) */
	public function isEmptyValue(string $value): bool
	{
		$value = trim($value);
		return $value === '' || $value === '0';
	}

	/**
	 * Condition SQL « valeur incomplète en base » pour la recherche de doublons de repli :
	 * l'objet reste un candidat, à faire vérifier. Chaîne vide : aucune valeur de ce type
	 * n'est considérée comme incomplète.
	 */
	public function getUnknownValueCondition(string $tableAlias, object $db): string
	{
		return '';
	}

	/** Poids du champ dans la note de ressemblance (une référence compte double) */
	public function getSimilarityWeight(): float
	{
		return 2.0;
	}

	/**
	 * Ressemblance (0 à 1) entre la valeur importée et la valeur en base, renseignée.
	 * Par défaut, comparaison de références (RAC 042 / RAC042, K*** / K69).
	 */
	public function similarityScore(string $importValue, string $baseValue): float
	{
		return ImportSimilarity::refScore($importValue, $baseValue);
	}

	/**
	 * La valeur importée peut-elle servir de « champ suffisant » ? Par défaut, un texte
	 * d'au moins 3 lettres ou chiffres (« 6 », « 51 », « ** » n'identifient rien).
	 */
	public function isSimilarityKey(string $value): bool
	{
		return !$this->isEmptyValue($value) && !str_starts_with($value, self::NEW_PREFIX)
			&& strlen(ImportSimilarity::normalize($value)) >= 3;
	}

	/** « Champ suffisant » identique entre la valeur importée et la valeur en base */
	public function isSimilarityKeyEqual(string $importValue, string $baseValue): bool
	{
		$a = ImportSimilarity::normalize($importValue);
		return $a !== '' && $a === ImportSimilarity::normalize($baseValue);
	}

	/**
	 * Condition SQL : valeur en base contenant un joker (« ** », « K*** », « ? ») ;
	 * à utiliser par les types texte dans getUnknownValueCondition().
	 */
	protected function jokerCondition(string $tableAlias, object $db): string
	{
		$col = $db->quoteName($tableAlias . '.' . $this->_field->tablecolumn);
		return '(' . $col . ' LIKE ' . $db->quote('%*%') . ' OR ' . $col . ' LIKE ' . $db->quote('%?%') . ')';
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