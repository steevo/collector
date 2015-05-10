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

jimport('joomla.application.component.model');
jimport('joomla.filesystem.folder');
jimport('joomla.filesystem.file');

/**
 * Filemanager model
 * @package		Collector
 */
class CollectorModelFilemanager extends JModelList
{
	/**
	 * The relative Path from site root to the current folder
	 * @var string 
	 */
	var $_folder;
	
	/**
	 * The path to the current folder
	 * @var array 
	 */
	var $_path;
	
	/**
	 * Filemanager model constructor
	 *
	 * @access	public
	 */
	function __construct()
	{
		parent::__construct();
		
		// Get folder request variables
		$this->_folder = $this->getUserStateFromRequest($this->context.'.filemanager.folder', 'folder');
		
		$path = JPath::clean(COM_COLLECTOR_BASE);
		if (!is_dir($path))
		{
			JFolder::create($path);
		}
		
		$this->_path = array();
		$tmp = new JObject();
		$tmp->name = JText::_( 'COM_COLLECTOR_ROOT' );
		$tmp->path = '';
		$this->_path[] = $tmp;
		
		if ( $this->_folder != '' )
		{
			$this->cleanPath();
		}
	}
	
	/**
	 * Retrieve the current folder
	 *
	 * @access	public
	 * @return	string	Relative Path from site root
	 */
	function getFolder()
	{
		return $this->_folder;
	}
	
	/**
	 * Retrieves list of folders and files from a folder
	 *
	 * @access	public
	 * @param	string	$baseFolder	Relative Path from site root
	 * @return	array				Array of objects containing the data from the filesystem
	 */
	function getList($baseFolder = null)
	{
		$baseSite = JPath::clean(COM_COLLECTOR_BASE.DIRECTORY_SEPARATOR);
		
		$folders = array();
		$files = array();
		
		//Si aucun dossier specifie on va a la racine du site
		if ( $baseFolder == null )
		{
			if ( $this->_folder == '' )
			{
				$baseFolder = $baseSite;
			}
			else
			{
				$baseFolder = $baseSite.$this->_folder;
				
				$precFolder = implode ( DIRECTORY_SEPARATOR , explode( DIRECTORY_SEPARATOR, $baseFolder, -1 ) );
				
				$prec = new JObject();
				$prec->name = JText::_('COM_COLLECTOR_BACK_DIRECTORY');
				$prec->type = '';
				$prec->text = '';
				$prec->ico = 'components/com_collector/assets/images/folder_back.png';
				$prec->path = JPath::clean($precFolder);
				if ( $prec->path.DIRECTORY_SEPARATOR == $baseSite )
				{
					$prec->path_relative = '';
				}
				else
				{
					$prec->path_relative = str_replace($baseSite, '', $prec->path);
				}
				//$count = MediaHelper::countFiles($prec->path);
				//$prec->files = $count[0];
				//$prec->folders = $count[1];
				
				$folders[] = $prec;
			}
		}
		else
		{
			$baseFolder = COM_COLLECTOR_BASE.DIRECTORY_SEPARATOR.$baseFolder;
		}
		
		$extensions = $this->getExtensions();
		
		$foldersList = JFolder::folders($baseFolder);
		$filesList = JFolder::files($baseFolder);
		
		if ( $filesList != null )
		{
			foreach ( $filesList as $file )
			{
				$ext = strtolower(JFile::getExt($file));
				
				if ( array_key_exists( $ext, $extensions ) )
				{
					$tmp = new JObject();
					$tmp->name = $file;
					$tmp->path = JPath::clean($baseFolder.'/'.$file);
					$tmp->path_relative = str_replace($baseSite, '', $tmp->path);
					$tmp->size = $this->get_file_size($tmp->path);
					$tmp->modified = $this->get_file_date($tmp->path);
					$tmp->type = $extensions[$ext]->type;
					$tmp->text = JText::_('COM_COLLECTOR_'.$extensions[$ext]->text);
					$tmp->ico = 'components/com_collector/assets/images/'.$extensions[$ext]->ico;
					$files[] = $tmp;
				}
			}
			
		}
		
		if ( $foldersList != null )
		{
			foreach ( $foldersList as $folder )
			{
				$tmp = new JObject();
				$tmp->name = basename($folder);
				$tmp->text = JText::_('COM_COLLECTOR_DIRECTORY');
				$tmp->ico = 'components/com_collector/assets/images/folder.png';
				$tmp->path = JPath::clean($baseFolder.'/'.$folder);
				$tmp->path_relative = str_replace($baseSite, '', $tmp->path);
				//$count = MediaHelper::countFiles($tmp->path);
				//$tmp->files = $count[0];
				//$tmp->folders = $count[1];
				
				$folders[] = $tmp;
			}
		}
		
		$this->list = array('folders' => $folders, 'files' => $files);
		
		return $this->list;
	}
	
