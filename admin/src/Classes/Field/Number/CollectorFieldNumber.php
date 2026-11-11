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
use Joomla\CMS\Router\Route;
use Joomla\Database\DatabaseInterface;

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
	 * Attributs du champ avec leurs valeurs par défaut (anciens champs sans attribs complets).
	 */
	protected function attrib(string $name, $default)
	{
		$value = $this->_field->attribs[$name] ?? '';

		return ($value === '' || $value === null) ? $default : $value;
	}

	/**
	 * Mode de recherche : 0 = zones de texte « de X à Y », 1 = curseur double.
	 */
	protected function isSliderMode(): bool
	{
		return (int) $this->attrib('search_method', 0) === 1;
	}

	/**
	 * Convertit une borne saisie en nombre, ou chaîne vide si ce n'est pas un nombre.
	 */
	protected static function cleanBound($value): string
	{
		$value = str_replace(',', '.', trim((string) $value));

		return is_numeric($value) ? (string) (0 + $value) : '';
	}

	/**
	 * Découpe une valeur de filtre « min|max » en deux bornes nettoyées.
	 */
	protected static function splitRange($value): array
	{
		$parts = explode('|', (string) $value, 2);

		return [self::cleanBound($parts[0] ?? ''), self::cleanBound($parts[1] ?? '')];
	}

	/**
	 * Un filtre de menu « | » (aucune borne) n'impose rien : le champ reste proposé dans la recherche.
	 */
	function isFiltered($params)
	{
		$this->valueFilterMenu = $this->menuFilterValue($params);

		return $this->valueFilterMenu !== '';
	}

	/**
	 * Filtre « min|max » enregistré dans l'élément de menu, ou chaîne vide s'il n'impose rien.
	 *
	 * Le menu enregistre aussi les deux bornes séparément (filterfield_x_min / _max) : elles servent
	 * de secours quand la valeur combinée n'a pas été mise à jour avant l'enregistrement.
	 */
	protected function menuFilterValue($params): string
	{
		$filter = $params ? $params->get('filter') : null;

		if (!$filter || (!\is_array($filter) && !\is_object($filter))) {
			return '';
		}

		$filter = (array) $filter;
		$name   = 'filterfield_' . $this->_field->tablecolumn;

		[$min, $max] = self::splitRange($filter[$name] ?? '');

		if ($min === '') {
			$min = self::cleanBound($filter[$name . '_min'] ?? '');
		}
		if ($max === '') {
			$max = self::cleanBound($filter[$name . '_max'] ?? '');
		}

		return ($min === '' && $max === '') ? '' : $min . '|' . $max;
	}

	/**
	 * Bornes imposées par le filtre de l'élément de menu, s'il y en a un.
	 */
	protected function menuRange($params): array
	{
		return $this->isFiltered($params) ? self::splitRange($this->valueFilterMenu) : ['', ''];
	}

	/**
	 * Gets the action to do on submit
	 *
	 * @return string
	 */
	function resetSearchArea($params)
	{
		$col = $this->_field->tablecolumn;
		[$menuMin, $menuMax] = $this->menuRange($params);

		if ($this->isSliderMode()) {
			[$minValued, $maxValued] = $this->getMinMaxValued();
			$min = $menuMin !== '' ? $menuMin : ($minValued === null ? '' : (string) (0 + $minValued));
			$max = $menuMax !== '' ? $menuMax : ($maxValued === null ? '' : (string) (0 + $maxValued));
		} else {
			$min = $menuMin;
			$max = $menuMax;
		}

		return 'CollectorNumberFilter.reset(' . json_encode($col) . ',' . json_encode($min) . ',' . json_encode($max) . ');';
	}

	/**
	 * Gets the action to do on submit search
	 *
	 * @return string
	 */
	function submitSearchArea()
	{
		if (!$this->_field->filter) {
			return '';
		}

		return 'if (window.CollectorNumberFilter) { CollectorNumberFilter.submit(' . json_encode($this->_field->tablecolumn) . '); }';
	}

	/**
	 * Gets the javascript for menu creation
	 *
	 * @return string
	 */
	function getMenuJs()
	{
		[$minValue, $maxValue] = $this->getMinMaxValued();
		$col = $this->_field->tablecolumn;

		// Une fonction par champ : avec un nom commun, le dernier champ nombre écrasait les autres.
		return "
		window['populateHidden_" . $col . "'] = function() {
			var lo = " . json_encode($minValue === null ? null : (float) $minValue) . ", hi = " . json_encode($maxValue === null ? null : (float) $maxValue) . ";
			var a = document.getElementById('filterfield_" . $col . "_min'), b = document.getElementById('filterfield_" . $col . "_max');
			var min = a.value.trim().replace(',', '.'), max = b.value.trim().replace(',', '.');
			if (min !== '' && isNaN(parseFloat(min))) { min = ''; }
			if (max !== '' && isNaN(parseFloat(max))) { max = ''; }
			if (min !== '' && lo !== null && parseFloat(min) < lo) { min = lo; }
			if (max !== '' && hi !== null && parseFloat(max) > hi) { max = hi; }
			a.value = min; b.value = max;
			document.getElementById('filterfield_" . $col . "').value = min + '|' + max;
		};";
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
		);

		return parent::getFieldAttributes($attributes);
	}

	/**
	 * Method to add where clause to query on filter value
	 *
	 * @param	\Joomla\Database\QueryInterface	$query
	 * @param	mixed							$value	« min|max »
	 * @param	\Joomla\Registry\Registry		$params
	 */
	function setFilterWhereClause(&$query,$value,$params)
	{
		[$min, $max]         = self::splitRange($value);
		[$menuMin, $menuMax] = $this->menuRange($params);

		if ($min === '') {
			$min = $menuMin;
		}
		if ($max === '') {
			$max = $menuMax;
		}

		if ($min === '' && $max === '') {
			return false;
		}

		$column = 'CAST(h.' . $this->_field->tablecolumn . ' AS DECIMAL(18,5))';

		if ($min !== '') {
			$query->where($column . ' >= ' . (float) $min);
		}
		if ($max !== '') {
			$query->where($column . ' <= ' . (float) $max);
		}
		$query->where('h.' . $this->_field->tablecolumn . ' != ' . $query->quote(''));

		return true;
	}

	/**
	 * Method to add order by clause
	 */
	function getOrderBy()
	{
		return 'CAST(h.'.$this->_field->tablecolumn.' AS DECIMAL(18,5))';
	}

	/**
	 * Method to get the min and max values
	 */
	function getMinMaxValued()
	{
		if ($this->_minValue === '')
		{
			$decimal = max(0, min(10, (int) $this->attrib('decimal', 0)));
			$column  = 'CAST(h.' . $this->_field->tablecolumn . ' AS DECIMAL(18,' . $decimal . '))';

			$db = Factory::getContainer()->get(DatabaseInterface::class);
			$query = $db->getQuery(true)
				->select('MIN(' . $column . ') AS mini, MAX(' . $column . ') AS maxi')
				->from('#__collector_items_history_' . (int) $this->_collection . ' AS h')
				->where('h.state = 1')
				->where('h.' . $this->_field->tablecolumn . ' != ' . $db->quote(''));
			$row = $db->setQuery($query)->loadObject();

			$this->_minValue = $row ? $row->mini : null;
			$this->_maxValue = $row ? $row->maxi : null;
		}

		return array($this->_minValue,$this->_maxValue);
	}

	/**
	 * Script et styles partagés par tous les filtres numériques de la page (chargés une fois).
	 */
	protected static function addFilterAssets(): void
	{
		static $done = false;

		if ($done) {
			return;
		}
		$done = true;

		$wa = Factory::getApplication()->getDocument()->getWebAssetManager();

		$wa->addInlineStyle('
.collector-number-filter{display:inline-flex;flex-wrap:wrap;align-items:center;gap:.25rem .5rem}
.collector-number-filter input.cnf-num{width:6.5em}
.collector-number-filter input.cnf-readonly{text-align:center;padding-left:.25rem;padding-right:.25rem;background-color:transparent}
.collector-number-filter .cnf-range{position:relative;height:1.75rem;min-width:9rem;flex:1 1 9rem}
.collector-number-filter .cnf-range::before{content:"";position:absolute;left:0;right:0;top:50%;height:.3rem;margin-top:-.15rem;border-radius:.15rem;background:var(--collector-border,#ccc)}
.collector-number-filter .cnf-fill{position:absolute;top:50%;height:.3rem;margin-top:-.15rem;border-radius:.15rem;background:var(--collector-accent,#c62144)}
.collector-number-filter .cnf-range input[type=range]{position:absolute;left:0;top:0;width:100%;height:100%;margin:0;background:none;pointer-events:none;-webkit-appearance:none;appearance:none}
.collector-number-filter .cnf-range input[type=range]::-webkit-slider-runnable-track{background:none}
.collector-number-filter .cnf-range input[type=range]::-moz-range-track{background:none}
.collector-number-filter .cnf-range input[type=range]::-webkit-slider-thumb{pointer-events:auto;-webkit-appearance:none;appearance:none;width:1.1rem;height:1.1rem;border-radius:50%;background:#fff;border:2px solid var(--collector-accent,#c62144);cursor:pointer}
.collector-number-filter .cnf-range input[type=range]::-moz-range-thumb{pointer-events:auto;width:1rem;height:1rem;border-radius:50%;background:#fff;border:2px solid var(--collector-accent,#c62144);cursor:pointer}
');

		$wa->addInlineScript(<<<'JS'
window.CollectorNumberFilter = (function () {
	function els(col) {
		return {
			box: document.querySelector('.collector-number-filter[data-column="' + col + '"]'),
			min: document.getElementById('filterfield_' + col + '_min'),
			max: document.getElementById('filterfield_' + col + '_max'),
			hidden: document.getElementById('filterfield_' + col)
		};
	}
	function paint(e) {
		if (!e.box || !e.box.dataset.cnfMode) { return; }
		var lo = e.box.querySelector('.cnf-lo'), hi = e.box.querySelector('.cnf-hi'), fill = e.box.querySelector('.cnf-fill');
		var a = parseFloat(lo.min), b = parseFloat(lo.max), span = (b - a) || 1;
		fill.style.left = ((parseFloat(lo.value) - a) / span * 100) + '%';
		fill.style.right = ((b - parseFloat(hi.value)) / span * 100) + '%';
	}
	function fromRange(col, which) {
		var e = els(col), lo = e.box.querySelector('.cnf-lo'), hi = e.box.querySelector('.cnf-hi');
		if (parseFloat(lo.value) > parseFloat(hi.value)) {
			if (which === 'lo') { lo.value = hi.value; } else { hi.value = lo.value; }
		}
		e.min.value = lo.value;
		e.max.value = hi.value;
		paint(e);
	}
	function fromText(col) {
		var e = els(col);
		if (!e.box) { return; }
		if (!e.box.dataset.cnfMode) {
			// Zones de texte : valeurs ramenées entre le minimum et le maximum de la collection.
			var a = parseFloat(e.box.dataset.lo), b = parseFloat(e.box.dataset.hi);
			[e.min, e.max].forEach(function (input) {
				if (input.value === '' || isNaN(parseFloat(input.value))) { input.value = ''; return; }
				var v = parseFloat(input.value);
				if (!isNaN(a) && v < a) { v = a; }
				if (!isNaN(b) && v > b) { v = b; }
				input.value = v;
			});
			if (e.min.value !== '' && e.max.value !== '' && parseFloat(e.min.value) > parseFloat(e.max.value)) {
				var t = e.min.value; e.min.value = e.max.value; e.max.value = t;
			}
			return;
		}
		var lo = e.box.querySelector('.cnf-lo'), hi = e.box.querySelector('.cnf-hi');
		if (e.min.value !== '') { lo.value = e.min.value; e.min.value = lo.value; }
		if (e.max.value !== '') { hi.value = e.max.value; e.max.value = hi.value; }
		if (parseFloat(lo.value) > parseFloat(hi.value)) { lo.value = hi.value; e.min.value = lo.value; }
		paint(e);
	}
	function submit(col) {
		var e = els(col);
		if (!e.hidden || !e.min || !e.max) { return; }
		if (e.box && !e.box.dataset.cnfMode && !e.min.readOnly) { fromText(col); }
		var min = e.min.value.trim(), max = e.max.value.trim();
		if (e.box && e.box.dataset.cnfMode) {
			// Curseur laissé aux extrémités : pas de filtre (les objets sans valeur restent visibles).
			if (min === e.box.dataset.lo) { min = ''; }
			if (max === e.box.dataset.hi) { max = ''; }
		}
		e.hidden.value = min + '|' + max;
	}
	function reset(col, min, max) {
		var e = els(col);
		if (e.min) { e.min.value = min; }
		if (e.max) { e.max.value = max; }
		if (e.box && e.box.dataset.cnfMode) {
			e.box.querySelector('.cnf-lo').value = min;
			e.box.querySelector('.cnf-hi').value = max;
			paint(e);
		}
		if (e.hidden) { e.hidden.value = '|'; }
	}
	document.addEventListener('DOMContentLoaded', function () {
		document.querySelectorAll('.collector-number-filter[data-cnf-mode]').forEach(function (box) {
			paint(els(box.dataset.column));
		});
	});
	return { fromRange: fromRange, fromText: fromText, submit: submit, reset: reset };
})();
JS);
	}

	/**
	 * Method to display filter in search area
	 *
	 * @param	\Joomla\Registry\Registry	$params
	 * @param	mixed						$value	« min|max »
	 * @param	boolean						$menu	true dans le formulaire de l'élément de menu
	 */
	function displayFilter($params,$value,$menu=false)
	{
		$col  = $this->_field->tablecolumn;
		$esc  = fn ($v) => htmlspecialchars((string) $v, ENT_QUOTES, 'UTF-8');
		[$minValue, $maxValue] = $this->getMinMaxValued();
		// 0 seul = « pas de valeur » (valeur par défaut transmise par le formulaire de menu), pas une borne.
		if ($value === 0 || $value === '0' || $value === null) {
			$value = '';
		}
		[$min, $max]           = self::splitRange($value);
		$value                 = $min . '|' . $max;

		// Formulaire de l'élément de menu : deux zones de texte qui définissent le filtre imposé.
		if ($menu) {
			$name = fn ($suffix) => 'jform[params][filter][filterfield_' . $col . $suffix . ']';

			// Valeur déjà enregistrée, y compris par les bornes séparées.
			if ($min === '' && $max === '') {
				[$min, $max] = self::splitRange($this->menuFilterValue($params));
				$value       = $min . '|' . $max;
			}

			// Mise à jour de la valeur combinée sans dépendre du script chargé à part (getMenuJs).
			$sync = $esc("var d=document,a=d.getElementById('filterfield_" . $col . "_min'),b=d.getElementById('filterfield_" . $col . "_max');"
				. "if(window['populateHidden_" . $col . "']){window['populateHidden_" . $col . "']();}"
				. "else{d.getElementById('filterfield_" . $col . "').value=a.value.trim()+'|'+b.value.trim();}");

			return '<td>' . $esc($this->_field->field) . '</td><td>'
				. Text::_('COM_COLLECTOR_NUMBER_FIELD_SEARCH_FROM')
				. '<input type="text" class="form-control d-inline-block" style="width:6em" onchange="' . $sync . '" id="filterfield_' . $esc($col) . '_min" name="' . $esc($name('_min')) . '" value="' . $esc($min) . '" placeholder="' . $esc($minValue) . '">'
				. Text::_('COM_COLLECTOR_NUMBER_FIELD_SEARCH_TO')
				. '<input type="text" class="form-control d-inline-block" style="width:6em" onchange="' . $sync . '" id="filterfield_' . $esc($col) . '_max" name="' . $esc($name('_max')) . '" value="' . $esc($max) . '" placeholder="' . $esc($maxValue) . '">'
				. '<input type="hidden" id="filterfield_' . $esc($col) . '" name="' . $esc($name('')) . '" value="' . $esc($value) . '">'
				. '</td>';
		}

		$filtered = $this->isFiltered($params);

		// Filtre imposé par le menu et masqué : rien à afficher.
		if ($filtered && $params->get('hide_filter')) {
			return '<input type="hidden" id="filterfield_' . $esc($col) . '" name="filterfield_' . $esc($col) . '" value="' . $esc($value) . '">';
		}

		// Aucune valeur dans la collection : pas de filtre utile.
		if ($minValue === null || $maxValue === null) {
			return '';
		}

		self::addFilterAssets();

		[$menuMin, $menuMax] = $this->menuRange($params);
		if ($min === '') {
			$min = $menuMin;
		}
		if ($max === '') {
			$max = $menuMax;
		}

		$required = '';
		if (!$params->get('show_entire_listing')) {
			$requiredParams = (array) $params->get('required');
			if (!empty($requiredParams['filterfield_' . $col])) {
				$required = ' <span class="star">*</span>';
			}
		}

		$decimal = max(0, min(10, (int) $this->attrib('decimal', 0)));
		$label   = '<label for="filterfield_' . $esc($col) . '_min">' . $esc($this->_field->field) . $required . '</label>';
		$boxWidth = (int) $this->attrib('textboxwidth', 0);
		$boxStyle = $boxWidth > 0 ? ' style="width:' . max(50, $boxWidth) . 'px"' : '';
		// En lecture seule, une zone « number » garde la place des flèches masquées : on passe en texte.
		$input   = fn ($suffix, $v, $placeholder, $extra = '') => (str_contains($extra, 'readonly')
				? '<input type="text" class="form-control form-control-sm cnf-num cnf-readonly" tabindex="-1"'
				: '<input type="number" class="form-control form-control-sm cnf-num" step="any"') . $boxStyle
			. ' id="filterfield_' . $esc($col) . $suffix . '" name="filterfield_' . $esc($col) . $suffix . '"'
			. ' value="' . $esc($v) . '" placeholder="' . $esc($placeholder) . '"' . $extra . '>';
		$hidden  = '<input type="hidden" id="filterfield_' . $esc($col) . '" name="filterfield_' . $esc($col) . '" value="' . $esc($value) . '">';

		if (!$this->isSliderMode()) {
			$bounds = ' min="' . $esc(0 + $minValue) . '" max="' . $esc(0 + $maxValue) . '"'
				. ' onchange="CollectorNumberFilter.fromText(' . $esc(json_encode($col)) . ')"';

			return '<div class="collector-number-filter" data-column="' . $esc($col) . '" data-lo="' . $esc(0 + $minValue) . '" data-hi="' . $esc(0 + $maxValue) . '">'
				. $label . ' '
				. Text::_('COM_COLLECTOR_NUMBER_FIELD_SEARCH_FROM') . $input('_min', $min, $minValue, $bounds)
				. Text::_('COM_COLLECTOR_NUMBER_FIELD_SEARCH_TO') . $input('_max', $max, $maxValue, $bounds)
				. $hidden . '</div>';
		}

		// Curseur double : deux curseurs natifs superposés et les deux bornes en clair.
		$lo   = (string) (0 + $minValue);
		$hi   = (string) (0 + $maxValue);
		$step = (float) $this->attrib('sliderstep', 0) > 0 ? (string) (0 + $this->attrib('sliderstep', 1)) : (string) (10 ** -$decimal);
		$curMin = $min !== '' ? $min : $lo;
		$curMax = $max !== '' ? $max : $hi;
		$editable = (int) $this->attrib('textboxeditable', 1) === 1;
		$textExtra = $editable
			? ' onchange="CollectorNumberFilter.fromText(' . $esc(json_encode($col)) . ')"'
			: ' readonly';
		$range = fn ($cls, $v) => '<input type="range" class="' . $cls . '" min="' . $esc($lo) . '" max="' . $esc($hi) . '" step="' . $esc($step) . '" value="' . $esc($v) . '"'
			. ' oninput="CollectorNumberFilter.fromRange(' . $esc(json_encode($col)) . ',' . $esc(json_encode($cls === 'cnf-lo' ? 'lo' : 'hi')) . ')"'
			. ' aria-label="' . $esc($this->_field->field) . '">';
		$width = (int) $this->attrib('sliderwidth', 0);

		return '<div class="collector-number-filter" data-column="' . $esc($col) . '" data-cnf-mode="slider" data-lo="' . $esc($lo) . '" data-hi="' . $esc($hi) . '">'
			. $label . ' '
			. $input('_min', $curMin, $lo, $textExtra)
			. '<div class="cnf-range"' . ($width > 0 ? ' style="max-width:' . $width . 'px"' : '') . '><div class="cnf-fill"></div>'
			. $range('cnf-lo', $curMin) . $range('cnf-hi', $curMax) . '</div>'
			. $input('_max', $curMax, $hi, $textExtra)
			. $hidden . '</div>';
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
					if ( ($this->_field->attribs['show_fieldlink'] ?? 0) && ( $this->_field->filter == 1 ) )
					{
						$link= 'index.php?option=com_collector&view=collection&id='.$this->_field->collection.'&filterfield_'.$this->_field->tablecolumn.'='.$value.'|'.$value;
						$return = '<a href="'.Route::_($link).'">'.$return.'</a>';
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
