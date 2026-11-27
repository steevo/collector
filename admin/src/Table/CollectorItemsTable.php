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

namespace Steevo\Component\Collector\Administrator\Table;

defined('_JEXEC') or die;

use Joomla\CMS\Access\Rules;
use Joomla\CMS\Application\ApplicationHelper;
use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\Factory;
use Joomla\CMS\Filter\InputFilter;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Router\Route;
use Joomla\CMS\String\PunycodeHelper;
use Joomla\CMS\Table\Table;
use Joomla\CMS\Tag\TaggableTableInterface;
use Joomla\CMS\Tag\TaggableTableTrait;
use Joomla\CMS\Versioning\VersionableTableInterface;
use Joomla\Database\ParameterType;
use Joomla\Database\DatabaseDriver;
use Joomla\Registry\Registry;
use Joomla\String\StringHelper;
use Joomla\Utilities\ArrayHelper;
use Steevo\Component\Collector\Administrator\Table\CollectorItemsHistoryTable;
use Steevo\Component\Collector\Administrator\Classes\CollectorField;
use Steevo\Component\Collector\Administrator\Classes\Field\Date\CollectorFieldDate;
use Steevo\Component\Collector\Administrator\Classes\Field\Define\CollectorFieldDefine;
use Steevo\Component\Collector\Administrator\Classes\Field\File\CollectorFieldFile;
use Steevo\Component\Collector\Administrator\Classes\Field\Image\CollectorFieldImage;
use Steevo\Component\Collector\Administrator\Classes\Field\Item\CollectorFieldItem;
use Steevo\Component\Collector\Administrator\Classes\Field\Line\CollectorFieldLine;
use Steevo\Component\Collector\Administrator\Classes\Field\Number\CollectorFieldNumber;
use Steevo\Component\Collector\Administrator\Classes\Field\Text\CollectorFieldText;
use Joomla\Database\DatabaseInterface;
use Joomla\CMS\Table\Asset;

/**
 * Collector_items table
 * @package		Collector
 */
class CollectorItemsTable extends Table
{	
	/**
     * @var string
     */
    public $typeAlias = 'com_collector.item';
	
	/**
	 * @param database A database connector object
	 */
	function __construct( DatabaseDriver $db )
	{
		parent::__construct( '#__collector_items', 'id', $db );
	}
	
	/**
	 * function to load a version of an item
	 *
	 * @access	public
	 * @param	int		$collection	Collection Id
	 */
	function initVersion($collection)
	{
		// mise a jour du nom de la table
		$this->collection = $collection;
		
		// $this->history = TableCollector_items_history_::getInstance($collection);
		// $this->history = CollectorItemsHistoryTable::getInstance('Collector_items_history_', 'Table', array('collection'=>$collection));
		$this->history = new CollectorItemsHistoryTable($this->getDbo(),$collection);
		$this->history->collection = $collection;
		
		$this->history->initVersion($collection);
		
		// Convert to the JObject before adding other data.
		$properties = $this->history->getProperties(1);
		
		foreach ( $properties as $key => $value )
		{
			if ($key!='id')
			{
				$this->$key = $value;
			}
			else
			{
				$this->historyId = $value;
			}
		}
		
		$this->modification = Text::_('COM_COLLECTOR_INIT_VERSION');
	}
	
	/**
	 * function to load a copy version of an item
	 *
	 * @access	public
	 * @param	int		$collection	Collection Id
	 * @param	int		$id			Item Id
	 * @param	int		$version	Version Id
	 */
	function loadCopyVersion( $collection, $id, $version = null )
	{
		// initialisation de l'item
		$this->initVersion($collection);
		
		// recuperation de l'item
		$this->load($id);
		
		// recuperation des donnees de la version
		$this->history->loadVersion( $collection, $id, true, $version );

		// Convert to the JObject before adding other data.
		$properties = $this->history->getProperties(1);
		
		foreach ( $properties as $key => $value )
		{
			if ( ($key!='id') && ($key!='state') )
			{
				$this->$key = $value;
			}
			else
			{
				$this->historyId = $value;
			}
		}
		
		unset($this->history);
		
		return true;
	}
	
	/**
	 * Overloaded load function
	 *
	 * @param   mixed    $keys   An optional primary key value to load the row by, or an array of fields to match.  If not
	 *                           set the instance property value is used.
	 * @param   boolean  $reset  True to reset the default values before loading the new row.
	 *
	 * @return  boolean  True if successful. False if row not found or on error (internal error state set in that case).
	 */
	public function load($keys = null, $reset = true)
	{
		$return = parent::load($keys, $reset);
		
		return $return;
	}
	
