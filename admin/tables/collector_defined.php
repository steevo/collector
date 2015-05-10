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
 * Collector_defined table
 * @package		Collector
 */
class TableCollector_defined extends JTable
{
	/**
	 * @param database A database connector object
	 */
	function TableCollector_defined( &$db )
	{
		parent::__construct( '#__collector_defined', 'id', $db );
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
		if( trim($this->name) == '' ) {
			$this->setError(JText::_( 'COM_COLLECTOR_WARNING_PROVIDE_VALID_NAME' ));
			return false;
		}
		
		if(empty($this->alias)) {
			$this->alias = $this->name;
		}
		$this->alias = JFilterOutput::stringURLSafe($this->alias);
		
		if (trim(str_replace('-','',$this->alias)) == '') {
			$this->alias = JFactory::getDate()->format('Y-m-d-H-i-s');
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

			if (empty($this->created_by)) {
				$this->created_by = $user->get('id');
			}
		}
		// Verify that the alias is unique
		$table = JTable::getInstance('Collector_defined','Table');
		if ($table->load(array('alias'=>$this->alias)) && ($table->id != $this->id || $this->id==0)) {
			$this->setError(JText::_('COM_COLLECTOR_DATABASE_ERROR_COLLECTION_UNIQUE_ALIAS'));
			return false;
		}
		return parent::store($updateNulls);
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
			$query = 'DELETE FROM `#__collector_defined_content` WHERE defined = '.$pk;
			$this->_db->setQuery( $query );
			$this->_db->execute();
		}
		
		return parent::delete($pk);
	}
}