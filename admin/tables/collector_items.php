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

require_once(JPATH_ROOT.'/administrator/components/com_collector/classes/field.php');
require_once(JPATH_ROOT.'/administrator/components/com_collector/tables/collector_items_history_.php');

/**
 * Collector_items table
 * @package		Collector
 */
class TableCollector_items extends JTable
{
	/**
	 * @param database A database connector object
	 */
	function __construct( &$db )
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
		$this->history = TableCollector_items_history_::getInstance('Collector_items_history_', 'Table', array('collection'=>$collection));
		
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
		
		$this->modification = JText::_('COM_COLLECTOR_INIT_VERSION');
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
			if ( ($key!='id') && ($key!='state') )
			{
				$this->$key = $value;
			}
			else
			{
				$this->historyId = $value;
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
				$msg = JText::_('COM_COLLECTOR_WARNREG_INUSE');
				if ( JPATH_COMPONENT == JPATH_COMPONENT_SITE )
				{
					$link = JRoute::_( 'index.php?option=com_collector&view=item&id=' . $this->collection . '&item=' . $xid );
				}
				else
				{
					$link = JRoute::_( 'index.php?option=com_collector&controller=items&collection=' . $this->collection . '&task=edit&cid[]=' . $xid );
				}
				$a = '<a href="'.$link.'">'.$xid.'</a>';
				$this->setError( JText::sprintf( $msg, $a ) );
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
	function storeVersion( $version = null )
	{
		$db = JFactory::getDBO();
		$user = JFactory::getUser();
		$datenow = JFactory::getDate();
		
		if ( !$this->id )
		{
			$this->modified = null;
			$this->modified_by = null;
		}
		
		// $this->history = TableCollector_items_history_::getInstance($this->collection);
		$this->history = TableCollector_items_history_::getInstance('Collector_items_history_', 'Table', array('collection'=>$this->collection));
		
		// recuperation des donnees de la version
		$this->history->loadVersion( $this->collection, $this->id, false, $version );
		
		// Convert to the JObject before adding other data.
		$properties = $this->history->getProperties(1);
		
		foreach ( $properties as $key => $value )
		{
			if (($key!='id')&&($key!='state'))
			{
				$this->history->$key = $this->$key;
				unset($this->$key);
			}
		}
		
		unset($this->historyId);
		
		if (!$this->store())
		{
			return false;
		}
		
		$this->history->id = $version;
		$this->history->item = $this->id;
		$this->history->store();
		
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
		$query = 'DELETE FROM `#__collector_items_history_'.$this->collection.'` WHERE `id` = "' . $id . '";';
		
		$this->_db->setQuery( $query );
		$this->_db->execute();
		
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
		$db = JFactory::getDBO();
		
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
			$registry = new JRegistry;
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
		
		$this->alias = JFilterOutput::stringURLSafe($this->fulltitle);
		
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
			JCommentsModel::deleteComments($pk, 'com_collector');
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
		$db = JFactory::getDBO();

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
				$asset = JTable::getInstance('Asset', 'JTable', array('dbo' => $this->getDbo()));
				$asset->loadByName($name);
				
				$rules = new JAccessRules($asset->rules);
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
	protected function _getAssetParentId(JTable $table = null, $id = null)
	{
		$asset = JTable::getInstance('Asset');
		$asset->loadByName('com_collector.collection.'.(int) $this->collection);
		return $asset->id;
	}
}