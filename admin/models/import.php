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

// import Joomla filesystem library
jimport('joomla.filesystem.folder');
jimport('joomla.filesystem.file');

require_once(JPATH_ROOT.'/administrator/components/com_collector/classes/field.php');

/**
 * Import model
 * @package	Collector
 */
class CollectorModelImport extends JModelLegacy
{
	/**
	 * Collection Id
	 * @var int
	 */
	var $_collection = null;

	/**
	 * Returns a reference to the a Table object, always creating it.
	 *
	 * @param	type	The table type to instantiate
	 * @param	string	A prefix for the table class name. Optional.
	 * @param	array	Configuration array for model. Optional.
	 * @return	JTable	A database object
	 * @since	1.6
	 */
	public function getTable($type = 'Collector_fields', $prefix = 'Table', $config = array()) 
	{
		
		return JTable::getInstance($type, $prefix, $config);
	}
	
	/**
	 * Method to load default collection Id in <var>_collection</var>
	 *
	 * @access	private
	 */
	public function getCollection()
	{
		$app = JFactory::getApplication();
		if ($this->_collection == '')
		{
			$this->_collection = $app->input->getString('collection', null);
			
			if ($this->_collection == '')
			{
				$query = 'SELECT id';
				$query .= ' FROM `#__collector`';
				$query .= ' WHERE home = 1';
				
				$row = $this->_getList( $query );
				
				if ( !$row )
				{
					/**
					 * tag à defaut d'une collection
					 */
					$query = 'UPDATE `#__collector` SET home = 1 LIMIT 1;';
					$this->_db->setQuery( $query );
					$this->_db->execute();
					
					$query = 'SELECT id';
					$query .= ' FROM `#__collector`';
					$query .= ' WHERE home = 1';
					
					$row = $this->_getList( $query );
				}
				if ( $row == null )
				{
					return false;
				}
				else
				{
					$this->_collection = $row[0]->id;
				}
			}
		}
		
		return $this->_collection;
	}

	/**
	 * Method to load listdrop of collections
	 *
	 * @access	public
	 */
	public function getFields()
	{
		if ( empty($this->_fields) )
		{
			$collection = $this->getCollection();
			
			// Create a new query object.
			$db = JFactory::getDBO();
			$query = $db->getQuery(true);
			
			// Select the required fields from the table.
			$query->select('f.id, f.field, f.description, f.tablecolumn, f.required, f.attribs');
			$query->from('#__collector_fields as f');
			
			// Join over the type.
			$query->select('t.type AS type');
			$query->join('LEFT', '#__collector_fields_type AS t ON t.id = f.type');
			
			// Filter by collection
			$query->where('f.collection = '.$collection);
			
			// Add the list ordering clause.
			$query->order($db->escape('f.ordering ASC'));
			
			$db->setQuery( $query );
			
			$fields = $db->loadObjectList();
			
			foreach ($fields as $field)
			{
				$registry = new JRegistry;
				$registry->loadString($field->attribs);
				$field->attribs = $registry->toArray();
				$fieldObjects[] = CollectorField::getInstance( $collection, $field );
			}
			$this->_fields = $fieldObjects;
		}
		
		return $this->_fields;
	}
	
	/**
	 * Method to get the list of siblings in a menu.
	 * The method requires that parent be set.
	 *
	 * @return  array  The field option objects or false if the parent field has not been set
	 * @since   1.7
	 */
	public function getCollections()
	{
		$collection = $this->getCollection();
		
		$options = array();

		$db = JFactory::getDbo();
		$query = $db->getQuery(true)
			->select('id AS value, name AS text')
			->from('#__collector')
			->order('text');

		// Get the options.
		$db->setQuery($query);

		try
		{
			$options = $db->loadObjectList();
		}
		catch (RuntimeException $e)
		{
			JFactory::getApplication()->enqueueMessage($e->getMessage(),'warning');
		}

		$html = JHTML::_('select.genericlist', $options, 'collection', 'class="inputbox" onchange="changeCollection(this);"', 'value', 'text', $collection);

		return $html;
	}
	
	/**
	 * Retrieves list of files to import
	 *
	 * @access	public
	 * @return	array				Array of objects containing the data from the filesystem
	 */
	function getFiles()
	{
		$baseSite = JPath::clean(COM_COLLECTOR_BASE.DIRECTORY_SEPARATOR);
		
		$files = array();
		
		$extensions = array("xls","xlsx");
		
		$filesList = JFolder::files($baseSite);
		
		if ( $filesList != null )
		{
			foreach ( $filesList as $file )
			{
				$ext = strtolower(JFile::getExt($file));
				
				if ( in_array( $ext, $extensions ) )
				{
					$tmp = new JObject();
					$tmp->name = $file;
					$tmp->path = JPath::clean($baseSite.$file);
					$tmp->size = $this->get_file_size($tmp->path);
					$tmp->modified = $this->get_file_date($tmp->path);
					$tmp->type = $ext;
					$tmp->ico = 'components/com_collector/assets/images/page_white_excel.png';
					$files[] = $tmp;
				}
			}
		}
		
		return $files;
	}
	
	/**
	 * Retrieve size file from filesystem
	 *
	 * @access	public
	 * @return	string	Size
	 */
	function get_file_size($filename)
	{
		$size_in_bytes = filesize($filename);
		
		$precision = 0;
		
		if ( $size_in_bytes < 1024 )
		{
			$unit = ' bytes';
			$size = $size_in_bytes;
		}
		else
		{
			$size_in_kilobytes = (($size_in_bytes / 1024));
			
			if ( $size_in_kilobytes < 1024 )
			{
				$unit = ' Kb';
				$size = round($size_in_kilobytes, $precision) ? round($size_in_kilobytes, $precision) : 1;
			}
			else
			{
				$size_in_megabytes = (($size_in_kilobytes / 1024));
				
				$unit = ' Mb';
				$size = round($size_in_megabytes, $precision) ? round($size_in_megabytes, $precision) : 1;
			}
		}
		
		return $size.$unit;
	}
	
	/**
	 * Retrieve date file from filesystem
	 *
	 * @access	public
	 * @return 	string 	Date html
	 */
	function get_file_date($filename)
	{
		$config = JFactory::getConfig();
		$tzoffset = $config->get('config.offset');
		$date = JFactory::getDate(filemtime($filename), $tzoffset);
		$modified = $date->toSql();
		
		return JHTML::_( 'date', $modified, 'Y-m-d H:i:s');
	}
}