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

/**
 * Collector_fields table
 * @package		Collector
 */
class CollectorFieldsTable extends Table
{
	/**
	 * @param database A database connector object
	 */
	function __construct(DatabaseDriver $db)
	{
		parent::__construct( '#__collector_fields', 'id', $db );
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
	 * Overloaded bind function
	 *
	 * @access	public
	 * @return	boolean	True if the object is ok
	 * @see		JTable::bind
	 */
	public function bind($src, $ignore = array())
	{
		$db = $this->getDbo();
		$query = 'SELECT * FROM #__collector_fields_type WHERE id='.$src['type'];
		$db->setQuery( $query );
		$type = $db->loadObject();
		
		if (isset($src['attribs-'.$type->type]) && is_array($src['attribs-'.$type->type])) {
			$registry = new Registry;
			$registry->loadArray($src['attribs-'.$type->type]);
			$src['attribs'] = (string)$registry;
		}
		
		return parent::bind($src, $ignore = array());
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
		// check for valid name
		if( trim($this->field) == '' ) {
			$this->setError(Text::_( 'COM_COLLECTOR_WARNING_PROVIDE_VALID_NAME' ));
			return false;
		}
		
		// check for valid table column
		if(empty($this->tablecolumn))
		{
			$this->tablecolumn = strtolower(preg_replace('/[^A-Za-z0-9_]/', '', $this->field));
		}
		
		// Check tablecolumn
		$db = Factory::getDBO();
		$queryTablecolumn = $db->getQuery(true);
		$queryTablecolumn->select('id')
			->from('`#__collector_fields`')
			->where('collection = '.$this->collection.' AND tablecolumn = "'.$this->tablecolumn.'"');
		
		$db->setQuery( $queryTablecolumn );
		$row = $db->loadResult();
		
		if ( $row && ($row->id == $this->id) )
		{
			$this->setError(Text::_('COM_COLLECTOR_DATABASE_ERROR_FIELD_UNIQUE_TABLECOLUMN'));
			return false;
		}
		
		// Check home
		$db = Factory::getDBO();
		$queryHome = $db->getQuery(true);
		$queryHome->select('id')
			->from('`#__collector_fields`')
			->where('collection = '.$this->collection.' AND home = 1');
		
		$db->setQuery( $queryHome );
		$row = $db->loadResult();

		if ( !$row )
		{
			$queryHome = $db->getQuery(true);
			$queryHome->select('intitle')
				->from('`#__collector_fields_type`')
				->where('id = '.$this->type);
			
			$db->setQuery( $queryHome );
			
			if ($intitle = $db->loadResult())
			{
				$this->home = 1;
			}
		}
		
		// Set publish_up to null if not set
        if (!$this->publish_up) {
            $this->publish_up = null;
        }

        // Set publish_down to null if not set
        if (!$this->publish_down) {
            $this->publish_down = null;
        }
		
		// Check the publish down date is not earlier than publish up.
		if (!\is_null($this->publish_up) && !\is_null($this->publish_down) && $this->publish_down < $this->publish_up) {
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
	public function store($updateNulls=false)
	{
		$date	= Factory::getDate()->toSql();
		$user	= Factory::getApplication()->getIdentity();
		
		if ($this->id) {
			// Existing item
			$this->modified		= $date;
			$this->modified_by	= $user->get('id');
		} else {
			// New field. A field created and created_by field can be set by the user,
			// so we don't touch either of these if they are set.
			if (!intval($this->created)) {
				$this->created = $date;
			}

			if (empty($this->created_by)) {
				$this->created_by = $user->get('id');
			}
		}
		
		// Verify that the tablecolumn is unique
		// $table = self::getInstance('Collector_fields','Table');
		// if ($table->load(array('tablecolumn'=>$this->tablecolumn,'collection'=>$this->collection)) && ($table->id != $this->id || $this->id==0)) {
			// $this->setError(Text::_('COM_COLLECTOR_DATABASE_ERROR_FIELD_UNIQUE_TABLECOLUMN'));
			// return false;
		// }
		
		$k = $this->_tbl_key;
		if( empty($this->$k) )
        {
			$collection = $this->collection;
			$column = $this->tablecolumn;
			
			$query = "ALTER TABLE `#__collector_items_history_".$collection."` ADD `".$column."` TEXT NOT NULL";
			$this->_db->setQuery( $query );
			$this->_db->execute();
        }
		
		return parent::store($updateNulls);
	}
	
	/**
	 * Overloaded delete function
	 *
	 * @access	public
	 * @return	true if successful otherwise returns and error message
	 * @see		JTable::delete
	 */
	function delete( $oid=null )
	{
		if ($oid)
		{
			$this->load($oid);
			$collection = $this->collection;
			$column = $this->tablecolumn;
			
			$query = 'ALTER TABLE `#__collector_items_history_'.$collection.'` DROP `'.$column.'`';
			$this->_db->setQuery( $query );
			$this->_db->execute();
		}
		parent::delete($oid);
		
		$this->reorder('collection = '.$collection);
		return true;
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
        ArrayHelper::toInteger($pks);
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
 
		// Check home
		$db = Factory::getDBO();
		$queryHome = $db->getQuery(true);
		$queryHome->select('id')
			->from('`#__collector_fields`')
			->where('collection = '.$this->collection.' AND home = 1 AND state IN (0,1)');
		
		$db->setQuery( $queryHome );
		$row = $db->loadResult();
		
		if ( !$row )
		{
			/**
			 * tag à defaut d'un champ non archivé et non corbeille
			 */
			$queryHome = 'UPDATE `#__collector_fields` AS f LEFT JOIN `#__collector_fields_type` AS t ON f.type = t.id SET home = 0 WHERE t.intitle = 1 AND f.collection = '.$this->collection.';';
			$db->setQuery( $queryHome );
			$db->execute();
			
			
			$queryHome = 'SELECT f.id FROM `#__collector_fields` AS f LEFT JOIN `#__collector_fields_type` AS t ON f.type = t.id WHERE t.intitle = 1 AND f.collection = '.$this->collection.' AND f.state IN (0,1) LIMIT 0,1;';
			$db->setQuery( $queryHome );
			$result = $db->loadResult();
			if ( $result ) {
				$queryHome = 'UPDATE `#__collector_fields` SET home = 1 WHERE id = '.$result.';';
				$db->setQuery( $queryHome );
				$db->execute();
			}
		}
		
        return true;
    }
	
	/**
     * Method to copy one collection
     *
     * @param    integer	A primary key value to copy.
     * @param    string		The name for the new collection
     * @param    integer	Copy mode (	1 only fields,
	 *									2 fields and items without history,
	 *									3 fields and items with history)
     *
     * @return    boolean    True on success.
     */
    public function copy($pk, $collection)
    {
        $db = Factory::getDBO();
		
		$this->load($pk);
		
		$name = $this->_getAssetName();
		$asset = self::getInstance('Asset', 'Table', array('dbo' => $this->getDbo()));
		$asset->loadByName($name);
		
		$rules = new JAccessRules($asset->rules);
		$this->setRules($rules);
		
		$this->id = 0;
		$this->asset_id = "";
		$this->collection = $collection;
		
		$this->check();
		$this->store();
		
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
		return 'com_collector.field.'.(int) $this->$k;
	}
 
	/**
	 * Method to return the title to use for the asset table.
	 *
	 * @return	string
	 * @since	1.6
	 */
	protected function _getAssetTitle()
	{
		return $this->field;
	}
 
	/**
	 * Get the parent asset id for the record
	 *
	 * @return	int
	 * @since	1.6
	 */
	protected function _getAssetParentId(?Table $table = null, $id = null)
	{
		$asset = self::getInstance('Asset');
		$asset->loadByName('com_collector.collection.'.(int) $this->collection);
		return $asset->id;
	}
}