	/**
	 * function to load a version of an item
	 *
	 * @access	public
	 * @param	int		$collection	Collection Id
	 * @param	int		$id			Item Id
	 * @param	int		$version	Version Id
	 */
	function loadVersion( $collection, $id, $version = null )
	{
		// initialisation de l'item
		$this->initVersion($collection);
		
		// recuperation de l'item
		$this->load($id);
		
		// recuperation des donnees de la version
		$this->history->loadVersion( $collection, $id, false, $version );
		
		// Convert to the JObject before adding other data.
		$properties = $this->history->getProperties(1);
		
		foreach ( $properties as $key => $value )
		{
			if ($key == 'id')
			{
				$this->historyId = $value;
			}
			elseif ($key == 'state')
			{
				// L'objet garde son état de publication : celui de la version (en cours, archivée…) ne le
				// remplace pas, sinon l'enregistrement republie un objet dépublié ou à la corbeille
				continue;
			}
			else
			{
				$this->$key = $value;
			}
		}

		$this->modification = '';
		
		unset($this->history);
		
		return true;
	}
	
	/**
	 * Overloaded bind function
	 *
	 * @param       array           named array
	 * @return      null|string     null is operation was satisfactory, otherwise returns an error
	 * @see JTable:bind
	 */
	public function bind($array, $ignore = '') 
	{
		if (isset($array['metadata']) && is_array($array['metadata'])) {
			$registry          = new Registry($array['metadata']);
            $array['metadata'] = (string) $registry;
		}

		// Bind the rules.
		if (isset($array['rules']) && is_array($array['rules'])) {
			$rules = new Rules($array['rules']);
			$this->setRules($rules);
		}
		
		return parent::bind($array, $ignore);
	}
	
	/**
	 * function to bind a version of an item
	 *
	 * @access	public
	 * @param	mixed	$from		An associative array or object
	 */
	function bindVersion( $from )
	{
		$this->history->bind($from);
		return parent::bind($from);
	}
	
	/**
	 * function to check a version of an item
	 *
	 * @access	public
	 */
	function checkVersion()
	{
		// cast item Id
		$this->id = (int)$this->id;
		
		$query = 'SELECT tablecolumn FROM `#__collector_fields`';
		$query .= ' WHERE collection = ' . $this->collection;
		$query .= ' AND unik = 1';
		$query .= ' ORDER BY ordering';
			
		$this->_db->setQuery($query);
		
		$fields = $this->_db->loadObjectList();
		
		if ( $fields != null )
		{
			$where = array();
			
			$query = 'SELECT i.id FROM `#__collector_items` AS i';
			$query .= ' LEFT JOIN `#__collector_items_history_'.$this->collection.'` AS h ON h.item = i.id';
			$query .= ' WHERE h.state = 1';
			$query .= ' AND i.collection = '.$this->collection.'';
			$query .= ' AND i.id != '. (int) $this->id;
			
			foreach ($fields as $field)
			{
				$nameField = $field->tablecolumn;
				$where[] = 'h.'. $field->tablecolumn .' = "'.$this->$nameField.'"';
			}
			
			if (count($where) > 0 ) {
				$query.= ' AND ('.implode(' OR ', $where).')';
			}
			
			$this->_db->setQuery( $query );
			$xid = intval( $this->_db->loadResult() );
			if ($xid && $xid != intval( $this->id ))
			{
				$msg = Text::_('COM_COLLECTOR_WARNREG_INUSE');
				if ( JPATH_COMPONENT == JPATH_COMPONENT_SITE )
				{
					$link = Route::_( 'index.php?option=com_collector&view=item&id=' . $this->collection . '&item=' . $xid );
				}
				else
				{
					$link = Route::_( 'index.php?option=com_collector&controller=items&collection=' . $this->collection . '&task=edit&cid[]=' . $xid );
				}
				$a = '<a href="'.$link.'">'.$xid.'</a>';
				$this->setError( Text::sprintf( $msg, $a ) );
				return false;
			}
		}
		
		return $this->check();
	}
	
