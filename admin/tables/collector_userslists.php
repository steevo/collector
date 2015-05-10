<?php
/**
 * Joomla! 3.0 component Collector
 *
 * @package 	Collector
 * @copyright   Copyright (C) 2010 - 2015 Open Source Matters, Inc. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 *
 * Collector is a Multi Purpose Listing Tool.
 * Originaly developped to list Collections
 * it can be used for several purpose.
 */

// Check to ensure this file is included in Joomla!
defined('_JEXEC') or die( 'Restricted access' );

/**
 * Collector_userslists table
 * @package		Collector
 */
class TableCollector_userslists extends JTable
{
	/**
	 * @param database A database connector object
	 */
	function TableCollector_userslists( &$db )
	{
		parent::__construct( '#__collector_userslists', 'id', $db );
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
		if( trim($this->name) == '' ) {
			$this->setError(JText::_( 'COM_COLLECTOR_WARNING_PROVIDE_VALID_NAME' ));
			return false;
		}
		
		if (trim($this->alias) == '') {
			$this->alias = $this->name;
		}

		$this->alias = JApplication::stringURLSafe($this->alias);

		if (trim(str_replace('-','',$this->alias)) == '') {
			$this->alias = JFactory::getDate()->format('Y-m-d-H-i-s');
		}
		
		// Check the publish down date is not earlier than publish up.
		if ($this->publish_down > $this->_db->getNullDate() && $this->publish_down < $this->publish_up) {
			// Swap the dates.
			$temp = $this->publish_up;
			$this->publish_up = $this->publish_down;
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
		$date	= JFactory::getDate();
		$user	= JFactory::getUser();
		
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

			if (!intval($this->publish_up)) {
				$this->publish_up = $this->created;
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
	 * @return	true if successful otherwise returns and error message
	 * @see		JTable::delete
	 */
	function delete( $oid=null )
	{
		if ($oid)
		{
			$this->load($oid);
			$collection = $this->collection;
			// TODO : delete all users lists
			// $column = $this->tablecolumn;
			
			// $query = 'ALTER TABLE `#__collector_items_history_'.$collection.'` DROP `'.$column.'`';
			// $this->_db->setQuery( $query );
			// $this->_db->execute();
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
        $db = JFactory::getDBO();
		
		$this->load($pk);
		
		$this->id = 0;
		$this->collection = $collection;
		
		$this->check();
		$this->store();
		
		return true;
    }
}