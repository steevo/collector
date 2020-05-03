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

jimport( 'joomla.application.component.model' );
jimport( 'joomla.filesystem.folder' );
jimport( 'joomla.filesystem.file' );

/**
 * Folderlist model
 * @package	Collector
 */
class CollectorModelFolderlist extends JModelLegacy
{
	public function getState($property = null, $default = null)
	{
		static $set;

		if (!$set)
		{
			$input  = JFactory::getApplication()->input;
			$folder = $input->get('folder', '', 'path');
			$this->setState('folder', $folder);
			$set = true;
		}

		return parent::getState($property, $default);
	}
	
	/**
	 * Retrieves list of folders and files from a folder
	 *
	 * @access	public
	 * @return	array				Array of objects containing the data from the filesystem
	 */
	function getFolders()
	{
		// Get current path from request
		$current = $this->getState('folder');
		
		if (strlen($current) > 0)
		{
			$basePath = COM_COLLECTOR_BASE.'/'.$current;
		}
		else
		{
			$basePath = COM_COLLECTOR_BASE;
		}
		
		$mediaBase = str_replace(DIRECTORY_SEPARATOR, '/', COM_COLLECTOR_BASE.'/');
		
		$folders	= array ();
		$folderList = false;
		
		if (file_exists($basePath))
		{
			// Get the list of folders from the given folder
			$folderList = JFolder::folders($basePath);
		}
		
		$MediaHelper = new JHelperMedia;
		
		// Iterate over the folders if they exist
		if ($folderList !== false)
		{
			foreach ($folderList as $folder)
			{
				$tmp = new JObject;
				$tmp->name = basename($folder);
				$tmp->path = str_replace(DIRECTORY_SEPARATOR, '/', JPath::clean($basePath . '/' . $folder));
				$tmp->path_relative = str_replace($mediaBase, '', $tmp->path);
				$count = $MediaHelper->countFiles($tmp->path);
				$tmp->files = $count[0];
				$tmp->folders = $count[1];

				$folders[] = $tmp;
			}
		}
		
		return $folders;
	}
}