	/**
	 * function to store a version of an item
	 *
	 * @access	public
	 * @param	int		$version	Version Id
	 */
	function storeVersion( $version = null , $auto_validate = 1)
	{
		$date	= Factory::getDate();
		$user	= Factory::getApplication()->getIdentity();
		
		if ( !$this->id ) {
			$this->modified = null;
			$this->modified_by = null;
		}
		
		// $this->history = TableCollector_items_history_::getInstance($this->collection);
		// $this->history = CollectorItemsHistoryTable::getInstance('Collector_items_history_', 'Table', array('collection'=>$this->collection));
		$this->history = new CollectorItemsHistoryTable($this->getDbo(),$this->collection);
		$this->history->collection = $this->collection;
		
		// recuperation des donnees de la version
		$this->history->loadVersion( $this->collection, $this->id, false, $version );

		// Version sur laquelle la nouvelle a été saisie (la version en cours, sauf version donnée)
		$baseVersion = (int) $this->history->id;

		// Convert to the JObject before adding other data.
		$properties = $this->history->getProperties(1);

		foreach ( $properties as $key => $value )
		{
			if (($key!='id')&&($key!='state')&&($key!='collection')&&($key!='typeAlias'))
			{
				$this->history->$key = $this->$key;
				unset($this->$key);
			}
		}

		// Version en attente d'un objet existant : sa version de départ, pour ne reporter à la validation que
		// les champs réellement modifiés (voir rebasePendingVersion). Colonne absente avant la mise à jour de Collector.
		if (property_exists($this->history, 'base_version'))
		{
			$this->history->base_version = (!$auto_validate && $this->id && $baseVersion) ? $baseVersion : null;
		}
		if ($this->id) {
			if ($auto_validate) {
				// Version active immédiatement ? mettre à jour modified
				$this->modified    = $date->toSql();
				$this->modified_by = $user->get('id');
			}
		}
		
		unset($this->historyId);

		$isNewItem = empty($this->id);

		// Version en attente d'un objet existant : l'objet ne change pas avant la validation (date et auteur de
		// la modification, titre…) ; un nouvel objet est créé, dépublié tant que sa version n'est pas validée
		if (($auto_validate || $isNewItem) && !$this->store())
		{
			return false;
		}
		
		$this->history->id = $version;
		$this->history->state = $auto_validate;
		$this->history->item = $this->id;
		if ($auto_validate == 0) {
			$this->history->id = null;
		}
		
		if (!$this->history->store())
		{
			// Version refusée : l'erreur remonte, et un nouvel objet ne reste pas sans version
			$error = $this->history->getError() ?: Text::_('JLIB_APPLICATION_ERROR_SAVE_FAILED');

			if ($isNewItem)
			{
				// objet et asset créés juste avant
				$this->delete((int) $this->id);
			}

			$this->setError($error);

			return false;
		}
		
		if ($auto_validate == 0)
		{
			$query = $this->_db->getQuery(true)
				->update($this->_db->quoteName('#__collector_items'))
				->set($this->_db->quoteName('to_validate') . ' = GREATEST(0, ' . $this->_db->quoteName('to_validate') . ' + 1)')
				->where($this->_db->quoteName('id') . ' = ' . $this->id);
	 
			$this->_db->setQuery($query);
			$this->_db->execute();
		}
		
		return true;
	}
	
	/**
	 * function to delete a version of an item
	 *
	 * @access	public
	 * @param	int		$version	Version Id
	 */
	function deleteVersion($id)
	{
		$db      = $this->_db;
		$history = $db->quoteName('#__collector_items_history_' . (int) $this->collection);

		$version = $db->setQuery('SELECT item, state FROM ' . $history . ' WHERE id = ' . (int) $id)->loadObject();

		if (!$version)
		{
			return true;
		}

		// La version en cours ne se supprime pas : l'objet n'en aurait plus et disparaîtrait du site
		if ((int) $version->state === 1)
		{
			$this->setError(Text::_('COM_COLLECTOR_ITEM_HISTORY_DELETE_ACTIVE'));

			return false;
		}

		$db->setQuery('DELETE FROM ' . $history . ' WHERE id = ' . (int) $id)->execute();

		// Version en attente supprimée : une de moins à valider
		if ((int) $version->state === 0)
		{
			$this->decrementToValidate((int) $version->item);
		}

		return true;
	}

	/**
	 * Une version de l'objet (toutes ses colonnes), ou null
	 */
	protected function loadVersionRow(int $versionId): ?object
	{
		$db = $this->_db;

		return $db->setQuery(
			'SELECT * FROM ' . $db->quoteName('#__collector_items_history_' . (int) $this->collection) . ' WHERE id = ' . $versionId
		)->loadObject() ?: null;
	}

	/**
	 * Une version de moins à valider pour un objet
	 */
	protected function decrementToValidate(int $itemId): void
	{
		$db = $this->_db;

		$db->setQuery(
			$db->getQuery(true)
				->update($db->quoteName('#__collector_items'))
				->set($db->quoteName('to_validate') . ' = GREATEST(0, ' . $db->quoteName('to_validate') . ' - 1)')
				->where($db->quoteName('id') . ' = ' . $itemId)
		)->execute();
	}

