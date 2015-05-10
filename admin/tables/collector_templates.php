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
 * Collector_template table
 * @package		Collector
 */
class TableCollector_templates extends JTable
{
	/** @var int Primary key */
	var $id	= null;
	
	/** @var string */
	var $name = null;
	
	/** @var string */
	var $alias = null;
	
	/** @var int */
	var $collection = null;

	/** @var tinyint */
	var $client = null;

	/** @var tinyint */
	var $home = null;

	/** @var int */
	var $column = null;

	/**
	 * @param database A database connector object
	 */
	function TableCollector_templates( &$db )
	{
		$db = JFactory::getDBO();
		parent::__construct( '#__collector_templates', 'id', $db );
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
			$this->setError(JText::_( 'COM_COLLECTOR_TPL_NAME' ));
			return false;
		}
		
		if(empty($this->alias)) {
			$this->alias = $this->name;
			
			if ($this->client == 1)
			{
				$client = 'collection';
			}
			else
			{
				$client = 'item';
			}
			
			$file = JPATH_SITE.'/components/com_collector/views/'.$client.'/tmpl/default_'.$this->alias.'.php';
			
			jimport('joomla.filesystem.file');
			
			if( JFile::exists($file) ) {
				$this->setError(JText::_( 'COM_COLLECTOR_TPL_FILE_EXIST' ));
				return false;
			}
		}
		$this->alias = JFilterOutput::stringURLSafe($this->alias);
				
		return true;
	}
	
	/**
	 * Overloaded store function
	 *
	 * @access	public
	 * @return	null|string	null if successful otherwise returns and error message
	 * @see		JTable::store
	 */
	function store()
	{
		$app = JFactory::getApplication();
		$copied = $app->input->getVar( 'copied' );
		$clientCopied = $app->input->getVar( 'clientCopied' );
		
		if ($this->client == 1)
		{
			$client = 'collection';
		}
		else
		{
			$client = 'item';
		}
		
		$file = JPATH_SITE.'/components/com_collector/views/'.$client.'/tmpl/default_'.$this->alias.'.php';
		
		if (( $this->id == null ) && ( $copied == null ))
		{
			$content = '<?php defined(\'_JEXEC\') or die(\'Restricted access\'); ?>';
			
			jimport('joomla.filesystem.file');
			
			if( !(JFile::write( $file, $content )) ) {
				$this->setError(JText::_( 'COM_COLLECTOR_ERR_CREATE_TPL_FILE' ));
				return false;
			}
		}
		else if ( $copied != null )
		{
			if ($clientCopied == 1)
			{
				$clientCopied = 'collection';
			}
			else
			{
				$clientCopied = 'item';
			}
			
			$fileCopied = JPATH_SITE.'/components/com_collector/views/'.$clientCopied.'/tmpl/default_'.$copied.'.php';
			
			jimport('joomla.filesystem.file');
			
			if( !(JFile::copy( $fileCopied, $file )) ) {
				$this->setError(JText::_( 'COM_COLLECTOR_ERR_COPY_TPL_FILE' ).$fileCopied);
				return false;
			}
		}
		
		return parent::store();
	}
	
	/**
	 * Overloaded delete function
	 *
	 * @access	public
	 * @param	int		$id		Template Id
	 * @return	boolean		True if the template is deleted
	 * @see		JTable::delete
	 */
	function delete($id)
	{
		$this->load($id);
		
		if ($this->client == 1)
		{
			$client = 'collection';
		}
		else
		{
			$client = 'item';
		}
		
		jimport('joomla.filesystem.file');
		
		$file = JPATH_SITE.'/components/com_collector/views/'.$client.'/tmpl/default_'.$this->alias.'.php';
		
		if ( JFile::exists( $file ) )
		{
			if ( !(JFile::delete( $file )) )
			{
				$this->setError(JText::_( 'COM_COLLECTOR_ERR_DELETE_TPL_FILE' ));
				return false;
			}
		}
		
		return parent::delete($id);
	}
	
	/**
	 * function to load a copy of a template
	 *
	 * @access	public
	 * @param	int		$id		Template Id
	 * @return boolean
	 */
	function loadCopy($id)
	{
		$this->load($id);
		
		$this->copied = $this->alias;
		$this->id = 0;
		$this->name = '';
		$this->alias = '';
		
		return true;
	}
}
