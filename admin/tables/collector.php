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

// Check to ensure this file is included in Joomla!
defined('_JEXEC') or die( 'Restricted access' );

/**
 * Collector table
 * @package		Collector
 */
class TableCollector extends JTable
{
	/**
	 * @param database A database connector object
	 */
	function __construct( &$db )
	{
		parent::__construct( '#__collector', 'id', $db );
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
			$registry = new JRegistry;
			$registry->loadArray($array['metadata']);
			$array['metadata'] = (string)$registry;
		}

		// Bind the rules.
		if (isset($array['rules']) && is_array($array['rules'])) {
			$rules = new JAccessRules($array['rules']);
			$this->setRules($rules);
		}
		
		return parent::bind($array, $ignore);
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
		if (trim($this->name) == '') {
			$this->setError(JText::_('COM_COLLECTOR_WARNING_PROVIDE_VALID_NAME'));
			return false;
		}

		if (trim($this->alias) == '') {
			$this->alias = $this->name;
		}

		$this->alias = JApplication::stringURLSafe($this->alias);

		if (trim(str_replace('-','',$this->alias)) == '') {
			$this->alias = JFactory::getDate()->format('Y-m-d-H-i-s');
		}
		
		// Check home
		$db = JFactory::getDBO();
		$queryHome = $db->getQuery(true);
		$queryHome->select('id')
			->from('`#__collector`')
			->where('home = 1');
		
		$db->setQuery( $queryHome );
		$row = $db->loadResult();
		
		if ( !$row )
		{
			$this->home = 1;
			
		}
		
		// Check the publish down date is not earlier than publish up.
		if ($this->publish_down > $this->_db->getNullDate() && $this->publish_down < $this->publish_up) {
			// Swap the dates.
			$temp = $this->publish_up;
			$this->publish_up = $this->publish_down;
			$this->publish_down = $temp;
		}

		// Clean up keywords -- eliminate extra spaces between phrases
		// and cr (\r) and lf (\n) characters from string
		if (!empty($this->metakey)) {
			// Only process if not empty
			$bad_characters = array("\n", "\r", "\"", "<", ">"); // array of characters to remove
			$after_clean = JString::str_ireplace($bad_characters, "", $this->metakey); // remove bad characters
			$keys = explode(',', $after_clean); // create array using commas as delimiter
			$clean_keys = array();

			foreach($keys as $key) {
				if (trim($key)) {
					// Ignore blank keywords
					$clean_keys[] = trim($key);
				}
			}
			$this->metakey = implode(", ", $clean_keys); // put array back together delimited by ", "
		}
		