	/**
	 * Colonnes de données d'une version : champs de la collection (avec leur CollectorField, qui sait
	 * comparer ses valeurs) et métadonnées (null)
	 *
	 * @return  array  [colonne => CollectorField|null]
	 */
	public function getVersionDataColumns(): array
	{
		$db    = $this->_db;
		$query = $db->getQuery(true)
			->select('f.id, f.field, f.tablecolumn, f.attribs, f.filter, t.type AS type')
			->from($db->quoteName('#__collector_fields', 'f'))
			->join('LEFT', $db->quoteName('#__collector_fields_type', 't') . ' ON t.id = f.type')
			->where('f.collection = ' . (int) $this->collection)
			->order('f.ordering');

		$columns = [];

		foreach ($db->setQuery($query)->loadObjectList() as $def)
		{
			$registry = new Registry;
			$registry->loadString($def->attribs ?? '');
			$def->attribs = $registry->toArray();

			$columns[$def->tablecolumn] = CollectorField::getInstance((int) $this->collection, $def);
		}

		foreach (['metakey', 'metadesc', 'metadata'] as $column)
		{
			$columns[$column] = null;
		}

		return $columns;
	}

	/**
	 * Version en attente rapportée sur la version en cours.
	 *
	 * Une version en attente est une copie complète de l'objet, prise sur sa version de départ (base_version).
	 * Si l'objet a changé depuis (autre validation, administration, import), les champs que son auteur n'a
	 * pas touchés reprennent la valeur en cours au lieu de revenir en arrière ; ceux qu'il a changés gardent
	 * sa valeur. Conflit : champ changé par l'auteur et aussi, autrement, depuis sa version de départ.
	 * Sans version de départ connue (proposition d'un nouvel objet, version plus ancienne) : rien n'est rapporté.
	 *
	 * @return  array  ['data' => [colonne => valeur brute], 'rebased' => colonnes reprises, 'conflicts' => colonnes en conflit]
	 */
	public function rebasePendingVersion(int $versionId): array
	{
		$result  = ['data' => [], 'rebased' => [], 'conflicts' => []];
		$pending = $this->loadVersionRow($versionId);

		if (!$pending)
		{
			return $result;
		}

		$columns = array_filter($this->getVersionDataColumns(), fn ($column) => property_exists($pending, $column), ARRAY_FILTER_USE_KEY);

		foreach ($columns as $column => $field)
		{
			$result['data'][$column] = $pending->$column;
		}

		$baseId = (int) ($pending->base_version ?? 0);

		if (!$baseId)
		{
			return $result;
		}

		$db     = $this->_db;
		$active = $db->setQuery(
			'SELECT * FROM ' . $db->quoteName('#__collector_items_history_' . (int) $this->collection)
			. ' WHERE item = ' . (int) $pending->item . ' AND state = 1'
		)->loadObject();
		$base   = $this->loadVersionRow($baseId);

		if (!$active || !$base || (int) $active->id === $baseId)
		{
			return $result;
		}

		foreach ($columns as $column => $field)
		{
			$normalize = fn ($value) => $field ? $field->normalizeForComparison((string) $value) : trim((string) $value);

			$p = $normalize($pending->$column);
			$b = $normalize($base->$column ?? '');
			$a = $normalize($active->$column ?? '');

			if ($p === $b)
			{
				// Champ non touché par l'auteur : valeur en cours
				if ($a !== $b)
				{
					$result['data'][$column] = $active->$column;
					$result['rebased'][]     = $column;
				}
			}
			elseif ($a !== $b && $a !== $p)
			{
				$result['conflicts'][] = $column;
			}
		}

		return $result;
	}

