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

namespace Steevo\Component\Collector\Administrator\Classes\Field\Date;

use Steevo\Component\Collector\Administrator\Classes\CollectorField;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Language\Text;

defined('_JEXEC') or die;

/**
 * Field Date class
 *
 * @package	Collector
 */
class CollectorFieldDate extends CollectorField
{
	/**
	 * type
	 * 
	 * @var string
	 */
	public $type = 'date';
	
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
			'class'			=> "inputbox",
			'size'			=> "22",
			// 'format'		=> $this->_field->attribs['format'],
			// 'format'		=> '%Y-%m-%d %H:%M:%S',
			'format'		=> '%Y-%m-%d',
			'filter'		=> "user_utc"
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
		$format = $this->_field->attribs['format'];
		if ( $format == '' )
		{
			$format = '%Y-%m-%d';
		}
		if ($value != '') {
			$format = str_replace('%','',$format);
			if (\DateTime::createFromFormat('Y-m-d H:i:s', $value) === FALSE) {
				// it's not a datetime format
				$value = \DateTime::createFromFormat($format, $value)->format('Y-m-d H:i:s');
			}
			$return = HTMLHelper::_( 'date', $value, $format);
		}
		else
		{
			return false;
		}
		return parent::display($return,$listing,$params,$itemId);
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
		$format = $this->_field->attribs['format'];
		if ( $format == '' )
		{
			$format = '%Y-%m-%d';
		}
		if ($value != '') {
			$format = str_replace('%', '', $format);

			if (\DateTime::createFromFormat('Y-m-d H:i:s', $value) === false) {
				// Pas au format DB — tenter la conversion
				$dt = \DateTime::createFromFormat($format, $value);

				// Formats de secours si le format configuré échoue
				if ($dt === false) {
					foreach (['Y', 'Y-m-d', 'd/m/Y', 'd/m/Y H:i:s'] as $tryFmt) {
						$dt = \DateTime::createFromFormat($tryFmt, $value);
						if ($dt !== false) break;
					}
				}

				if ($dt === false) {
					// Valeur non parseable → afficher brut
					return htmlspecialchars($value, ENT_QUOTES);
				}

				$value = $dt->format('Y-m-d H:i:s');
			}

			return HTMLHelper::_('date', $value, $format);
		}
		
