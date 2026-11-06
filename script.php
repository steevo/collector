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

defined('_JEXEC') or die;

use Joomla\CMS\Application\ApplicationHelper;
use Joomla\CMS\Factory;
use Joomla\CMS\Filesystem\File;
use Joomla\CMS\Filesystem\Folder;
use Joomla\CMS\Installer\InstallerAdapter;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Table\Table;

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
	public function install($parent) 
	{
		$user	= Factory::getApplication()->getIdentity();
		$params   = JComponentHelper::getParams('com_languages');
		$frontend_lang = $params->get('site', 'en-GB');
		$language = JLanguage::getInstance($frontend_lang);
		
		// get language file for default layouts
		$language = Factory::getLanguage();
		$language->load('com_collector');
		$db = Factory::getDBO();
		
		// check for root item
		$query = "SELECT id from `#__collector_defined_content` WHERE id=1;";
		$db->setQuery( $query );
		if (!$result = $db->LoadResult())
		{
			$query = "INSERT INTO `#__collector_defined_content` SET `id` = 1,`defined` = 0,`parent_id` = 0,`level` = 0,`path` = '',`content` = 'List_Item_Root',`image` = '', `lft` = 0,`rgt` = 1;";
			$db->setQuery( $query );
			$db->execute();
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
			$db->execute();
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
			$db->execute();
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
			$db->execute();
		}
		
		$imagepath = JUri::root().'administrator/components/com_collector/assets/images/collector_logo.png';
		
		$msg = array();
		$msg[] = '<center>';
		$msg[] = '<table width="100%" style="border:0px; margin-bottom:10px;">';
		$msg[] = '<tr>';
		$msg[] = '<td align="center" colspan="2">';
		$msg[] = '<img src="' . $imagepath . '" border="0" alt="Collector" /><br />Version 0.7.0';
		$msg[] = '</td>';
		$msg[] = '</tr>';
		$msg[] = '<tr>';
		$msg[] = '<td style="border:1px solid #999;background-color:aliceblue;" colspan="2">';
		$msg[] = '<b>' . Text::_('COM_COLLECTOR_INSTALL') . '</b>';
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
	public function update($parent) 
	{
		// $parent is the class calling this method

        $this->install($parent);
		
		$db = Factory::getDBO();
		$query = "SHOW COLUMNS FROM `#__collector_userslists` LIKE 'type'";
		$db->setQuery( $query );
		$db->execute();
		$exist = $db->getNumRows();
		
		if (!$exist) {
			$query = "ALTER TABLE `#__collector_userslists` ADD `type` INT(11) NOT NULL after `collection`";
			$db->setQuery( $query );
			$db->execute();
		}

		// Userlists creation if not exists
		// foreach collection
		$query = "SELECT id FROM `#__collector`;";
		$db->setQuery( $query );
		$collections = $db->loadObjectList();

		foreach ($collections AS $collection)
		{
			// collection InnoDB
			$query = "ALTER TABLE `#__collector_items_history_".$collection->id."` ENGINE=InnoDB;";
			$db->setQuery( $query );
			$db->execute();
			
			$table = '#__collector_items_history_' . (int) $collection->id;
			// Vérifier si la colonne existe déjà
			$db->setQuery('SHOW COLUMNS FROM ' . $db->quoteName($table) . ' LIKE ' . $db->quote('refuse_note'));
			if (!$db->loadResult()) {
				$db->setQuery('ALTER TABLE ' . $db->quoteName($table) . ' ADD COLUMN `refuse_note` text DEFAULT NULL AFTER `modification`');
				$db->execute();
			}
			
			// collection list
			$query = "SELECT id FROM `#__collector_userslists` WHERE collection = '".$collection->id."' AND type = '2'";
			$db->setQuery( $query );
			if (!$result = $db->LoadResult())
			{
				$query = "INSERT INTO `#__collector_userslists` SET `collection` = '".$collection->id."',`type` = 2,`name` = '".$db->escape(Text::_('COM_COLLECTOR_USERLIST_COLLECTION'))."',`alias` = '".ApplicationHelper::stringURLSafe(Text::_('COM_COLLECTOR_USERLIST_COLLECTION'))."',`description` = '0',`state` = '0',`ordering` = '0',`created` = '".Factory::getDate()->toSql()."',`created_by` = '".Factory::getUser()->get('id')."',`publish_up` = '".Factory::getDate()->toSql()."',`access` = '1';";
				$db->setQuery( $query );
				$db->execute();
			}
			
			// wish list
			$query = "SELECT id FROM `#__collector_userslists` WHERE collection = '".$collection->id."' AND type = '0'";
			$db->setQuery( $query );
			if (!$result = $db->LoadResult())
			{
				$query = "INSERT INTO `#__collector_userslists` SET `collection` = '".$collection->id."',`type` = 0,`name` = '".$db->escape(Text::_('COM_COLLECTOR_USERLIST_WISH_LIST'))."',`alias` = '".ApplicationHelper::stringURLSafe(Text::_('COM_COLLECTOR_USERLIST_WISH_LIST'))."',`description` = '0',`state` = '0',`ordering` = '0',`created` = '".Factory::getDate()->toSql()."',`created_by` = '".Factory::getUser()->get('id')."',`publish_up` = '".Factory::getDate()->toSql()."',`access` = '1';";
				$db->setQuery( $query );
				$db->execute();
			}
			
			// swap list
			$query = "SELECT id FROM `#__collector_userslists` WHERE collection = '".$collection->id."' AND type = '1'";
			$db->setQuery( $query );
			if (!$result = $db->LoadResult())
			{
				$query = "INSERT INTO `#__collector_userslists` SET `collection` = '".$collection->id."',`type` = 1,`name` = '".$db->escape(Text::_('COM_COLLECTOR_USERLIST_SWAP_LIST'))."',`alias` = '".ApplicationHelper::stringURLSafe(Text::_('COM_COLLECTOR_USERLIST_SWAP_LIST'))."',`description` = '0',`state` = '0',`ordering` = '0',`created` = '".Factory::getDate()->toSql()."',`created_by` = '".Factory::getUser()->get('id')."',`publish_up` = '".Factory::getDate()->toSql()."',`access` = '1';";
				$db->setQuery( $query );
				$db->execute();
			}
		}

		$db = Factory::getDBO();
		$query = "SHOW COLUMNS FROM `#__collector_fields` LIKE 'next_sorted_field'";
		$db->setQuery( $query );
		$db->execute();
		$exist = $db->getNumRows();

		if (!$exist) {
			$query = "ALTER TABLE `#__collector_fields` ADD `next_sorted_field` varchar(30) NOT NULL default '0' after `required`";
			$db->setQuery( $query );
			$db->execute();
		}

		$db = Factory::getDBO();
		$query = "SHOW COLUMNS FROM `#__collector_fields` LIKE 'column_width'";
		$db->setQuery( $query );
		$db->execute();
		$exist = $db->getNumRows();

		if (!$exist) {
			$query = "ALTER TABLE `#__collector_fields` ADD `column_width` varchar(30) NOT NULL default '' after `next_sorted_field`";
			$db->setQuery( $query );
			$db->execute();
		}
		
		$repertoireObsolete = JPATH_SITE . '/administrator/components/com_collector/layouts/joomla/searchtools';

        // 2. Vérifier si le répertoire existe avant de tenter la suppression
        if (Folder::exists($repertoireObsolete)) {
            try {
                // 3. Supprimer le fichier
                Folder::delete($repertoireObsolete);
                
                // Optionnel : Log ou message pour dire que c'est fait
                // echo "<p>Fichier obsolète supprimé : " . basename($repertoireObsolete) . "</p>";
            } catch (\Exception $e) {
                // Gérer l'erreur silencieusement ou l'afficher
                Factory::getApplication()->enqueueMessage('Erreur lors de la suppression : ' . $e->getMessage(), 'warning');
            }
        }
	}
	
	/**
	 * method to uninstall the component
	 *
	 * @return void
	 */
	public function uninstall($parent) 
	{
		// $parent is the class calling this method
		
		$db = Factory::getDBO();
		
		// foreach collection
		$query = "SELECT id FROM `#__collector`;";
		$db->setQuery( $query );
		$collections = $db->loadObjectList();

		foreach ($collections AS $collection)
		{
			// drop history table
			$query = "DROP TABLE `#__collector_items_history_".$collection->id."`";
			$db->setQuery( $query );
			$db->execute();
		}

		/* delete comments */
		$jcomments = JPATH_SITE . DIRECTORY_SEPARATOR .'components' . DIRECTORY_SEPARATOR . 'com_jcomments' . DIRECTORY_SEPARATOR . 'jcomments.php';
		if (file_exists($jcomments)) {
			$query = "DELETE FROM #__jcomments "
				. " WHERE object_group = " . $db->Quote('com_collector');
			$db->setQuery($query);
			$db->execute();
			$query = "DELETE FROM #__jcomments_objects "
				. " WHERE object_group = " . $db->Quote('com_collector');
			$db->setQuery($query);
			$db->execute();
		}
	}
}