	/**
	 * Valide une version en attente : elle devient la version en cours.
	 *
	 * - seulement la plus ancienne version en attente de l'objet ;
	 * - rapportée sur la version en cours (voir rebasePendingVersion) ; sans $values, un conflit bloque la
	 *   validation (à faire depuis la comparaison, champ par champ) ;
	 * - $values : valeurs choisies champ par champ dans la comparaison [colonne => valeur brute] ;
	 * - titre et alias recalculés, date et auteur de la version reportés sur l'objet ;
	 * - objet sans version validée (proposition) : publié s'il était dépublié.
	 */
	public function validatePendingVersion(int $versionId, ?array $values = null): bool
	{
		$db      = $this->_db;
		$history = $db->quoteName('#__collector_items_history_' . (int) $this->collection);
		$version = $this->loadVersionRow($versionId);

		if (!$version || (int) $version->state !== 0)
		{
			$this->setError(Text::_('COM_COLLECTOR_ITEM_HISTORY_NOT_PENDING'));

			return false;
		}

		$itemId = (int) $version->item;

		if ((int) $this->id !== $itemId)
		{
			$this->load($itemId);
		}

		$oldestPending = (int) $db->setQuery('SELECT MIN(id) FROM ' . $history . ' WHERE item = ' . $itemId . ' AND state = 0')->loadResult();

		if ($oldestPending !== $versionId)
		{
			$this->setError(Text::_('COM_COLLECTOR_ITEM_HISTORY_VALIDATE_ORDER_ERROR'));

			return false;
		}

		$firstVersion = !(int) $db->setQuery('SELECT COUNT(*) FROM ' . $history . ' WHERE item = ' . $itemId . ' AND state = 1')->loadResult();

		$rebase = $this->rebasePendingVersion($versionId);

		if ($values === null && $rebase['conflicts'])
		{
			$columns = $this->getVersionDataColumns();
			$names   = array_map(fn ($column) => $columns[$column] ? $columns[$column]->getFieldName() : $column, $rebase['conflicts']);
			$this->setError(Text::sprintf('COM_COLLECTOR_ITEM_HISTORY_VALIDATE_CONFLICT', implode(', ', $names)));

			return false;
		}

		$data = $rebase['data'];

		foreach ((array) $values as $column => $value)
		{
			if (array_key_exists($column, $data))
			{
				$data[$column] = $value;
			}
		}

		// Valeurs retenues enregistrées dans la version, qui devient la version en cours
		if ($data)
		{
			$query = $db->getQuery(true)
				->update($history)
				->where('id = ' . $versionId);

			foreach ($data as $column => $value)
			{
				$query->set($db->quoteName($column) . ' = ' . ($value === null ? 'NULL' : $db->quote((string) $value)));
			}

			$db->setQuery($query)->execute();
		}

		if (!$this->activateVersion($versionId))
		{
			return false;
		}

		$db->setQuery(
			$db->getQuery(true)
				->update($db->quoteName('#__collector_items'))
				->set($db->quoteName('modified_by') . ' = ' . (int) $version->modified_by)
				->set($db->quoteName('to_validate') . ' = GREATEST(0, ' . $db->quoteName('to_validate') . ' - 1)')
				->where($db->quoteName('id') . ' = ' . $itemId)
		)->execute();

		// Objet sans version validée (proposition) : la validation le publie
		if ($firstVersion)
		{
			$db->setQuery(
				$db->getQuery(true)
					->update($db->quoteName('#__collector_items'))
					->set($db->quoteName('state') . ' = 1')
					->where($db->quoteName('id') . ' = ' . $itemId)
					->where($db->quoteName('state') . ' = 0')
			)->execute();
		}

		// Sans historique : l'ancienne version en cours n'est pas gardée
		if (!ComponentHelper::getParams('com_collector')->get('save_history', 1))
		{
			$db->setQuery('DELETE FROM ' . $history . ' WHERE item = ' . $itemId . ' AND state = 2')->execute();
		}

		return true;
	}

	/**
	 * Refuse une version en attente : état « refusée » (3) avec la note de refus ; elle ne peut pas être rétablie
	 */
	public function refuseVersion(int $versionId, string $note = ''): bool
	{
		$db      = $this->_db;
		$version = $this->loadVersionRow($versionId);

		if (!$version || (int) $version->state !== 0)
		{
			$this->setError(Text::_('COM_COLLECTOR_ITEM_HISTORY_NOT_PENDING'));

			return false;
		}

		$db->setQuery(
			$db->getQuery(true)
				->update($db->quoteName('#__collector_items_history_' . (int) $this->collection))
				->set($db->quoteName('state') . ' = 3')
				->set($db->quoteName('refuse_note') . ' = ' . $db->quote($note))
				->where('id = ' . $versionId)
		)->execute();

		$this->decrementToValidate((int) $version->item);

		return true;
	}

	/**
	 * Rétablit une ancienne version (archivée) : elle redevient la version en cours
	 */
	public function restoreVersion(int $versionId): bool
	{
		$version = $this->loadVersionRow($versionId);

		if (!$version)
		{
			$this->setError(Text::_('COM_COLLECTOR_ITEM_HISTORY_VERSION_NOT_FOUND'));

			return false;
		}

		switch ((int) $version->state)
		{
			case 1:
				$this->setError(Text::_('COM_COLLECTOR_ITEM_HISTORY_ALREADY_ACTIVE'));

				return false;

			case 0:
				$this->setError(Text::_('COM_COLLECTOR_ITEM_HISTORY_TO_BE_CHECKED_UNRESTORED'));

				return false;

			case 3:
				$this->setError(Text::_('COM_COLLECTOR_ITEM_HISTORY_REFUSED_UNRESTORED'));

				return false;
		}

		if ((int) $this->id !== (int) $version->item)
		{
			$this->load((int) $version->item);
		}

		return $this->activateVersion($versionId);
	}
	