		return true;
	}
	
	/**
	 * Overrides JTable::store to set modified data and user id.
	 *
	 * @param   boolean  True to update fields even if they are null.
	 *
	 * @return  boolean  True on success.
	 */
	public function store($updateNulls = false)
	{
		$date	= JFactory::getDate();
		$user	= JFactory::getUser();

		if ($this->id) {
			// Existing item
			$this->modified		= $date->toSql();
			$this->modified_by	= $user->get('id');
		} else {
			// New collection. A collection created and created_by field can be set by the user,
			// so we don't touch either of these if they are set.
			if (!intval($this->created)) {
				$this->created = $date->toSql();
			}

			if (!intval($this->publish_up)) {
				$this->publish_up = $this->created;
			}

			if (empty($this->created_by)) {
				$this->created_by = $user->get('id');
			}
		}
		
		$store = parent::store($updateNulls);
		
		// Create history table
		// Import the class file.
		require_once(JPATH_ROOT.'/administrator/components/com_collector/tables/collector_items_history_.php');
		// if (!$table = TableCollector_items_history_::getInstance($this->id)) {
		if (!$tablehistory = TableCollector_items_history_::getInstance('Collector_items_history_', 'Table', array('collection'=>$this->id))) {
			$this->setError(JText::_('COM_COLLECTOR_DATABASE_ERROR_CREATE_HISTORY_TABLE'));
			return false;
		}
		
		// Create userlists
		// Import the class file.
		require_once(JPATH_ROOT.'/administrator/components/com_collector/tables/collector_userslists.php');
		if (!$tablelists = TableCollector_userslists::getInstance('collector_userslists', 'Table')) {
			$this->setError(JText::_('COM_COLLECTOR_DATABASE_ERROR_CREATE_USERS_LISTS'));
			return false;
		} else {
			$tablelists->initLists($this->id);
		}
		
		return $store;
	}
	
	/**
	 * Overloaded delete function
	 *
	 * @access	public
	 * @return	boolean	True if the object is ok
	 * @see		JTable::delete
	 */
	public function delete($pk = null)
	{
		if ($pk)
		{
			$query = 'DELETE FROM `#__collector_fields` WHERE collection = '.$pk;
			$this->_db->setQuery( $query );
			$this->_db->execute();
			
			$query = 'DELETE FROM `#__collector_items` WHERE collection = '.$pk;
			$this->_db->setQuery( $query );
			$this->_db->execute();
			
			$query = 'DROP TABLE `#__collector_items_history_'.$pk.'`';
			$this->_db->setQuery( $query );
			$this->_db->execute();
			
			$query = 'DELETE FROM `#__collector_userslists` WHERE collection = '.$pk;
			$this->_db->setQuery( $query );
			$this->_db->execute();
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
        JArrayHelper::toInteger($pks);
        $userId = (int) $userId;
        $state  = (int) $state;
 
        // If there are no primary keys set check to see if the instance key is set.
        if (empty($pks)) {
            if ($this->$k) {
                $pks = array($this->$k);
            }
            // Nothing to set publishing state on, return false.
            else {
                $this->setError(JText::_('JLIB_DATABASE_ERROR_NO_ROWS_SELECTED'));
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
        $this->_db->execute();
 
        // Check for a database error.
        if ($this->_db->getErrorNum()) {
            $this->setError($this->_db->getErrorMsg());
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
		$db = JFactory::getDBO();
		$queryHome = $db->getQuery(true);
		$queryHome->select('id')
			->from('`#__collector`')
			->where('home = 1 AND state IN (0,1)');
		
		$db->setQuery( $queryHome );
		$row = $db->loadResult();
		
		if ( !$row )
		{
			/**
			 * tag à defaut d'une collection non archivé ou non corbeille
			 */
			$queryHome = 'UPDATE `#__collector` SET home = 0;';
			$db->setQuery( $queryHome );
			$db->execute();
			
			$queryHome = 'UPDATE `#__collector` SET home = 1 WHERE state IN (0,1) LIMIT 1;';
			$db->setQuery( $queryHome );
			$db->execute();
		}
		
        return true;
    }
	
	/**
     * Method to copy one collection
     *
     * @param    integer	A primary key value to copy.
     * @param    integer	Copy mode (	1 only fields,
	 *									2 fields and items without history,
	 *									3 fields and items with history)
     * @param    integer	The Asset Group ID for the new collection
     * @param    integer	A primary key value destination.
     *
     * @return    boolean    True on success.
     */
    public function copy($pk, $mode = 1, $assetgroup_id, $new_pk = 0)
    {
        $db = JFactory::getDBO();
		
		if ( $new_pk == 0 ) {
			$this->load($pk);
			$name = $this->_getAssetName();
			$asset = JTable::getInstance('Asset', 'JTable', array('dbo' => $this->getDbo()));
			$asset->loadByName($name);
			
			$rules = new JAccessRules($asset->rules);
			$this->setRules($rules);
			
			$table =  JTable::getInstance('Collector','Table');
			while ($table->load(array('alias' => $this->alias)))
			{
				$this->name = JString::increment($this->name);
				$this->alias = JString::increment($this->alias, 'dash');
			}

			$this->id = 0;
			$this->asset_id = "";
			if ( $assetgroup_id ) {
				$this->access = $assetgroup_id;
			}
			$this->state = 0;
			$this->home = 0;
			
			$this->check();
			$this->store();
		} else {
			$this->load($new_pk);
		}
		
		$limit = 50;
		
		$updated_fields = 0;
		$columns = $db->getTableColumns('#__collector_fields');
		if (!isset($columns['done'])) {
			$query = "ALTER TABLE `#__collector_fields` ADD `done` INT NOT NULL DEFAULT '0'";
			$db->setQuery( $query );
			$db->execute();
		}
		
		$query = "SELECT * FROM `#__collector_fields` WHERE done != '1' AND collection = ".$pk;
		$db->setQuery( $query );
		$db->execute();
		$remaining_fields = $db->getNumRows();
		
		$updated_items = 0;
		$columns = $db->getTableColumns('#__collector_items');
		if (!isset($columns['done'])) {
			$query = "ALTER TABLE `#__collector_items` ADD `done` INT NOT NULL DEFAULT '0'";
			$db->setQuery( $query );
			$db->execute();
		}
		
		if ( $mode != 1 )
		{
			$query = "SELECT * FROM `#__collector_items` WHERE done != '1' AND collection = ".$pk;
			$db->setQuery( $query );
			$db->execute();
			$remaining_items = $db->getNumRows();
		}
		else
		{
			$remaining_items = 0;
		}
		
		if ($remaining_fields == 0)
		{
			if (($mode != 1) && ($remaining_items != 0))
			{
				$query = "SELECT * FROM `#__collector_items`";
				$query .= " WHERE done != '1'";
				$query .= " AND collection = ".$pk;
				$query .= " LIMIT ".$limit;
				
				$db->setQuery( $query );
				$items = $db->loadObjectList();
				
				foreach ($items AS $item)
				{
					$updated_items = $updated_items + 1;
					
					if ($mode == 2) {
						$copy_mode = 'LAST';
					} else {
						$copy_mode = 'FULL';
					}
					$itemInstance = JTable::getInstance('Collector_items','Table');
				
					$itemInstance->copy($item->id, $pk, $this->id, $copy_mode);
					
					$query = "UPDATE `#__collector_items` SET `done` = '1' WHERE id = '".$item->id."'";
					$db->setQuery( $query );
					$db->execute();
				}
			}
		}
		else
		{
			$query = "SELECT * FROM `#__collector_fields`";
			$query .= " WHERE done != '1'";
			$query .= " AND collection = ".$pk;
			$query .= " LIMIT ".$limit;
			
			$db->setQuery( $query );
			$fields = $db->loadObjectList();
				
			foreach ($fields AS $field)
			{
				$updated_fields = $updated_fields + 1;
				
				$fieldInstance = JTable::getInstance('Collector_fields','Table');
			
				$fieldInstance->copy($field->id, $this->id);
				
				$query = "UPDATE `#__collector_fields` SET `done` = '1' WHERE id = '".$field->id."'";
				$db->setQuery( $query );
				$db->execute();
			}
		}
		$remaining_fields = $remaining_fields - $updated_fields;
		$remaining_items = $remaining_items - $updated_items;
		if (($remaining_fields == 0) && ($remaining_items == 0)) {
			$query = "ALTER TABLE `#__collector_fields` DROP `done`";
			$db->setQuery( $query );
			$db->execute();
			
			$query = "ALTER TABLE `#__collector_items` DROP `done`";
			$db->setQuery( $query );
			$db->execute();
		}
		$response = array( 'updated_fields' => $updated_fields, 'remaining_fields' => $remaining_fields , 'updated_items' => $updated_items, 'remaining_items' => $remaining_items, 'new_col' => $this->id );
		
		return $response;
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
		return 'com_collector.collection.'.(int) $this->$k;
	}
 
	/**
	 * Method to return the title to use for the asset table.
	 *
	 * @return	string
	 * @since	1.6
	 */
	protected function _getAssetTitle()
	{
		return $this->name;
	}
 
	/**
	 * Get the parent asset id for the record
	 *
	 * @return	int
	 * @since	1.6
	 */
	protected function _getAssetParentId(JTable $table = null, $id = null)
	{
		$asset = JTable::getInstance('Asset');
		$asset->loadByName('com_collector');
		return $asset->id;
	}
}