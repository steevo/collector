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

namespace Steevo\Component\Collector\Administrator\Classes\Field\Define;

use Steevo\Component\Collector\Administrator\Classes\CollectorField;
use Steevo\Component\Collector\Administrator\Table\CollectorDefinedContentTable;
use Joomla\CMS\Factory;
use Joomla\Filesystem\File;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Router\Route;
use Joomla\Database\DatabaseInterface;


defined('_JEXEC') or die;

/**
 * Field Define class
 *
 * @package	Collector
 */
class CollectorFieldDefine extends CollectorField
{
	/**
	 * type
	 * 
	 * @var string
	 */
	public $type = 'define';
	
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
	}
	
	/**
	 * Gets the action to do on submit
	 *
	 * @param	JRegistry object		$params
	 *
	 * @return string
	 */
	function resetSearchArea($params)
	{
		if ( $this->valueFilterMenu == null ) {
			$this->isFiltered($params);
		}
		
		$return = array();
		$return[] = "jQuery( '#filterfield_".$this->_field->tablecolumn."' ).val( '".$this->valueFilterMenu."' );";
		
		return implode($return);
	}
	
	/**
	 * Gets the field attributes for the form definition
	 *
	 * @return string
	 */
	function getFieldAttributes($attributes = array())
	{
		$attributes = array(
			'list'			=> $this->_field->attribs['list'],
			'default'		=> ''
		);
		
		$this->directory = 'images/collector/define/' . $attributes['list'];
		
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
		$query->select('d'.$this->_field->id.'.content AS `'.$this->_field->tablecolumn.'`');
		$query->join('LEFT', '#__collector_defined_content AS d'.$this->_field->id.' ON d'.$this->_field->id.'.id = h.'.$this->_field->tablecolumn);
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
		$db = Factory::getContainer()->get(DatabaseInterface::class);
		$text = $db->quote('%' . $db->escape($search_all_value, true) . '%', false);
		$where = 'LOWER(d'.$this->_field->id.'.content) LIKE LOWER(' . $text . ')';
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
		if ( $value != '' )
		{
			// $query->where('h.'.$this->_field->tablecolumn.' = "'.$value.'"');
			$query->where('d'.$this->_field->id.'.path LIKE "%|'.$value.'|%"');
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
		$orderBy = 'd'.$this->_field->id.'.lft';
		return $orderBy;
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
		// Initiliase variables.
		$application	= Factory::getApplication();
		$html = array();
		$attr = '';
		
		$user	= Factory::getApplication()->getIdentity();
		$groups	= implode(',', $user->getAuthorisedViewLevels());
		
		if ($menu) {
			$prefix = 'jform[params][filter][';
			$suffix = ']';
		} else {
			$prefix = '';
			$suffix = '';
		}
		
		$db = Factory::getContainer()->get(DatabaseInterface::class);
		$query = $db->getQuery(true)
			->select('c.id AS value, c.content AS text, c.level')
			->from('#__collector_defined_content AS c')
			->join('LEFT', $db->quoteName('#__collector_defined_content') . ' AS b ON c.lft > b.lft AND c.rgt < b.rgt');

		if ($defined = $this->_field->attribs['list'])
		{
			$query->where('c.defined = ' . $db->quote($defined));
		}
		else
		{
			$query->where('c.defined != ' . $db->quote(''));
		}

		$query->group('c.id, c.content, c.level, c.lft, c.rgt, c.defined, c.parent_id')
			->order('LOWER(c.content) ASC');

		// Get the options.
		$db->setQuery($query);
		
		try
		{
			$options = $db->loadObjectList();
		}
		catch (RuntimeException $e)
		{
			$application->enqueueMessage($e->getMessage(),'warning');
		}
		
		// Pad the option text with spaces using depth level as a multiplier.
		for ($i = 0, $n = count($options); $i < $n; $i++)
		{
			$options[$i]->text = str_repeat('- ', $options[$i]->level-1) . $options[$i]->text;
		}
		
		// Filter unselected items
		if ( ($this->_field->attribs['hide_unselected']) && ($menu == false) ) {
			$query = $db->getQuery(true)
				->select('h.' . $this->_field->tablecolumn . ' AS value')
				->from('#__collector_items_history_' . $this->_field->collection . ' AS h')
				->join('LEFT', $db->quoteName('#__collector_items') . ' AS i ON h.item = i.id');

			$query->where('i.access IN ('.$groups.')');
			// Filter by start and end dates.
			if ((!$user->authorise('core.edit.state', 'com_collector.collection.'.(int) $this->_field->collection)) &&  (!$user->authorise('core.edit', 'com_collector.collection.'.(int) $this->_field->collection))){
				$nullDate	= $db->quote($db->getNullDate());
				$nowDate	= $db->quote(Factory::getDate()->toSql());

				$query->where('(i.publish_up = '.$nullDate.' OR i.publish_up <= '.$nowDate.')')
					->where('(i.publish_down = '.$nullDate.' OR i.publish_down >= '.$nowDate.')')
					->where('i.state = 1');
			}
			$query->group('value');

			// Get the options.
			$db->setQuery($query);
			
			try
			{
				$optionsSelected = $db->loadObjectList('value');
			}
			catch (RuntimeException $e)
			{
				$application->enqueueMessage($e->getMessage(),'warning');
			}
			
			$tempDelete = array();
			
			$old_level = 0;
			$tempDelete[$old_level] = array();
			
			for ($i = 0, $n = count($options); $i < $n; $i++)
			{
				$actual_level = $options[$i]->level;
				
				if ( $actual_level == $old_level )
				{
					foreach ( $tempDelete[$actual_level] as $key => $deleteId )
					{
						unset($options[$deleteId]);
					}
				}
				else if ( $actual_level < $old_level )
				{
					for ($level = $actual_level; $level <= $old_level; $level++)
					{
						foreach ( $tempDelete[$level] as $key => $deleteId)
						{
							unset($options[$deleteId]);
						}
					}
				}
				else
				{
					$tempDelete[$actual_level] = array();
				}
				
				if (!array_key_exists($options[$i]->value, $optionsSelected))
				{
					$tempDelete[$actual_level][] = $i;
				}
				else
				{
					for ($level = 0; $level < $actual_level; $level++)
					{
						$tempDelete[$level] = array();
					}
				}
				
				$old_level = $actual_level;
			}
			
			for ($level = 0; $level <= $actual_level; $level++)
			{
				foreach ( $tempDelete[$level] as $key => $deleteId)
				{
					unset($options[$deleteId]);
				}
			}
		}
		
		array_unshift($options,array('value' => '', 'text' => ''));
		
		// Initialize some field attributes.
		if ( $params && (!$params->get('show_entire_listing',true)) && ($menu == false) ) {
			$requiredparams = $params->get('required');
			$required = $requiredparams['filterfield_'.$this->_field->tablecolumn]?' required':'';
		} else {
			$required = '';
		}
		$attr .= ' class="inputbox'.$required.'"';
		$attr .= ' size="1"';

		if ( $this->valueFilterMenu == null ) {
			$this->isFiltered($params);
			if ( $value == '' ) {
				$value = $this->valueFilterMenu;
			}
		}
		
		if ( ($menu == true) || (!$this->valueFilterMenu) || ( $this->valueFilterMenu && (!$params->get('hide_filter'))) )
		{
			if ( $params && (!$params->get('show_entire_listing',true)) && ($menu == false) ) {
				$requiredparams = $params->get('required');
				$required = $requiredparams['filterfield_'.$this->_field->tablecolumn]?'<span class="star">&nbsp;*</span>&nbsp;&nbsp;':'&nbsp;&nbsp;';
			} else {
				$required = '&nbsp;&nbsp;';
			}
			// Create a regular list.
			$html[] = $this->_field->field . $required;
			if ($menu == true) {
				$html[] = '</td><td>';
			}
			$html[] = HTMLHelper::_('select.genericlist', $options, $prefix.'filterfield_'.$this->_field->tablecolumn.$suffix, trim($attr), 'value', 'text', $value);
		}
		else
		{
			$html[] = '<input type=hidden name=\''.$prefix.'filter_field_'.$this->_field->tablecolumn.$suffix.'\' value=\''.$value.'\' />';
		}

		if ($menu == true) {
			$return = '<td>'.implode($html).'</td>';
		} else {
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
		$db = Factory::getContainer()->get(DatabaseInterface::class);
		$defined = (int) ($this->_field->attribs['list'] ?? 0);
		
		$query = 'SELECT id, content, image, defined';
		$query .= ' FROM #__collector_defined_content';
		$query .= ' WHERE content = '.$db->quote($value);
		if ($defined) {
			$query .= ' AND defined = ' . $defined;
		}

		$db->setQuery( $query );

		$result = $db->loadObject();

		// ── Si pas trouvé par contenu et que la valeur est un ID numérique,
		//    chercher directement par ID (cas import : valeur stockée = ID DB)
		if ($result === null && is_numeric($value) && (int)$value > 0) {
			$query = 'SELECT id, content, image, defined'
				   . ' FROM #__collector_defined_content'
				   . ' WHERE id = ' . (int)$value;
			if ($defined) {
				$query .= ' AND defined = ' . $defined;
			}
			$db->setQuery($query);
			$result = $db->loadObject();
		}
		
		if ( $result != null )
		{
			if ( File::exists($result->image) )
			{
				$query = 'SELECT width, height';
				$query .= ' FROM #__collector_defined';
				$query .= ' WHERE id = "'.$result->defined.'"';

				$db->setQuery( $query );

				$image = $db->loadObject();

				$imagePath=$result->image;
				$imagePath=JPATH_SITE.'/'.$imagePath;
				$taille=getimagesize($imagePath);
				$largeur=$taille[0];
				$hauteur=$taille[1];

				$largeurmax = $image->width ? $image->width : $largeur;
				$hauteurmax = $image->height ? $image->height : $hauteur;

				if ( ($hauteur/$hauteurmax) < ($largeur/$largeurmax) )
				{
					if ($largeur < $largeurmax)
					{
						$size= 'style="width:'.$largeur.'px;"';
					}
					else
					{
						$size= 'style="width:'.$largeurmax.'px;"';
					}
				}
				else
				{
					if ($hauteur < $hauteurmax)
					{
						$size= 'style="height:'.$hauteur.'px;"';
					}
					else
					{
						$size= 'style="height:'.$hauteurmax.'px;"';
					}
				}
				
				$return = '<img src="'.$result->image.'" alt="'.$result->content.'" title="'.$result->content.'" '.$size.' />';
			} else {
				$return = $result->content;
			}
			
			if ($params && !$params->get('compare_view')) {
				if ( ($this->_field->attribs['show_fieldlink'] ?? 0) && (!empty($this->_field->filter ?? null)) )
				{
					// $link = 'javascript:setSearchField(\'filter_field_'.$this->_field->tablecolumn.'\',\''.$value.'\')';
					$link= 'index.php?option=com_collector&view=collection&id='.$this->_field->collection.'&reset=0&filterfield_'.$this->_field->tablecolumn.'='.$result->id;
					$return = '<a href="'.Route::_($link).'">'.$return.'</a>';
				}
			}
		} else {
			$return = false;
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
	/**
	 * Résout une valeur Excel en ID de la table #__collector_defined_content.
	 *
	 * Ordre de résolution :
	 *  1. Mapping explicite fourni par l'utilisateur dans l'UI d'import :
	 *     $mapping['values'][$value] = db_id  (clé = valeur Excel, valeur = id DB)
	 *     $mapping['empty']          = db_id  (si la cellule Excel est vide)
	 *  2. Correspondance insensible à la casse sur le contenu de la liste
	 *  3. Valeur vide → retourne '' (pas d'insertion automatique)
	 *
	 * Note : la création automatique de valeur (ancien comportement avec
	 * JTable::getInstance) est supprimée — elle utilisait l'API Joomla 3
	 * et créait des entrées non désirées en cas d'erreur de mapping.
	 *
	 * @param  string  $value    Valeur brute lue dans le fichier Excel/CSV
	 * @param  array   $mapping  Mapping optionnel fourni par l'UI d'import
	 * @return string  ID de la valeur dans #__collector_defined_content, ou ''
	 */
	public function getImportedValue($value, array $mapping = [])
	{
		$valueTrimmed = trim((string) $value);

		// ── 1. Mapping explicite ─────────────────────────────────────────────
		if (!empty($mapping)) {
			// Cellule vide → valeur mappée pour le vide
			if ($valueTrimmed === '' && isset($mapping['empty']) && $mapping['empty'] !== '') {
				return (string) $mapping['empty'];
			}
			// Correspondance par valeur fichier (insensible à la casse)
			// Structure : mapping['values'] = [['file'=>'...', 'db'=>id], ...]
			if ($valueTrimmed !== '' && isset($mapping['values'])) {
				foreach ($mapping['values'] as $pair) {
					$fileVal     = trim((string) ($pair['file']         ?? ''));
					$dbId        = (string) ($pair['db']               ?? '');
					$shouldCreate = !empty($pair['create']);
					$createLabel  = trim((string) ($pair['create_label'] ?? $fileVal));
					if ($createLabel === '') $createLabel = $fileVal;

					if (mb_strtolower($fileVal) !== mb_strtolower($valueTrimmed)) continue;

					if ($dbId !== '') {
						// Vérifier que l'ID existe encore en base
						$db = Factory::getContainer()->get(DatabaseInterface::class);
						$db->setQuery($db->getQuery(true)
							->select('COUNT(*)')
							->from('#__collector_defined_content')
							->where('id = ' . (int) $dbId)
							->where('defined = ' . $db->quote($this->_field->attribs['list']))
						);
						if ((int) $db->loadResult()) {
							return $dbId; // ← ID valide
						}
						// ID invalide → tomber sur les étapes suivantes (lookup + autocreate)
					}

					if ($shouldCreate && $createLabel !== '') {
						// Créer dans la base avec le label personnalisé
						return $this->createDefinedValue($createLabel);
					}

					return '';  // Pas mappée, pas de création
				}
			}
		}

		// ── 2. Cellule vide sans mapping → pas de valeur ─────────────────────
		if ($valueTrimmed === '') {
			return '';
		}

		// ── 3. Correspondance automatique insensible à la casse ──────────────
		$db = Factory::getContainer()->get(DatabaseInterface::class);
		$query = $db->getQuery(true)
			->select('id')
			->from('#__collector_defined_content')
			->where('defined = ' . $db->quote($this->_field->attribs['list']))
			->where('LOWER(content) = LOWER(' . $db->quote($valueTrimmed) . ')');

		$db->setQuery($query);
		$result = $db->loadResult();

		if ($result) {
			return (string) $result;
		}

		// ── 4. Aucune correspondance → création automatique de la valeur ──────
		try {
			$listitem = new CollectorDefinedContentTable($db);
			$listitem->setLocation(1, 'last-child');
			$listitem->bind([
				'parent_id' => 1,
				'content'   => $valueTrimmed,
				'defined'   => $this->_field->attribs['list'],
				'image'     => '',
			]);
			$listitem->check();
			$listitem->store();
			$listitem->rebuildPath($listitem->id);

			return $listitem->id ? (string) $listitem->id : '';
		} catch (\Throwable $e) {		
			// En cas d'échec (doublon de race condition, erreur BDD…)
			// on retente une lecture avant de renvoyer vide
			$db->setQuery($db->getQuery(true)
				->select('id')
				->from('#__collector_defined_content')
				->where('defined = ' . $db->quote($this->_field->attribs['list']))
				->where('LOWER(content) = LOWER(' . $db->quote($valueTrimmed) . ')'));
			$retry = $db->loadResult();
			return $retry ? (string) $retry : '';
		}
	}
	
	private function createDefinedValue(string $label): string
	{
		$db       = Factory::getContainer()->get(DatabaseInterface::class);
		$defined  = $this->_field->attribs['list'] ?? '';

		// Vérifier si existe déjà (race condition)
		$db->setQuery($db->getQuery(true)
			->select('id')
			->from('#__collector_defined_content')
			->where('defined = '      . $db->quote($defined))
			->where('LOWER(content) = LOWER(' . $db->quote($label) . ')')
		);
		$existing = $db->loadResult();
		if ($existing) return (string) $existing;

		try {
			$item = new CollectorDefinedContentTable($db);
			$item->setLocation(1, 'last-child');
			$item->bind([
				'parent_id' => 1,
				'content'   => $label,
				'image'     => '',
				'defined'   => $defined,
			]);
			if (!$item->check() || !$item->store()) return '';
			$item->rebuildPath($item->id);
			return $item->id ? (string) $item->id : '';
		} catch (\Throwable $e) {
			error_log('createDefinedValue FAILED: ' . $e->getMessage());
			return '';
		}
	}
	
	/**
	 * Retourne la configuration de mapping d'import pour ce champ de type define.
	 *
	 * Retourne la liste complète des valeurs de la liste associée à ce champ,
	 * permettant à l'UI d'import d'afficher une table de correspondance :
	 *   colonne Excel → valeur de la liste  (ou cellule vide → valeur de la liste)
	 *
	 * @return array  ['type'=>'define', 'col'=>'...', 'label'=>'...', 'options'=>[...]]
	 */
	public function getImportMapping(): ?array
	{
		$db = Factory::getContainer()->get(DatabaseInterface::class);
		$query = $db->getQuery(true)
			->select('c.id, c.content, c.level')
			->from('#__collector_defined_content AS c')
			->where('c.defined = ' . $db->quote($this->_field->attribs['list']))
			->order('c.content ASC');

		$db->setQuery($query);
		$options = $db->loadObjectList();

		if (empty($options)) {
			return null;
		}

		// Trier insensible à la casse (ORDER BY sur la collation MySQL peut varier)
		usort($options, fn($a, $b) => strcasecmp($a->content, $b->content));

		// Indenter le libellé selon le niveau APRÈS le tri
		foreach ($options as $opt) {
			$opt->content = str_repeat('— ', max(0, $opt->level - 1)) . $opt->content;
		}

		return [
			'type'    => 'define',
			'col'     => $this->_field->tablecolumn,
			'label'   => $this->_field->field,
			'options' => $options,
		];
	}
	
	public function renderImportMapping(array $savedCol, array $fileValues): string
	{
		// Valider les IDs sauvegardés — réinitialiser si l'entrée a été supprimée
		$defined = $this->_field->attribs['list'] ?? '';
		if (!empty($savedCol['values'])) {
			$db = Factory::getContainer()->get(DatabaseInterface::class);
			foreach ($savedCol['values'] as $idx => &$pair) {
				$dbId = (string) ($pair['db'] ?? '');
				if ($dbId === '') continue;
				$db->setQuery($db->getQuery(true)
					->select('COUNT(*)')
					->from('#__collector_defined_content')
					->where('id = ' . (int) $dbId)
					->where('defined = ' . $db->quote($defined))
				);
				if (!(int) $db->loadResult()) {
					// L'ID n'existe plus → réinitialiser pour permettre re-création
					$pair['db']           = '';
					$pair['create']       = '1';
					$pair['create_label'] = $pair['file'] ?? '';
				}
			}
			unset($pair);
		}
		
		$mapCfg = $this->getImportMapping();
		if ($mapCfg === null) return '';

		$col        = $this->_field->tablecolumn;
		$colEsc     = htmlspecialchars($col, ENT_QUOTES);
		$datalistId = 'dl-' . $colEsc;
		$noMap      = Text::_('COM_COLLECTOR_IMPORT_DEFINE_NO_MAP');

		$dbIdToName = [];
		$dbNameToId = [];
		foreach ($mapCfg['options'] as $o) {
			$dbIdToName[(string)$o->id]                   = $o->content;
			$dbNameToId[mb_strtolower(trim($o->content))] = (string)$o->id;
		}

		$hasEmptyInFile = in_array('', $fileValues, true);
		$nonEmptyVals   = array_values(array_filter($fileValues, fn($v) => $v !== ''));
		$savedEmpty     = (string)($savedCol['empty'] ?? '');
		$savedEmptyName = $dbIdToName[$savedEmpty] ?? '';

		ob_start(); ?>

		<datalist id="<?php echo $datalistId; ?>">
		  <?php foreach ($mapCfg['options'] as $opt): ?>
		  <option value="<?php echo htmlspecialchars($opt->content, ENT_QUOTES); ?>"
				  data-id="<?php echo (int)$opt->id; ?>"></option>
		  <?php endforeach; ?>
		</datalist>

		<script>
		window._dbOptions = window._dbOptions || {};
		window._dbOptions[<?php echo json_encode($col); ?>] = <?php echo json_encode(
			array_map(fn($o) => ['id' => (int)$o->id, 'content' => $o->content], $mapCfg['options']),
			JSON_HEX_APOS | JSON_HEX_QUOT
		); ?>;
		</script>

		<table class="table table-sm mb-0">
		  <thead class="table-light">
			<tr>
			  <th class="w-40"><?php echo Text::_('COM_COLLECTOR_IMPORT_DEFINE_COL_FILE'); ?></th>
			  <th class="w-40"><?php echo Text::_('COM_COLLECTOR_IMPORT_DEFINE_COL_DB'); ?></th>
			  <th class="w-20"><?php echo Text::_('COM_COLLECTOR_IMPORT_DEFINE_COL_CREATE'); ?></th>
			</tr>
		  </thead>
		  <tbody>

		  <?php if ($hasEmptyInFile || $savedEmpty !== ''): ?>
		  <tr class="table-warning">
			<td class="small text-muted fst-italic align-middle">
			  <span class="icon-minus me-1"></span><?php echo Text::_('COM_COLLECTOR_IMPORT_DEFINE_EMPTY_CELL'); ?>
			</td>
			<td>
			  <div class="input-group input-group-sm">
				<input type="text" class="form-control form-control-sm db-name-input"
					   list="<?php echo $datalistId; ?>"
					   data-col="<?php echo $colEsc; ?>"
					   data-target="empty-db-<?php echo $colEsc; ?>"
					   placeholder="<?php echo $noMap; ?>"
					   value="<?php echo htmlspecialchars($savedEmptyName, ENT_QUOTES); ?>"
					   oninput="resolveDbId(this)">
				<input type="hidden"
					   id="empty-db-<?php echo $colEsc; ?>"
					   name="valuemap[<?php echo $colEsc; ?>][empty]"
					   value="<?php echo htmlspecialchars($savedEmpty, ENT_QUOTES); ?>">
			  </div>
			</td>
		  </tr>
		  <?php endif; ?>

		  <?php if (empty($nonEmptyVals)): ?>
			<tr><td colspan="2" class="text-muted small fst-italic text-center py-2">
			  <?php echo Text::_('COM_COLLECTOR_IMPORT_DEFINE_NO_FILE_VALUES'); ?>
			</td></tr>
		  <?php endif; ?>

		  <?php foreach ($nonEmptyVals as $rowIdx => $fileVal):
			  $autoId      = $dbNameToId[mb_strtolower(trim($fileVal))] ?? '';
			  $savedPair = [];
			  foreach (($savedCol['values'] ?? []) as $pair) {
				if (mb_strtolower(trim((string)($pair['file'] ?? ''))) === mb_strtolower($fileVal)) {
					$savedPair = $pair;
					break;
				}
			  }
			  $savedFile   = $savedPair['file'] ?? $fileVal;
			  $savedDbId   = (string)($savedPair['db'] ?? $autoId);
			  $savedDbName = $dbIdToName[$savedDbId] ?? '';
			  $rowId       = $colEsc . '-' . $rowIdx;
		  ?>
		  <tr>
			<td>
			  <input type="text" class="form-control form-control-sm"
					 name="valuemap[<?php echo $colEsc; ?>][values][<?php echo $rowIdx; ?>][file]"
					 value="<?php echo htmlspecialchars($savedFile, ENT_QUOTES); ?>">
			</td>
			<td>
			  <div class="input-group input-group-sm">
				<input type="text" class="form-control form-control-sm db-name-input"
					   list="<?php echo $datalistId; ?>"
					   data-col="<?php echo $colEsc; ?>"
					   data-target="db-id-<?php echo $rowId; ?>"
					   value="<?php echo htmlspecialchars($savedDbName, ENT_QUOTES); ?>"
					   oninput="resolveDbId(this)">
				<input type="hidden"
					   id="db-id-<?php echo $rowId; ?>"
					   name="valuemap[<?php echo $colEsc; ?>][values][<?php echo $rowIdx; ?>][db]"
					   value="<?php echo htmlspecialchars($savedDbId, ENT_QUOTES); ?>">
			  </div>
			</td>
			<?php
			$shouldCreate  = (bool) ($savedPair['create']       ?? ($savedDbId === ''));
			$createLabel   = (string) ($savedPair['create_label'] ?? $savedFile);
			$rowId         = $colEsc . '-' . $rowIdx;
			?>
			<td>
			  <div id="create-section-<?php echo $rowId; ?>"
				 class="<?php echo $savedDbId !== '' ? 'd-none' : ''; ?>">
				<div class="form-check form-check-inline mb-1">
				  <input type="checkbox"
						 name="valuemap[<?php echo $colEsc; ?>][values][<?php echo $rowIdx; ?>][create]"
						 value="1"
						 id="create-chk-<?php echo $rowId; ?>"
						 class="form-check-input create-chk"
						 data-target="create-label-<?php echo $rowId; ?>"
						 <?php echo $shouldCreate ? 'checked' : ''; ?>>
				  <label for="create-chk-<?php echo $rowId; ?>" class="form-check-label small">
					<?php echo Text::_('COM_COLLECTOR_IMPORT_DEFINE_CREATE'); ?>
				  </label>
				</div>
				<input type="text"
					   id="create-label-<?php echo $rowId; ?>"
					   name="valuemap[<?php echo $colEsc; ?>][values][<?php echo $rowIdx; ?>][create_label]"
					   class="form-control form-control-sm <?php echo $shouldCreate ? '' : 'd-none'; ?>"
					   value="<?php echo htmlspecialchars($createLabel, ENT_QUOTES); ?>"
					   placeholder="<?php echo Text::_('COM_COLLECTOR_IMPORT_DEFINE_CREATE_LABEL'); ?>">
			  </div>

			  <span id="mapped-indicator-<?php echo $rowId; ?>"
					class="text-success small <?php echo $savedDbId === '' ? 'd-none' : ''; ?>">
				<span class="icon-check me-1"></span>
				<?php echo Text::_('COM_COLLECTOR_IMPORT_DEFINE_MAPPED'); ?>
			  </span>
			</td>
		  </tr>
		  <?php endforeach; ?>

		  </tbody>
		</table>

		<?php return ob_get_clean();
	}
	
	public function getImportMappingJs(): string
	{
		return <<<'JS'
		document.addEventListener('DOMContentLoaded', function () {
			document.querySelectorAll('.db-name-input').forEach(function (input) {
				input.addEventListener('input', function () {
					const target = this.dataset.target;
					const hidden = document.getElementById(target);
					if (!hidden) return;

					setTimeout(function () {
						const rowId    = target.replace('db-id-', '');
						const section  = document.getElementById('create-section-'  + rowId);
						const indicator = document.getElementById('mapped-indicator-' + rowId);
						const hasDb    = hidden.value !== '';

						if (section)   section.classList.toggle('d-none',  hasDb);
						if (indicator) indicator.classList.toggle('d-none', !hasDb);

						// Si db devient vide → décocher "Créer" au cas où
						if (!hasDb) {
							const chk = document.getElementById('create-chk-' + rowId);
							if (chk) { chk.checked = false; chk.dispatchEvent(new Event('change')); }
						}
					}, 0);
				});
			});

			document.querySelectorAll('.create-chk').forEach(function (chk) {
				chk.addEventListener('change', function () {
					const lbl = document.getElementById(this.dataset.target);
					if (lbl) lbl.classList.toggle('d-none', !this.checked);

					if (this.checked) {
						const rowId  = this.id.replace('create-chk-', '');
						const dbInput = document.querySelector('[data-target="db-id-' + rowId + '"]');
						const dbHid   = document.getElementById('db-id-' + rowId);
						if (dbInput) dbInput.value = '';
						if (dbHid)   dbHid.value   = '';
						const indicator = document.getElementById('mapped-indicator-' + rowId);
						if (indicator) indicator.classList.add('d-none');
					}
				});
			});
		});
		JS;
	}
	
	public function refreshImportMapping(array $colMapping): ?array
	{
		$defined = $this->_field->attribs['list'] ?? '';
		$pairs   = $colMapping['values'] ?? [];
		$changed = false;

		foreach ($pairs as $idx => $pair) {
			if (($pair['db'] ?? '') !== '') continue;
			if (empty($pair['create']))      continue;

			$label = trim($pair['create_label'] ?? $pair['file'] ?? '');
			if ($label === '') continue;

			$db = Factory::getContainer()->get(DatabaseInterface::class);
			$db->setQuery($db->getQuery(true)
				->select('id')
				->from('#__collector_defined_content')
				->where('defined = ' . $db->quote($defined))
				->where('LOWER(content) = LOWER(' . $db->quote($label) . ')')
			);
			$newId = $db->loadResult();

			if ($newId) {
				$colMapping['values'][$idx]['db'] = (string) $newId;
				unset($colMapping['values'][$idx]['create']);
				unset($colMapping['values'][$idx]['create_label']);
				$changed = true;
			}
		}

		return $changed ? $colMapping : null;
	}

	public function normalizeForComparison(string $value): string
	{
		// "" et "0" signifient tous les deux "pas de valeur" pour un champ define
		if ($value === '' || $value === '0') {
			return '';
		}
		return $value;
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
		$db = Factory::getContainer()->get(DatabaseInterface::class);
		
		$query = 'SELECT content, image, defined';
		$query .= ' FROM #__collector_defined_content';
		$query .= ' WHERE id = "'.$value.'"';

		$db->setQuery( $query );

		$result = $db->loadObject();

		if ( $result != null )
		{
			$return = $result->content;
		} else {
			$return = '';
		}
		return $return;
	}
	
	/**
	 * Method to use ajax
	 *
	 * Can be overloaded/supplemented by the child class
	 */
	function ajax()
	{
		$input		= Factory::getApplication()->input;
		$action		= $input->get('action');
		
		switch ($action) {
			case 'populate' :
				$defined		= $input->get('defined');
				$parent_id		= $input->get('parent_id');
				$level			= $input->get('level');
				
				$db = Factory::getContainer()->get(DatabaseInterface::class);
				$query = $db->getQuery(true);
				
				// Select the required fields from the table.
				$query->select('c.id AS value, c.defined, c.path, c.parent_id, c.level, c.content AS text, c.lft, c.rgt');
				$query->from('#__collector_defined_content as c');
				
				// Filter by list
				$query->where('c.defined = '.$defined);
				
				// Filter by parent
				$query->where('c.parent_id = '.$parent_id);
				
				// Filter by level
				$query->where('c.level = '.$level);
				
				// Add the list ordering clause.
				$query->order($db->escape('c.lft') . ' ' . $db->escape('ASC'));
				
				$code = HTMLHelper::_('select.genericlist', $select, 'jform[request][id]', ' class=”inputbox” size="1" ', 'value', 'text', $default, 'jform_request_id');
				
				break;
		}
		
		return;
	}
}