	/**
	 * function to activate a version of an item
	 *
	 * @access	public
	 * @param	int		$version	Version Id
	 */
	function activateVersion($id)
	{
		$db = $this->_db;

		// Désactiver la version courante
		$db->setQuery(
			'UPDATE ' . $db->quoteName('#__collector_items_history_' . $this->collection)
			. ' SET state = 2 WHERE item = ' . (int) $this->id . ' AND state = 1'
		)->execute();

		// Activer la version restaurée
		$db->setQuery(
			'UPDATE ' . $db->quoteName('#__collector_items_history_' . $this->collection)
			. ' SET state = 1 WHERE id = ' . (int) $id
		)->execute();

		// Charger les données de la version restaurée (dont modified)
		$this->loadVersion($this->collection, $this->id, $id);

		// Recalculer fulltitle et alias
		$this->check();

		// Mettre à jour #__collector_items avec fulltitle, alias
		// et modified = date de la version restaurée (pas la date courante)
		$db->setQuery(
			$db->getQuery(true)
			   ->update($db->quoteName('#__collector_items'))
			   ->set($db->quoteName('fulltitle') . ' = ' . $db->quote($this->fulltitle))
			   ->set($db->quoteName('alias')     . ' = ' . $db->quote($this->alias))
			   ->set($db->quoteName('modified')  . ' = ' . $db->quote($this->modified ?? '0000-00-00 00:00:00'))
			   ->where($db->quoteName('id')      . ' = ' . (int) $this->id)
		)->execute();

		return true;
	}
	
	/**
	 * Overloaded check function
	 *
	 * @access	public
	 * @return	boolean	True if the object is ok
	 * @see		JTable::check
	 */
	public function check()
	{
		$db = Factory::getContainer()->get(DatabaseInterface::class);
		
		// Check custom title
		$query = 'SELECT custom FROM `#__collector` WHERE id ="'.$this->collection.'"';
		$db->setQuery($query);
		$customTitle = $db->loadResult();
		
		// Select the required fields from the table.
		$query = $db->getQuery(true);
		$query->select('f.id, f.field, f.tablecolumn, f.attribs');
		$query->from('#__collector_fields as f');
		
		// Join over the type.
		$query->select('t.type AS type');
		$query->join('LEFT', '#__collector_fields_type AS t ON t.id = f.type');
		
		// Filter by collection
		$query->where('f.collection = '.$this->collection);
		
		// Filter by title
		if ( $customTitle != '0' )
		{
			$custom = explode('/',$customTitle);
			$query->where('f.id IN("'.implode('","',$custom).'")');
		}
		else
		{
			$queryHome = $db->getQuery(true);
			$queryHome->select('id')
				->from('`#__collector_fields`')
				->where('collection = '.$this->collection.' AND home = 1');
			
			$db->setQuery( $queryHome );
			$row = $db->loadResult();
			
			if ( !$row )
			{
				/**
				 * tag à defaut d'un champ
				 */
				$queryHome = 'UPDATE `#__collector_fields` SET home = 1  WHERE collection = '.$this->collection.' LIMIT 1;';
				$db->setQuery( $queryHome );
				$db->execute();
				
			}
			$query->where('home = 1');
		}
		
		$db->setQuery( $query );
		
		$fields = $db->loadObjectList();
		
		// Reorder
		$fieldsReordered = $fields;
		if ( $customTitle != '0' )
		{
			$custom = array_flip($custom);
			foreach( $fields as $field )
			{
				$fieldsReordered[$custom[$field->id]] = $field;
			}
		}
		
		$fieldsTitle = array();
		
		foreach ($fieldsReordered as $field)
		{
			$registry = new Registry;
			$registry->loadString($field->attribs);
			$field->attribs = $registry->toArray();
			$fieldsTitle[] = CollectorField::getInstance( $this->collection, $field );
		}
		
		$this->fulltitle = '';
		
		foreach ( $fieldsTitle as $field )
		{
			if ( $this->fulltitle != '' )
			{
				$this->fulltitle .= ' ';
			}
			$nameField = $field->_field->tablecolumn;
		
			$this->fulltitle .= $field->rebuild($this->$nameField);
		}
		
		$this->alias = ApplicationHelper::stringURLSafe($this->fulltitle);
		
		// Set publish_up to null if not set
        // "0000-00-00 00:00:00" (old items): no date, never compared below (swap would unpublish)
        if (!$this->publish_up || str_starts_with((string) $this->publish_up, "0000")) {
            $this->publish_up = null;
        }

        // Set publish_down to null if not set
        if (!$this->publish_down || str_starts_with((string) $this->publish_down, "0000")) {
            $this->publish_down = null;
        }
		
		// Check the publish down date is not earlier than publish up.
		if (!is_null($this->publish_up) && !is_null($this->publish_down) && $this->publish_down < $this->publish_up) {
            // Swap the dates.
            $temp               = $this->publish_up;
            $this->publish_up   = $this->publish_down;
            $this->publish_down = $temp;
        }
		
		// Set ordering
		if ($this->state < 0) {
			// Set ordering to 0 if state is archived or trashed
			$this->ordering = 0;
		} else if (empty($this->ordering)) {
			// Set ordering to last if ordering was 0
			$this->ordering = self::getNextOrder('`collection`=' . $this->_db->Quote($this->collection).' AND state>=0');
		}
		
		// Clean up keywords -- eliminate extra spaces between phrases
		// and cr (\r) and lf (\n) characters from string
		if (!empty($this->metakey)) {
            // Only process if not empty

            // Array of characters to remove
            $badCharacters = ["\n", "\r", "\"", '<', '>'];

            // Remove bad characters
            $afterClean = StringHelper::str_ireplace($badCharacters, '', $this->metakey);

            // Create array using commas as delimiter
            $keys = explode(',', $afterClean);

            $cleanKeys = [];

            foreach ($keys as $key) {
                if (trim($key)) {
                    // Ignore blank keywords
                    $cleanKeys[] = trim($key);
                }
            }

            // Put array back together delimited by ", "
            $this->metakey = implode(', ', $cleanKeys);
        } else {
            $this->metakey = '';
        }
		
		if ($this->metadesc === null) {
            $this->metadesc = '';
        }
		
		if ($this->metadata === null) {
            $this->metadata = '';
        }
		
		return true;
	}
	
