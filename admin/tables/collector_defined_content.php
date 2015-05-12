<?php
/**
 * Joomla! 3.0 component Collector
 *
 * @package 	Collector
 * @copyright   Copyright (C) 2010 - 2015 Philippe Ousset. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 *
 * Collector is a Multi Purpose Listing Tool.
 * Originaly developped to list Collections
 * it can be used for several purpose.
 */

// Check to ensure this file is included in Joomla!
defined('_JEXEC') or die( 'Restricted access' );

/**
 * Collector_defined_content table
 * @package		Collector
 */
class TableCollector_defined_content extends JTableNested
{
	/**
	 * @param database A database connector object
	 */
	function TableCollector_defined_content( &$db )
	{
		parent::__construct( '#__collector_defined_content', 'id', $db );
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
		if( trim($this->content) == '' ) {
			$this->setError(JText::_( 'COM_COLLECTOR_WARNING_PROVIDE_VALID_NAME' ));
			return false;
		}
		
		$query = 'SELECT id'
		. ' FROM #__collector_defined_content'
		. ' WHERE defined = "' . $this->defined .'"'
		. ' AND content = "' . $this->content .'"'
		. ' AND parent_id = "' . $this->parent_id .'"'
		. ' AND id != "'. (int) $this->id .'"';
		;
		
		$this->_db->setQuery( $query );
		$xid = intval( $this->_db->loadResult() );
		if ($xid && $xid != intval( $this->id )) {
			$this->setError(  JText::_('COM_COLLECTOR_WARNREG_INUSE'));
			return false;
		}
		
		return true;
	}
	
	/**
	 * Method to recursively rebuild the whole nested set tree.
	 *
	 * @param   integer  $parentId  The root of the tree to rebuild.
	 * @param   integer  $leftId    The left id to start with in building the tree.
	 * @param   integer  $level     The level to assign to the current nodes.
	 * @param   string   $path      The path to the current nodes.
	 *
	 * @return  integer  1 + value of root rgt on success, false on failure
	 *
	 * @link    http://docs.joomla.org/JTableNested/rebuild
	 * @since   11.1
	 * @throws  RuntimeException on database error.
	 */
	public function rebuild($parentId = null, $leftId = 0, $level = 0, $path = '')
	{
		// If no parent is provided, try to find it.
		if ($parentId === null)
		{
			// Get the root item.
			$parentId = $this->getRootId();
		}

		$query = $this->_db->getQuery(true);

		// Build the structure of the recursive query.
		if (!isset($this->_cache['rebuild.sql']))
		{
			$query->clear()
				->select($this->_tbl_key)
				->from($this->_tbl)
				->where('parent_id = %d')
				->order('parent_id, lft');

			$this->_cache['rebuild.sql'] = (string) $query;
		}

		// Make a shortcut to database object.

		// Assemble the query to find all children of this node.
		$this->_db->setQuery(sprintf($this->_cache['rebuild.sql'], (int) $parentId));

		$children = $this->_db->loadObjectList();

		// The right value of this node is the left value + 1
		$rightId = $leftId + 1;

		// Execute this function recursively over all children
		foreach ($children as $node)
		{
			/*
			 * $rightId is the current right value, which is incremented on recursion return.
			 * Increment the level for the children.
			 * Add this item's id to the path
			 */
			$rightId = $this->rebuild($node->{$this->_tbl_key}, $rightId, $level + 1,  str_replace('||','|','|' . $path . $node->id . '|'));

			// If there is an update failure, return false to break out of the recursion.
			if ($rightId === false)
			{
				return false;
			}
		}

		// We've got the left value, and now that we've processed
		// the children of this node we also know the right value.
		$query->clear()
			->update($this->_tbl)
			->set('lft = ' . (int) $leftId)
			->set('rgt = ' . (int) $rightId)
			->set('level = ' . (int) $level)
			->set('path = ' . $this->_db->quote($path))
			->where($this->_tbl_key . ' = ' . (int) $parentId);
		$this->_db->setQuery($query)->execute();

		// Return the right value of this node + 1.
		return $rightId + 1;
	}
	
	/**
	 * Method to rebuild the node's path field from the alias values of the
	 * nodes from the current node to the root node of the tree.
	 *
	 * @param   integer  $pk  Primary key of the node for which to get the path.
	 *
	 * @return  boolean  True on success.
	 *
	 * @link    http://docs.joomla.org/JTableNested/rebuildPath
	 * @since   11.1
	 */
	public function rebuildPath($pk = null)
	{
		$k = $this->_tbl_key;
		$pk = (is_null($pk)) ? $this->$k : $pk;

		// Get the aliases for the path from the node to the root node.
		$query = $this->_db->getQuery(true)
			->select('p.id')
			->from($this->_tbl . ' AS n, ' . $this->_tbl . ' AS p')
			->where('n.lft BETWEEN p.lft AND p.rgt')
			->where('n.' . $this->_tbl_key . ' = ' . (int) $pk)
			->order('p.lft');
		$this->_db->setQuery($query);

		$segments = $this->_db->loadColumn();

		// Make sure to remove the root path if it exists in the list.
		if ($segments[0] == '1')
		{
			array_shift($segments);
		}

		// Build the path.
		$path = '|'.trim(implode('|', $segments)).'|';

		// Update the path field for the node.
		$query = $this->_db->getQuery(true)
			->update($this->_tbl)
			->set('path = ' . $this->_db->quote($path))
			->where($this->_tbl_key . ' = ' . (int) $pk);

		$this->_db->setQuery($query)->execute();

		// Update the current record's path to the new one:
		$this->path = $path;

		return true;
	}
}