	/**
	 * Check folder from request
	 * Clean <var>_folder</var>
	 *
	 * @access	public
	 * @return	void
	 */
	function cleanPath()
	{
		$path = explode(DIRECTORY_SEPARATOR,$this->_folder);
		
		$i = 0;
		
		foreach ( $path as $folder )
		{
			if ( $folder == '..' )
			{
				$i = $i - 1;
				if ( $i < 0 )
				{
					$cleanPath = array();
					$this->_path = array();
					$tmp = new JObject();
					$tmp->name = JText::_( 'COM_COLLECTOR_ROOT' );
					$tmp->path = '';
					$this->_path[] = $tmp;
					break;
				}
				else
				{
					unset($cleanPath[$i]);
					unset($this->_path[$i]);
				}
			}
			else
			{
				if ( $folder != '.' )
				{
					$cleanPath[] = $folder;
					$tmp = new JObject();
					$tmp->name = $folder;
					$tmp->path = implode(DIRECTORY_SEPARATOR, $cleanPath);
					$this->_path[] = $tmp;
					$i = $i + 1;
				}
			}
		}
		
		$this->_folder = implode(DIRECTORY_SEPARATOR, $cleanPath);
	}
	
	/**
	 * Retrieve extensions available
	 *
	 * @access	public
	 * @return	object	Array of extensions objects
	 */
	function getExtensions()
	{
		$query = 'SELECT e.id, t.text, e.type, e.ext, e.ico, e.state';
		$query .= ' FROM `#__collector_files_ext` AS e';
		$query .= ' LEFT JOIN `#__collector_files_type` AS t ON e.type = t.id';
		$query .= ' WHERE e.state = "1"';
		
		$db = $this->getDBO();
		
		$db->setQuery($query);
		$db->execute();
		
		$this->_ext = $db->loadObjectList ( 'ext' );
		
		return $this->_ext;
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
	
	/**
	 * Construct Path links for navigation in the filesystem
	 *
	 * @access	public
	 * @return 	string 	Navigation html
	 */
	function get_path_navigation()
	{
		$navigation = '<b><big>';
		
		foreach ($this->_path as $folder)
		{
			$navigation .= '<a href="' . JRoute::_( 'index.php?option=com_collector&amp;view=filemanager&amp;folder='.$folder->path ) . '" >' . $folder->name . '</a>/';
		}
		
		$navigation .= '</big></b>';
		
		return $navigation;
	}
	
	/**
	 * Method to remove files
	 *
	 * @access	public
	 * @return	string	Message to display
	 */
	function remove()
	{
		$app = JFactory::getApplication();
		$cid = $app->input->getVar( 'cid', array(0), '', 'array');
		$deleted = array( "files" => 0 , "folders" => 0 );
		
		foreach ($cid as $id)
		{
			$path = JPath::clean(COM_COLLECTOR_BASE.'/'.$id);
			
			if (!is_dir($path))
			{
				if (is_file($path))
				{
					if ( JFile::delete($path) )
					{
						$deleted[files]++;
					}
				}
			}
			else
			{
				$filesList = JFolder::files($path,'.',true,true);
				$candelete = 1;
				
				foreach ( $filesList as $file )
				{
					if ( JFile::getName($file) != 'index.html' )
					{
						$candelete = 0;
						break;
					}
					else
					{
						$content = file_get_contents($file);
						
						$blank = "<html><body bgcolor=\"#FFFFFF\"></body></html>";
						if ( $content != $blank )
						{
							$candelete = 0;
							break;
						}
					}
				}
				if ( $candelete == 1 )
				{
					if ( JFolder::delete($path) )
					{
						$deleted[folders]++;
					}
				}
			}
		}
		
		$msg[0] = $deleted[files] . ' ' . JText::_( 'COM_COLLECTOR_FILE_DELETED' ) . ' & ' . $deleted[folders] . ' ' . JText::_( 'COM_COLLECTOR_FOLDER_DELETED' );
		if ( $deleted[files]+$deleted[folders] == count($cid) )
		{
			$msg[1] = 'message';
		}
		else
		{
			$msg[0] .= ' ; ' . JText::_( 'COM_COLLECTOR_NO_DELETED' );
			$msg[1] = 'error';
		}
		
		return $msg;
	}
	
	/**
	 * Method to rename file or folder
	 *
	 * @access	public
	 */
	function rename()
	{
		$app = JFactory::getApplication();
		$renameElement = $app->input->getVar('renameElement', '', '', 'text');
		$rename = $app->input->getVar('rename', '', '', 'text');
		
		$path = JPath::clean(COM_COLLECTOR_BASE.'/'.$this->_folder.'/');
		$path_relative = $this->_folder.'/';
		
		if ( JFolder::exists( $path . $renameElement ) )
		{
			$result = JFolder::move( $path . $renameElement , $path . $rename );
		}
		else
		{
			$result = JFile::move( $path . $renameElement , $path . $rename );
		}
		
		if ( $result == 1 )
		{
			$this->update_path( $path_relative.$renameElement, $path_relative.$rename );
		}
		
		return;
	}
	
	/**
	 * Method to copy file or folder
	 *
	 * @access	public
	 */
	function copy()
	{
		$app = JFactory::getApplication();
		$elements = $app->input->getVar('cid', 0, '', 'array');
		
		$path = JPath::clean(COM_COLLECTOR_BASE.'/');
		$path2copy = JPath::clean(COM_COLLECTOR_BASE.'/'.$this->_folder.'/');
		
		foreach ( $elements as $element )
		{
			$element = str_replace('\\','/',$element);
			
			$arrayPath = array_reverse( explode('/',$element) );
			
			$singleElement = $arrayPath[0];
			
			if ( JFolder::exists( $path . $element ) )
			{
				JFolder::copy( $path . $element , $path2copy . $singleElement );
			}
			else
			{
				JFile::copy( $path . $element , $path2copy . $singleElement );
			}
		}
		
		return;
	}
	
	/**
	 * Method to move file or folder
	 *
	 * @access	public
	 */
	function move()
	{
		$app = JFactory::getApplication();
		$elements = $app->input->getVar('cid', 0, '', 'array');
		
		$path = JPath::clean(COM_COLLECTOR_BASE.'/');
		$path2copy = JPath::clean(COM_COLLECTOR_BASE.'/'.$this->_folder.'/');
		$path2copy_relative = $this->_folder.'/';
		
		foreach ( $elements as $element )
		{
			$element = str_replace('\\','/',$element);
			
			$arrayPath = array_reverse( explode('/',$element) );
			
			$singleElement = $arrayPath[0];
			
			if ( JFolder::exists( $path . $element ) )
			{
				$result = JFolder::move( $path . $element , $path2copy . $singleElement );
			}
			else
			{
				$result = JFile::move( $path . $element , $path2copy . $singleElement );
			}
			
			if ( $result == 1 )
			{
				$this->update_path( $element, $path2copy_relative.$singleElement );
			}
		}
		
		return;
	}
	
	/**
	 * Method to update path to files in database
	 *
	 * @access	public
	 * @param	string	$oldPath
	 * @param	string	$newPath
	 * @return	void
	 */
	function update_path( $oldPath, $newPath )
	{
		$params = JComponentHelper::getParams( 'com_collector' );
		$oldPath = str_replace( '\\', '/', $params->get('file_path', 'images/collector').'/'.$oldPath );
		$newPath = str_replace( '\\', '/', $params->get('file_path', 'images/collector').'/'.$newPath );
		
		// mise a jour des listes predefinies
		$db = $this->getDBO();
		
		$query = 'SELECT id, image FROM `#__collector_defined_content`';
		$query .= ' WHERE image LIKE "' . $oldPath . '%"';
		
		$db->setQuery($query);
		$db->execute();
		
		$rows = $db->loadObjectList();
		
		foreach ( $rows as $row )
		{
			$Path = str_replace( $oldPath, $newPath, $row->image );
			
			$query = 'UPDATE `#__collector_defined_content` SET image = "' . $Path . '" WHERE id = "' . $row->id . '"';
			
			$db->setQuery($query);
			$db->execute();
		}
		
		// mise a jour des objets
		// recherche des champs de type image ou fichier
		$query = 'SELECT id, tablecolumn, collection FROM `#__collector_fields`';
		
		$db->setQuery($query);
		$db->execute();
		
		$fields = $db->loadObjectList();
		
		foreach ( $fields as $field )
		{
			$query = 'SELECT id, '.$field->tablecolumn.' FROM `#__collector_items_history_'.$field->collection.'`';
			$query .= ' WHERE '.$field->tablecolumn.' LIKE "' . $oldPath . '%"';
			
			$db->setQuery($query);
			$db->execute();
			
			$rows = $db->loadObjectList();
			
			foreach ( $rows as $row )
			{
				$nameField = $field->tablecolumn;
				$Path = str_replace( $oldPath, $newPath, $row->$nameField );
				
				$query = 'UPDATE `#__collector_items_history_'.$field->collection.'` SET '.$field->tablecolumn.' = "' . $Path . '" WHERE id = "' . $row->id . '"';
				
				$db->setQuery($query);
				$db->execute();
			}
		}
		
		return;
	}
}