	/**
	 * Overloaded store function
	 *
	 * @access	public
	 * @param	boolean If false, null object variables are not updated
	 * @return	null|stringnull if successful otherwise returns and error message
	 * @see		JTable::store
	 */
	public function store($updateNulls=true)
	{
		$date	= Factory::getDate();
		$user	= Factory::getApplication()->getIdentity();
		
		if ($this->id) {
			// Existing item
			$this->modified		= $date->toSql();
			$this->modified_by	= $user->get('id');
		} else {
			// New field. A field created and created_by field can be set by the user,
			// so we don't touch either of these if they are set.
			if (!intval($this->created)) {
				$this->created = $date->toSql();
			}

			if (empty($this->created_by)) {
				$this->created_by = $user->get('id');
			}
		}
		
		return parent::store($updateNulls);
	}
	
	/**
	 * Overloaded delete function
	 *
	 * @access	public
	 * @param	int		$pk		Item Id
	 * @return	boolean		True if the item is deleted
	 * @see		JTable::delete
	 */
	function delete($pk = null)
	{
		$query = 'DELETE FROM `#__collector_items_history_'.$this->collection.'` WHERE `item` = "' . $pk . '";';
		
		$this->_db->setQuery( $query );
		$this->_db->execute();
		
		/* delete comments */
		$jcomments = JPATH_SITE . DIRECTORY_SEPARATOR .'components' . DIRECTORY_SEPARATOR . 'com_jcomments' . DIRECTORY_SEPARATOR . 'jcomments.php';
		if (file_exists($jcomments)) {
			require_once($jcomments);
			\JCommentsModel::deleteComments($pk, 'com_collector');
		}

		return parent::delete($pk);
	}
	