		return;
	}
	
	public function getImportMapping(): ?array
	{
		$joomlaFormat = ltrim($this->_field->attribs['format'] ?? '%Y-%m-%d', '%');
		return [
			'type'   => 'date',
			'col'    => $this->_field->tablecolumn,
			'label'  => $this->_field->field,
			'joomla' => $joomlaFormat,
		];
	}

	/**
	 * Convertit les années à 2 chiffres en 4 chiffres (règle POSIX)
	 * dans une valeur en se basant sur la position du token 'y' dans le format.
	 * Retourne [$newValue, $newFormat] avec 'y' remplacé par 'Y'.
	 */
	private function expand2DigitYear(string $value, string $format): array
	{
		// Construire un regex depuis le format pour localiser et capturer 'y'
		$tokenMap = [
			'd'=>'\d{2}',   'j'=>'\d{1,2}', 'D'=>'[A-Za-z]{3}', 'l'=>'[A-Za-z]+',
			'm'=>'\d{2}',   'n'=>'\d{1,2}', 'F'=>'[A-Za-z]+',   'M'=>'[A-Za-z]{3}',
			'Y'=>'\d{4}',   'y'=>'(\d{2})', // seul 'y' est un groupe capturant
			'H'=>'\d{2}',   'G'=>'\d{1,2}', 'h'=>'\d{2}',        'g'=>'\d{1,2}',
			'i'=>'\d{2}',   's'=>'\d{2}',   'A'=>'[AP]M',         'a'=>'[ap]m',
		];

		$pattern  = '/^';
		$groupIdx = 0;  // indice du groupe capturant 'y' dans les matches
		$tokenIdx = 0;

		for ($i = 0; $i < strlen($format); $i++) {
			$ch = $format[$i];
			if (isset($tokenMap[$ch])) {
				$pattern .= $tokenMap[$ch];
				if ($ch === 'y') {
					$groupIdx = $tokenIdx + 1;  // +1 car $matches[0] = full match
				}
				if ($tokenMap[$ch][0] === '(') {
					$tokenIdx++;
				}
			} else {
				$pattern .= preg_quote($ch, '/');
			}
		}
		$pattern .= '$/';

		if ($groupIdx > 0 && preg_match($pattern, trim($value), $matches) && isset($matches[$groupIdx])) {
			$yr       = (int)$matches[$groupIdx];
			$fullYear = ($yr >= 69 ? 1900 : 2000) + $yr;

			// Reconstruire la valeur en remplaçant l'année 2 chiffres par 4 chiffres
			$newValue  = '';
			$mIdx      = 1;
			for ($i = 0; $i < strlen($format); $i++) {
				$ch = $format[$i];
				if (!isset($tokenMap[$ch])) {
					$newValue .= $ch;
				} elseif ($ch === 'y') {
					$newValue .= str_pad($fullYear, 4, '0', STR_PAD_LEFT);
					$mIdx++;
				} elseif ($tokenMap[$ch][0] === '(') {
					$newValue .= $matches[$mIdx++];
				} else {
					// Retrouver la valeur en rematachant depuis la position
					// (les tokens non-capturants sont déjà dans la valeur tels quels)
					$newValue .= $matches[$mIdx] ?? '';
					$mIdx++;
				}
			}

			return [$newValue, str_replace('y', 'Y', $format)];
		}

		// Fallback : impossible à parser, on remplace juste 'y'→'Y' et on tente
		return [trim($value), str_replace('y', 'Y', $format)];
	}

	/**
	 * Method to display value in fulltitle
	 *
	 * Can be overloaded/supplemented by the child class
	 *
	 * @param	string		$value	Field value
	 */
	public function getImportedValue($value, array $mapping = [])
	{
		if (trim($value) === '') return '';

		$val          = trim($value);
		$sourceFormat = $mapping['format'] ?? null;

		if ($sourceFormat) {
			// Tenter le parsing avec le format fourni par l'utilisateur
			$dt = \DateTime::createFromFormat($sourceFormat, $val);

			if ($dt !== false) {
				// Si le format contient 'y' (année 2 chiffres), PHP peut produire
				// n'importe quel siècle selon la version. On corrige via la règle POSIX :
				// 69-99 → 19xx, 00-68 → 20xx
				if (strpos($sourceFormat, 'y') !== false) {
					$twoDigit   = (int) $dt->format('y'); // round-trip fiable quel que soit PHP
					$correctYear = ($twoDigit >= 69 ? 1900 : 2000) + $twoDigit;
					$dt->setDate(
						$correctYear,
						(int) $dt->format('m'),
						(int) $dt->format('d')
					);
				}

				// Normaliser les composantes absentes du format
				$hasDay   = (bool) preg_match('/[djDlNwz]/', $sourceFormat);
				$hasMonth = (bool) preg_match('/[mnFMN]/', $sourceFormat);
				if (!$hasDay && !$hasMonth) {
					$dt->setDate((int) $dt->format('Y'), 1, 1);
				} elseif (!$hasDay) {
					$dt->setDate((int) $dt->format('Y'), (int) $dt->format('m'), 1);
				}
				$dt->setTime(0, 0, 0);

				return $dt->format('Y-m-d H:i:s');
			}
		}

		// ── Fallback : format Joomla configuré sur le champ ──────────────────────
		$configuredFormat = ltrim($this->_field->attribs['format'] ?? '%Y-%m-%d', '%');
		if (\DateTime::createFromFormat('Y-m-d H:i:s', $val) !== false) {
			return $val; // déjà au format DB
		}
		$dt = \DateTime::createFromFormat($configuredFormat, $val);
		return $dt !== false ? $dt->format('Y-m-d H:i:s') : $val;
	}
	
	public function renderImportMapping(array $savedCol, array $fileValues): string
	{
		$col          = $this->_field->tablecolumn;
		$colEsc       = htmlspecialchars($col, ENT_QUOTES);
		$savedFormat  = $savedCol['format'] ?? '';
		$joomlaFormat = ltrim($this->_field->attribs['format'] ?? '%Y-%m-%d', '%');
		$sampleValues = array_slice(array_values(array_filter($fileValues, fn($v) => $v !== '')), 0, 5);

		$commonFormats = [
			''            => Text::_('COM_COLLECTOR_IMPORT_DATE_FORMAT_SELECT'),
			'd/m/Y'       => 'dd/mm/aaaa — ex: 25/12/2023',
			'd/m/Y H:i'   => 'dd/mm/aaaa hh:mm — ex: 25/12/2023 14:30',
			'd-m-Y'       => 'dd-mm-aaaa — ex: 25-12-2023',
			'm/d/Y'       => 'mm/dd/aaaa — ex: 12/25/2023',
			'Y-m-d'       => 'aaaa-mm-dd — ex: 2023-12-25',
			'Y-m-d H:i:s' => 'aaaa-mm-dd hh:mm:ss',
			'Y'           => 'aaaa — ex: 2023',
			'm/Y'         => 'mm/aaaa — ex: 12/2023',
			'y/m'         => 'aa/mm — ex: 04/12',
			'y'           => 'aa — ex: 88 (→ 1988), 04 (→ 2004)',
			'm/y'         => 'mm/aa — ex: 12/88',
			'd/m/y'       => 'dd/mm/aa — ex: 25/12/88',
		];

		$isCustom = ($savedFormat !== '' && !array_key_exists($savedFormat, $commonFormats));

		ob_start(); ?>

		<div class="row g-2 align-items-start">
		  <div class="col-md-6">
			<label class="form-label small fw-semibold">
			  <?php echo Text::_('COM_COLLECTOR_IMPORT_DATE_FORMAT_SOURCE'); ?>
			</label>
			<select class="form-select form-select-sm mb-2"
					onchange="syncDateFormat(this, '<?php echo $colEsc; ?>')">
			  <?php foreach ($commonFormats as $fmt => $lbl): ?>
			  <option value="<?php echo htmlspecialchars($fmt, ENT_QUOTES); ?>"
				<?php echo (!$isCustom && $savedFormat === $fmt) ? 'selected' : ''; ?>>
				<?php echo htmlspecialchars($lbl, ENT_QUOTES); ?>
			  </option>
			  <?php endforeach; ?>
			  <option value="__custom__" <?php echo $isCustom ? 'selected' : ''; ?>>
				<?php echo Text::_('COM_COLLECTOR_IMPORT_DATE_FORMAT_CUSTOM'); ?>
			  </option>
			</select>

			<input type="text"
				   id="date-fmt-<?php echo $colEsc; ?>"
				   class="form-control form-control-sm font-monospace"
				   placeholder="<?php echo Text::_('COM_COLLECTOR_IMPORT_DATE_FORMAT_PLACEHOLDER'); ?>"
				   value="<?php echo htmlspecialchars($savedFormat, ENT_QUOTES); ?>"
				   oninput="document.getElementById('date-fmt-hidden-<?php echo $colEsc; ?>').value=this.value">
			<input type="hidden"
				   id="date-fmt-hidden-<?php echo $colEsc; ?>"
				   name="valuemap[<?php echo $colEsc; ?>][format]"
				   value="<?php echo htmlspecialchars($savedFormat, ENT_QUOTES); ?>">

			<div class="form-text">
			  <?php echo Text::sprintf(
				  'COM_COLLECTOR_IMPORT_DATE_FORMAT_JOOMLA',
				  '<code>' . htmlspecialchars($joomlaFormat, ENT_QUOTES) . '</code>'
			  ); ?>
			</div>
		  </div>

		  <?php if (!empty($sampleValues)): ?>
		  <div class="col-md-6">
			<label class="form-label small fw-semibold">
			  <?php echo Text::_('COM_COLLECTOR_IMPORT_DATE_SAMPLES'); ?>
			</label>
			<ul class="list-unstyled mb-0">
			  <?php foreach ($sampleValues as $sv): ?>
			  <li><code class="small"><?php echo htmlspecialchars($sv, ENT_QUOTES); ?></code></li>
			  <?php endforeach; ?>
			</ul>
		  </div>
		  <?php endif; ?>
		</div>

		<?php return ob_get_clean();
	}
	
	public function getImportMappingJs(): string
	{
		return <<<'JS'
		function syncDateFormat(select, col) {
			const val    = select.value;
			const input  = document.getElementById('date-fmt-' + col);
			const hidden = document.getElementById('date-fmt-hidden-' + col);
			if (!input || !hidden) return;
			if (val === '__custom__') { input.focus(); return; }
			input.value  = val;
			hidden.value = val;
		}
		JS;
	}
	
	public function getSearchCondition(string $tableAlias, string $value, object $db): string
	{
		$col        = $db->quoteName($tableAlias . '.' . $this->_field->tablecolumn);
		$normalized = $this->normalizeForComparison($value);

		// Extraire l'année pour comparer sans se soucier du format de stockage
		if (preg_match('/^(\d{4})/', $normalized, $m)) {
			return 'LEFT(' . $col . ', 4) = ' . $db->quote($m[1]);
		}

		return $col . ' = ' . $db->quote($value);
	}
	
	public function normalizeForComparison(string $value): string
	{
		if ($value === '') return '';

		// Convertir la valeur en DateTime
		$dt = null;

		if (\DateTime::createFromFormat('Y-m-d H:i:s', $value) !== false) {
			$dt = \DateTime::createFromFormat('Y-m-d H:i:s', $value);
		} elseif (\DateTime::createFromFormat('Y-m-d', $value) !== false) {
			$dt = \DateTime::createFromFormat('Y-m-d', $value);
		} elseif (preg_match('/^\d{2,4}$/', $value)) {
			$yr = (int) $value;
			if ($yr >= 0 && $yr <= 99) {
				$yr = ($yr >= 69 ? 1900 : 2000) + $yr;
			}
			$dt = \DateTime::createFromFormat('Y', (string) $yr);
		} else {
			$fmt = ltrim($this->_field->attribs['format'] ?? '%Y-%m-%d', '%');
			$dt  = \DateTime::createFromFormat($fmt, $value);
		}

		if ($dt === false || $dt === null) return $value;

		// Normaliser selon la granularité du format configuré
		$format = $this->_field->attribs['format'] ?? '%Y-%m-%d';

		if ($format === '%Y') {
			// Granularité : année uniquement
			return $dt->format('Y');
		} elseif (in_array($format, ['%Y-%m', '%m/%Y'])) {
			// Granularité : mois
			return $dt->format('Y-m');
		} else {
			// Granularité : jour complet
			return $dt->format('Y-m-d');
		}
	}
	
	/**
	 * Method to display value in fulltitle
	 *
	 * Can be overloaded/supplemented by the child class
	 *
	 * @param	string		$value	Field value
	 */
	function rebuild($value)
	{
		$format = $this->_field->attribs['format'] ?? '';
		if ($format == '') {
			$format = '%Y-%m-%d';
		}

		if ($value != '') {
			$format = str_replace('%', '', $format);

			if (\DateTime::createFromFormat('Y-m-d H:i:s', $value) === false) {
				$dt = \DateTime::createFromFormat($format, $value);
				if ($dt === false) {
					// Valeur invalide — ne pas l'inclure dans le titre
					return '';
				}
				$value = $dt->format('Y-m-d H:i:s');
			}

			return HTMLHelper::_('date', $value, $format);
		}

		return '';
	}
}
