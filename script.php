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

// No direct access
defined('_JEXEC') or die;

ignore_user_abort( true ); 

/**
 * Script file of Collector component
 */
class com_collectorInstallerScript
{
    /**
	 * method to update the component
	 *
	 * @return void
	 */
	function install($parent) 
	{
		$user	= JFactory::getUser();
		$params   = JComponentHelper::getParams('com_languages');
		$frontend_lang = $params->get('site', 'en-GB');
		$language = JLanguage::getInstance($frontend_lang);
		
		// get language file for default layouts
		$language = JFactory::getLanguage();
		$language->load('com_collector');
		$db = JFactory::getDBO();
		
		// check for root item
		$query = "SELECT id from `#__collector_defined_content` WHERE id=1;";
		$db->setQuery( $query );
		if (!$result = $db->LoadResult())
		{
			$query = "INSERT INTO `#__collector_defined_content` SET `id` = 1,`defined` = 0,`parent_id` = 0,`level` = 0,`path` = '',`content` = 'List_Item_Root',`lft` = 0,`rgt` = 1;";
			$db->setQuery( $query );
			$db->query();
		}
		
		// check for fields types
		$query = "SELECT id from `#__collector_fields_type` WHERE id=1;";
		$db->setQuery( $query );
		if (!$result = $db->LoadResult())
		{
			$query = "INSERT INTO `#__collector_fields_type` (`id`, `type`, `state`, `unikable`, `sortable`, `searchable`, `filterable`, `intitle`) VALUES
				(1, 'line', 1, 1, 1, 1, 0, 1),
				(2, 'text', 1, 0, 0, 1, 0, 0),
				(3, 'define', 1, 0, 1, 1, 1, 1),
				(4, 'item', 0, 0, 1, 1, 1, 1),
				(5, 'image', 1, 0, 0, 1, 0, 0),
				(6, 'number', 1, 1, 1, 1, 1, 1),
				(7, 'date', 1, 1, 1, 1, 1, 1),
				(8, 'file', 1, 0, 0, 1, 0, 0);";
			$db->setQuery( $query );
			$db->query();
		}

		// check for files types
		$query = "SELECT id from `#__collector_files_type`;";
		$db->setQuery( $query );
		if (!$result = $db->LoadResult())
		{
			$query = "INSERT INTO `#__collector_files_type` (`id`, `name`, `text`) VALUES
				(1 , 'picture', 'IMG_DOC'),
				(2 , 'text', 'TXT_DOC'),
				(3 , 'calc', 'CALC_DOC'),
				(4 , 'pdf', 'PDF_DOC'),
				(5 , 'audio', 'AUDIO_DOC'),
				(6 , 'video', 'VIDEO_DOC'),
				(7 , 'other', 'OTHER_DOC');";
			$db->setQuery( $query );
			$db->query();
		}
		
		$query = "SELECT id from `#__collector_files_ext`;";
		$db->setQuery( $query );
		if (!$result = $db->LoadResult())
		{
			$query = "INSERT INTO `#__collector_files_ext` (`id` , `ext`, `type` ,`ico` ,`state`) VALUES
				(1 , 'jpg', '1', 'picture.png', '1'),
				(2 , 'png', '1', 'picture.png', '1'),
				(3 , 'bmp', '1', 'picture.png', '1'),
				(4 , 'gif', '1', 'picture.png', '1'),
				(5 , 'xcf', '1', 'picture.png', '0'),
				(6 , 'doc', '2', 'page_white_word.png', '1'),
				(7 , 'odt', '2', 'page_white_word.png', '1'),
				(8 , 'txt', '2', 'page_white_word.png', '1'),
				(9 , 'rtf', '2', 'page_white_word.png', '1'),
				(10 , 'xls', '3', 'page_white_excel.png', '1'),
				(11 , 'ods', '3', 'page_white_excel.png', '1'),
				(12 , 'pdf', '4', 'page_white_acrobat.png', '1');";
			$db->setQuery( $query );
			$db->query();
		}
		
		$imagepath = JUri::root().'administrator/components/com_collector/assets/images/collector_logo.png';
		
		$msg = array();
		$msg[] = '<center>';
		$msg[] = '<table width="100%" style="border:0px; margin-bottom:10px;">';
		$msg[] = '<tr>';
		$msg[] = '<td align="center" colspan="2">';
		$msg[] = '<img src="' . $imagepath . '" border="0" alt="Collector" /><br />Version 0.6.1';
		$msg[] = '</td>';
		$msg[] = '</tr>';
		$msg[] = '<tr>';
		$msg[] = '<td style="border:1px solid #999;background-color:aliceblue;" colspan="2">';
		$msg[] = '<b>' . JText::_('COM_COLLECTOR_INSTALL') . '</b>';
		$msg[] = '</td>';
		$msg[] = '</tr>';
		$msg[] = '</table>';
		$msg[] = '</center>';
		
		echo implode($msg);
	}
 
	/**
	 * method to update the component
	 *
	 * @return void
	 */
	function update($parent) 
	{
		// $parent is the class calling this method

        $this->install($parent);
	}
	
	/**
	 * method to uninstall the component
	 *
	 * @return void
	 */
	function uninstall($parent) 
	{
		// $parent is the class calling this method
		
		$db = JFactory::getDBO();
		
		// foreach collection
		$query = "SELECT id FROM `#__collector`;";
		$db->setQuery( $query );
		$collections = $db->loadObjectList();

		foreach ($collections AS $collection)
		{
			// drop history table
			$query = "DROP TABLE `#__collector_items_history_".$collection->id."`";
			$db->setQuery( $query );
			$db->query();
		}
	}
}