	/**
     * Method to set the publishing state for a row or list of rows in the database
     * table.  The method respects checked out rows by other users and will attempt
     * to checkin rows that it can after adjustments are made.
     *
     * @param    mixed    An optional array of primary key values to update.  If not
     *                     set the instance property value is used.
     * @param    integer The publishing state. eg. [0 = unpublished, 1 = published]
     * @param    integer The user id of the user performing the operation.
     *
     * @return    boolean    True on success.
     */
    public function publish($pks = null, $state = 1, $userId = 0)
    {
        // Initialise variables.
        $k = $this->_tbl_key;
 
        // Sanitize input.
        $pks = ArrayHelper::toInteger($pks);
        $userId = (int) $userId;
        $state  = (int) $state;
 
        // If there are no primary keys set check to see if the instance key is set.
        if (empty($pks)) {
            if ($this->$k) {
                $pks = array($this->$k);
            }
            // Nothing to set publishing state on, return false.
            else {
                $this->setError(Text::_('JLIB_DATABASE_ERROR_NO_ROWS_SELECTED'));
                return false;
            }
        }
 
        // Build the WHERE clause for the primary keys.
        $where = $k.'='.implode(' OR '.$k.'=', $pks);
 
        // Determine if there is checkin support for the table.
        if (!property_exists($this, 'checked_out') || !property_exists($this, 'checked_out_time')) {
            $checkin = '';
        } else {
            $checkin = ' AND (checked_out = 0 OR checked_out = '.(int) $userId.')';
        }
 
        // Update the publishing state for rows with the given primary keys.
        $this->_db->setQuery(
            'UPDATE `'.$this->_tbl.'`' .
            ' SET `state` = '.(int) $state .
            ' WHERE ('.$where.')' .
            $checkin
        );
		
		try {
			$this->_db->execute();
		} catch (\RuntimeException $e) {
			$this->setError($e->getMessage());

			return false;
		}
 
        // If checkin is supported and all rows were adjusted, check them in.
        if ($checkin && (count($pks) == $this->_db->getAffectedRows())) {
            // Checkin the rows.
            foreach($pks as $pk) {
                $this->checkin($pk);
            }
        }
 
        // If the JTable instance value is in the list of primary keys that were set, set the instance.
        if (in_array($this->$k, $pks)) {
            $this->state = $state;
        }
 
        $this->setError('');
 
        return true;
    }
	
	/**
     * Method to set the publishing state for a row or list of rows in the database
     * table.  The method respects checked out rows by other users and will attempt
     * to checkin rows that it can after adjustments are made.
     *
     * @param    integer	A primary key value to copy.
     * @param    integer	The old collection Id
     * @param    integer	The new collection Id
     * @param    string		Copy mode ( 'LAST', 'FULL' )
     *
     * @return   boolean    True on success.
     */
    public function copy($pk, $old_collection, $new_collection, $mode)
    {
		$db = Factory::getContainer()->get(DatabaseInterface::class);

		$query = "SELECT id";
		$query .= " FROM `#__collector_items_history_".$old_collection."`";
		$query .= " WHERE item = ".$pk;
		
        if ($mode == 'LAST') {
			$query .= " AND state = 1";
		}
		
		$db->setQuery( $query );
		$db->execute();
		$remaining_history = $db->getNumRows();
		
		if ($remaining_history != 0)
		{
			$new_id = 0;

			$query = "SELECT * FROM `#__collector_items_history_".$old_collection."`";
			$query .= " WHERE item = ".$pk;
			if ($mode == 'LAST') {
				$query .= " AND state = 1";
			}
			$query .= " ORDER BY modified ASC";
			
			$db->setQuery( $query );
			$histories = $db->loadObjectList();
				
			foreach ($histories AS $history)
			{
				$this->loadCopyVersion( $old_collection, $pk, $history->id );
				
				$name = $this->_getAssetName();
				$asset = new Asset($this->getDbo());
				$asset->loadByName($name);
				
				$rules = new Rules($asset->rules);
				$this->setRules($rules);
				
				$this->id = $new_id;
				$this->asset_id = "";
				$this->alias = "";
				$this->collection = $new_collection;
				
				$this->checkVersion();
				$this->storeVersion();
				$new_id = $this->id;
			}
		}
		
        return true;
    }
	
	/**
	 * Method to compute the default name of the asset.
	 * The default name is in the form `table_name.id`
	 * where id is the value of the primary key of the table.
	 *
	 * @return	string
	 * @since	1.6
	 */
	protected function _getAssetName()
	{
		$k = $this->_tbl_key;
		return 'com_collector.item.'.(int) $this->$k;
	}
 
	/**
	 * Method to return the title to use for the asset table.
	 *
	 * @return	string
	 * @since	1.6
	 */
	protected function _getAssetTitle()
	{
		return $this->id;
	}
 
	/**
	 * Get the parent asset id for the record
	 *
	 * @return	int
	 * @since	1.6
	 */
	protected function _getAssetParentId(?Table $table = null, $id = null)
	{
		$asset = new Asset($this->getDbo());
		$asset->loadByName('com_collector.collection.'.(int) $this->collection);
		return $asset->id;
	}
}