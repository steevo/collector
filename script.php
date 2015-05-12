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
		
		// mise a jour #__collector
		$fields = $db->getTableColumns('#__collector', false);
		if (!array_key_exists ( 'ordering' , $fields )) {
			$query = "ALTER TABLE `#__collector` ADD `ordering` INT(11) NOT NULL after `state`";
			$db->setQuery( $query );
			$db->query();
			$query = "UPDATE `#__collector` SET `ordering` = `id`, `created_by` = ".$user->id.", `access` = access+1";
			$db->setQuery( $query );
			$db->query();
			$query = "UPDATE `#__collector` SET `modified_by` = ".$user->id." WHERE modified_by != 0";
			$db->setQuery( $query );
			$db->query();
			$query = "UPDATE `#__collector_defined` SET `created_by` = ".$user->id.", `access` = access+1";
			$db->setQuery( $query );
			$db->query();
			$query = "UPDATE `#__collector_defined` SET `modified_by` = ".$user->id." WHERE modified_by != 0";
			$db->setQuery( $query );
			$db->query();
		}
		
		// mise a jour #__collector_defined_content
		$fields = $db->getTableColumns('#__collector_defined_content', false);
		if (!array_key_exists ( 'parent_id' , $fields )) {
			// id + 1
			$query = "UPDATE `#__collector_defined_content` SET id = id + 1 ORDER BY id DESC";
			$db->setQuery( $query );
			$db->query();
			
			// parent_id
			$query = "ALTER TABLE `#__collector_defined_content` ADD `parent_id` VARCHAR(30) NOT NULL after `defined`";
			$db->setQuery( $query );
			$db->query();
			$query = "UPDATE `#__collector_defined_content` SET `parent_id` = 1";
			$db->setQuery( $query );
			$db->query();
			// level
			$query = "ALTER TABLE `#__collector_defined_content` ADD `level` INT(10) UNSIGNED NOT NULL after `parent_id`";
			$db->setQuery( $query );
			$db->query();
			$query = "UPDATE `#__collector_defined_content` SET `level` = 1";
			$db->setQuery( $query );
			$db->query();
			// path
			$query = "ALTER TABLE `#__collector_defined_content` ADD `path` VARCHAR(30) NOT NULL after `level`";
			$db->setQuery( $query );
			$db->query();
			$query = "UPDATE `#__collector_defined_content` SET `path` = CONCAT('|',id,'|')";
			$db->setQuery( $query );
			$db->query();
			// left
			$query = "ALTER TABLE `#__collector_defined_content` ADD `lft` INT(11) NOT NULL DEFAULT 0 after `image`";
			$db->setQuery( $query );
			$db->query();
			// right
			$query = "ALTER TABLE `#__collector_defined_content` ADD `rgt` INT(11) NOT NULL DEFAULT 0 after `lft`";
			$db->setQuery( $query );
			$db->query();
			
			$query = "UPDATE `#__collector_fields` SET `created_by` = ".$user->id.", `access` = access+1";
			$db->setQuery( $query );
			$db->query();
			$query = "UPDATE `#__collector_fields` SET `modified_by` = ".$user->id." WHERE modified_by != 0";
			$db->setQuery( $query );
			$db->query();
		}
		
		// mise a jour #__collector_fields
		$fields = $db->getTableColumns('#__collector_fields', false);
		if (array_key_exists ( 'defined' , $fields )) {
			// required
			$query = "ALTER TABLE `#__collector_fields` ADD `required` INT(1) NOT NULL after `sort`";
			$db->setQuery( $query );
			$db->query();
			$query = "UPDATE `#__collector_fields` SET `required` = 0";
			$db->setQuery( $query );
			$db->query();
			// attribs
			$query = "ALTER TABLE `#__collector_fields` ADD `attribs` TEXT NOT NULL after `required`";
			$db->setQuery( $query );
			$db->query();
			
			// update attribs
			$query = "SELECT * FROM `#__collector_fields`";
			$db->setQuery( $query );
			$fields = $db->loadObjectList();
			
			foreach ($fields AS $field)
			{
				switch ($field->type) {
					// champ simple ligne
					case "1":
						$attribs = array(
							'template'=>$field->template,
							'emailMode'=>'1',
							'weblinkMode'=>'1'
							);
						break;
					// champ texte
					case "2":
						$attribs = array(
							'template'=>$field->template
							);
						break;
					// champ predefini
					case "3":
						$attribs = array(
							'list'=>$field->defined,
							'show_fieldlink'=>'1',
							'hide_unselected'=>'1'
							);
						break;
					// champ objet
					case "4":
						$attribs = array(
							'collection'=>$field->defined,
							'show_fieldlink'=>'2',
							'hide_unselected'=>'1'
							);
						break;
					// champ image
					case "5":
						$attribs = array(
							'default'=>'components/com_collector/assets/images/camera.png||||||',
							'directory'=>''
							);
						break;
					// champ nombre
					case "6":
						$attribs = array(
							'template'=>$field->template
							);
						break;
					// champ date
					case "7":
						$attribs = array(
							'format'=>$field->template
							);
						break;
					// champ fichier
					case "8":
						$attribs = array(
							'default'=>'',
							'directory'=>''
							);
						break;
				}
				
				$registry = new JRegistry();
				$registry->loadArray($attribs);
				$attribs = (string)$registry;
				
				$query = "UPDATE `#__collector_fields` SET `attribs` = '".$attribs."' WHERE id = '".$field->id."'";
				$db->setQuery( $query );
				$db->query();
			}
			
			// drop defined
			$query = "ALTER TABLE `#__collector_fields` DROP `defined`";
			$db->setQuery( $query );
			$db->query();
			
			// drop template
			$query = "ALTER TABLE `#__collector_fields` DROP `template`";
			$db->setQuery( $query );
			$db->query();
		}
		
		// mise a jour #__collector_fields_type
		$fields = $db->getTableColumns('#__collector_fields_type', false);
		if (!array_key_exists ( 'state' , $fields )) {
			// state
			$query = "ALTER TABLE `#__collector_fields_type` ADD `state` TINYINT(3) NOT NULL DEFAULT 0 after `type`";
			$db->setQuery( $query );
			$db->query();
			// unikable
			$query = "ALTER TABLE `#__collector_fields_type` ADD `unikable` INT(1) NOT NULL DEFAULT 0 after `state`";
			$db->setQuery( $query );
			$db->query();
			// sortable
			$query = "ALTER TABLE `#__collector_fields_type` ADD `sortable` INT(1) NOT NULL DEFAULT 0 after `unikable`";
			$db->setQuery( $query );
			$db->query();
			// searchable
			$query = "ALTER TABLE `#__collector_fields_type` ADD `searchable` INT(1) NOT NULL DEFAULT 0 after `sortable`";
			$db->setQuery( $query );
			$db->query();
			// filterable
			$query = "ALTER TABLE `#__collector_fields_type` ADD `filterable` INT(1) NOT NULL DEFAULT 0 after `searchable`";
			$db->setQuery( $query );
			$db->query();
			// intitle
			$query = "ALTER TABLE `#__collector_fields_type` ADD `intitle` INT(1) NOT NULL DEFAULT 0 after `filterable`";
			$db->setQuery( $query );
			$db->query();
			
			// delete all
			$query = "TRUNCATE `#__collector_fields_type`";
			$db->setQuery( $query );
			$db->query();
			
			$query = "INSERT INTO `#__collector_fields_type` (`id`, `type`, `state`, `unikable`, `sortable`, `searchable`, `filterable`, `intitle`) VALUES
				(1, 'line', 1, 1, 1, 1, 0, 1),
				(2, 'text', 1, 0, 0, 1, 0, 0),
				(3, 'define', 1, 0, 1, 1, 1, 1),
				(4, 'item', 1, 0, 1, 1, 1, 1),
				(5, 'image', 1, 0, 0, 1, 0, 0),
				(6, 'number', 1, 1, 1, 1, 1, 1),
				(7, 'date', 1, 1, 1, 1, 1, 1),
				(8, 'file', 1, 0, 0, 1, 0, 0);";
			$db->setQuery( $query );
			$db->query();
		}
		
		// mise a jour #__collector_items
		$fields = $db->getTableColumns('#__collector_items', false);
		if (!array_key_exists ( 'fulltitle' , $fields )) {
			// fulltitle
			$query = "ALTER TABLE `#__collector_items` ADD `fulltitle` TEXT NOT NULL after `alias`";
			$db->setQuery( $query );
			$db->query();
			
			$query = "UPDATE `#__collector_items` SET `created_by` = ".$user->id.", `access` = access+1";
			$db->setQuery( $query );
			$db->query();
			$query = "UPDATE `#__collector_items` SET `modified_by` = ".$user->id." WHERE modified_by != 0";
			$db->setQuery( $query );
			$db->query();
		
			// mise a jour #__collector_items_history_
			
			// foreach collection
			$query = "SELECT id FROM `#__collector`;";
			$db->setQuery( $query );
			$collections = $db->loadObjectList();

			foreach ($collections AS $collection)
			{
				// foreach field type defined
				$query = "SELECT * FROM `#__collector_fields`";
				$query .= " WHERE collection = '".$collection->id."'";
				$db->setQuery( $query );
				$fields = $db->loadObjectList();

				foreach ($fields AS $field)
				{
					if ($field->type == 3)
					{
						$query = "UPDATE `#__collector_items_history_".$collection->id."` SET ".$field->tablecolumn." = '' WHERE ".$field->tablecolumn."='0'";
						$db->setQuery( $query );
						$db->query();
						$query = "UPDATE `#__collector_items_history_".$collection->id."` SET ".$field->tablecolumn." = CAST(".$field->tablecolumn." AS UNSIGNED)+1 WHERE ".$field->tablecolumn."!=''";
						$db->setQuery( $query );
						$db->query();
					}
					
					if ($field->type == 5)
					{
						$namefield = $field->tablecolumn;
						
						// foreach field type defined
						$query = "SELECT * FROM `#__collector_items_history_".$collection->id."`";
						$db->setQuery( $query );
						$items = $db->loadObjectList();
						
						foreach ($items AS $item)
						{
							$old_value = $item->$namefield;
							$url = explode('|',$old_value);
							$new_value = $url[0].'||||||';
							
							$query = "UPDATE `#__collector_items_history_".$collection->id."` SET ".$field->tablecolumn." = '".$new_value."' WHERE id=".$item->id;
							$db->setQuery( $query );
							$db->query();
						}
					}
				}
				
				$query = "UPDATE `#__collector_items_history_".$collection->id."` SET `modified_by` = ".$user->id;
				$db->setQuery( $query );
				$db->query();
			}
		}
		
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

		// check for fields types
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
		$msg[] = '<img src="' . $imagepath . '" border="0" alt="Collector" /><br />Version 0.6.0';
		$msg[] = '</td>';
		$msg[] = '</tr>';
		$msg[] = '<tr>';
		$msg[] = '<td style="border:1px solid #999;background-color:aliceblue;" colspan="2">';
		$msg[] = '<b>' . JText::_('COM_COLLECTOR_INSTALL') . '</b>';
		$msg[] = '</td>';
		$msg[] = '</tr>';
		// $msg[] = '<tr>';
		// $msg[] = '<td>';
		// $msg[] = '<a style="margin:10px;" class="btn" href="index.php?option=com_collector&view=update&from=15"><span class="icon-database"></span>&nbsp;' . JText::_('COM_COLLECTOR_UPDATE_FROM_15') . '</a>';
		// $msg[] = '</td>';
		// $msg[] = '<td>';
		// $msg[] = '<a style="margin:10px;" class="btn" href="index.php?option=com_collector&view=update&from=25"><span class="icon-database"></span>&nbsp;' . JText::_('COM_COLLECTOR_UPDATE_FROM_25') . '</a>';
		// $msg[] = '</td>';
		// $msg[] = '</tr>';
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