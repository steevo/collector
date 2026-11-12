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
use Joomla\CMS\Factory;
use Joomla\CMS\Filter\InputFilter;
use Joomla\CMS\Language\Text;
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

/**
 * Collector_items_history_ table
 * @package		Collector
 */
class CollectorItemsHistoryTable extends Table
{
	/**
     * @var string
     */
    public $typeAlias = 'com_collector.itemhistory';
	
	/**
	 * @param database A database connector object
	 */
	function __construct( DatabaseDriver $db , $collection )
	{
		$this->tableExist($collection);
		parent::__construct( '#__collector_items_history_'.$collection , 'id', $db );
	}
	
	public static function getInstance($type, $prefix = 'Table', $config = array())
	{
		// If a database object was passed in the configuration array use it, otherwise get the global one from Factory.
		$db = isset($config['dbo']) ? $config['dbo'] : Factory::getContainer()->get(DatabaseInterface::class);
		
		$collection = $config['collection'];
		
		// Instantiate a new table class and return it.
		return new CollectorItemsHistoryTable($db,$collection);
    }
	
	/**
	 * function to create a new table for a new collection
	 *
	 * @access	public
	 * @param	int		$collection	Collection Id
	 */
	private function tableExist($collection)
	{
		$query = 'CREATE TABLE IF NOT EXISTS `#__collector_items_history_'.$collection.'` (';
		$query .= '`id` int(11) NOT NULL auto_increment,';
		$query .= '`item` int(11) NOT NULL,';
		$query .= '`state` tinyint(3) NOT NULL default "0",';
		$query .= '`modified` datetime NOT NULL default "0000-00-00 00:00:00",';
		$query .= '`modified_by` int(11) unsigned NOT NULL default "0",';
		$query .= '`metakey` text NOT NULL,';
		$query .= '`metadesc` text NOT NULL,';
		$query .= '`metadata` text NOT NULL,';
		$query .= '`modification` text NOT NULL,';
		$query .= '`refuse_note` text DEFAULT NULL,';
		$query .= 'PRIMARY KEY (`id`),';
		$query .= 'KEY `idx_item` (`item`),';
		$query .= 'KEY `idx_modifiedby` (`modified_by`)';
		$query .= ') ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 DEFAULT COLLATE = utf8mb4_unicode_ci;';
		
		$db = Factory::getContainer()->get(DatabaseInterface::class);
		$db->setQuery( $query );
		$db->execute();
		
		return true;
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
		$this->_collection = $collection;
		$this->_tbl = '#__collector_items_history_'.$collection;
		
		// recuperation des champs de la collection
		$query = 'SELECT tablecolumn FROM `#__collector_fields`';
		$query .= ' WHERE collection = ' . $collection;
		$query .= ' ORDER BY ordering';
		
		$this->_db->setQuery($query);
		
		$rows = $this->_db->loadColumn();
		
		for ($i=0, $n=count($rows);$i<$n;$i++)
		{
			$field = $rows[$i];
			
			$this->$field = null;
		}
		
		$this->metadesc = '';
        
		$this->metadata = '';
	}
	
	/**
	 * function to load a version of an item
	 *
	 * @access	public
	 * @param	int		$collection	Collection Id
	 * @param	int		$id			Item Id
	 * @param	int		$version	Version Id
	 */
	function loadVersion( $collection, $id, $copy = false, $version = null )
	{
		// Si pas de version, alors version courante
		if ( $version == null )
		{
			$query = 'SELECT id';
			$query .= ' FROM `'.$this->_tbl.'`';
			$query .= ' WHERE item = \'' . $id . '\'';
			$query .= ' AND state = \'1\'';
			
			$this->_db->setQuery($query);
			
			$version = $this->_db->loadResult();
		}
		
		// recuperation des donnees de la version
		$this->load($version);
		
		if ($copy == true)
		{
			$this->id = null;
		}
	}
	
	/**
	 * Overloaded store function
	 *
	 * @access	public
	 * @return	null|string	null if successful otherwise returns and error message
	 * @see		JTable::store
	 */
	function store($updateNulls = false)
	{
		$date	= Factory::getDate();
		$user	= Factory::getApplication()->getIdentity();
		
		if ($this->state == '1') {
			$query = 'UPDATE `'.$this->_tbl.'`';
			$query .= ' SET state = "2"';
			$query .= ' WHERE item = ' . $this->item . ' AND state = "1"';
			
			$this->_db->setQuery($query);
			$this->_db->execute();
		}
		
		
		$this->modified		= $date->toSql();
		$this->modified_by	= $user->get('id');

		// Nouvelle version : une colonne obligatoire sans valeur par défaut que le formulaire n'a pas
		// envoyée (champ désactivé, case décochée…) est enregistrée vide, sinon MySQL refuse la ligne
		if (empty($this->id)) {
			foreach ($this->_db->getTableColumns($this->_tbl, false) as $name => $column) {
				if ($name !== 'id' && $column->Null === 'NO' && $column->Default === null && !isset($this->$name)) {
					$this->$name = '';
				}
			}
		}

		return parent::store($updateNulls);
	}
	
	/**
	 * Overloaded delete function
	 *
	 * @access	public
	 * @param	int		$pk		Version Id
	 * @return	boolean		True if the version is deleted
	 * @see		JTable::delete
	 */
	function delete($pk = null)
	{
		return parent::delete($pk);
	}
	
	/**
	 * Overloaded check function
	 *
	 * @access	public
	 * @return	boolean	True if the object is ok
	 * @see		JTable::check
	 */
	function check()
	{
		// check for valid name
		// if( trim($this->name) == '' ) {
			// $this->setError(Text::_( 'COM_COLLECTOR_COL_NAME' ));
			// return false;
		// }
		// check for existing username
		
		// cast item Id
		$this->item = (int)($this->item);
		
		return true;
	}
}