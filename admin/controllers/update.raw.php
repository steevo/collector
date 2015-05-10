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
 * Update Controller
 *
 * @package  	Collector
 */
class CollectorControllerUpdate extends JControllerLegacy
{
	/**
	 * Method to display an html select of defined lists
	 * For AJAX request
	 *
	 * @access	public
	 */
	function update15()
	{
		$db = JFactory::getDBO();
		
		$updated_items = 0;
		$limit = 50;
		
		$columns = $db->getTableColumns('#__collector_items');
		if(!isset($columns['done'])){
			$query = "ALTER TABLE `#__collector_items` ADD `done` INT NOT NULL DEFAULT '0'";
			$db->setQuery( $query );
			$db->execute();
		}
		
		$query = "SELECT * FROM `#__collector_items` WHERE done != '1'";
		$db->setQuery( $query );
		$db->execute();
		$remaining = $db->getNumRows();
		
		if ($remaining == 0)
		{
			$query = "ALTER TABLE `#__collector_items` DROP `done`";
			$db->setQuery( $query );
			$db->execute();
			
			$query = "DROP TABLE `#__collector_items_history`";
			$db->setQuery( $query );
			$db->execute();
			
			$query = "DROP TABLE `#__collector_items_values`";
			$db->setQuery( $query );
			$db->execute();
		}
		else
		{
			$query = "SELECT id FROM `#__collector`;";
			$db->setQuery( $query );
			$collections = $db->loadObjectList();

			foreach ($collections AS $collection)
			{
				// create history table
				$query = "CREATE TABLE IF NOT EXISTS `#__collector_items_history_".$collection->id."` (
			`id` int(11) NOT NULL auto_increment,
			`item` int(11) NOT NULL,
			`state` tinyint(3) NOT NULL default '0',
			`modified` datetime NOT NULL default '0000-00-00 00:00:00',
			`modified_by` int(11) unsigned NOT NULL default '0',
			`metakey` text NOT NULL,
			`metadesc` text NOT NULL,
			`metadata` text NOT NULL,
			`modification` text NOT NULL default '',
			PRIMARY KEY (`id`),
			KEY `idx_item` (`item`),
			KEY `idx_modifiedby` (`modified_by`)
		) ENGINE=MyISAM;";

				$db->setQuery( $query );
				$db->execute();
				
				$query = "SELECT * FROM `#__collector_fields`";
				$query .= " WHERE collection = '".$collection->id."'";
				$db->setQuery( $query );
				$fields = $db->loadObjectList();

				$lastfield = "modification";
				
				foreach ($fields AS $field)
				{
					$tablecolumn = strtolower(preg_replace('/[^A-Za-z0-9_]/', '', $field->field));
					$field->tablecolumn = $tablecolumn;
					$query = "UPDATE `#__collector_fields` SET `tablecolumn` = '".$tablecolumn."' WHERE id = '".$field->id."';";
					$db->setQuery( $query );
					$db->execute();
					
					$columns = $db->getTableColumns('#__collector_items_history_'.$collection->id);
					if(!isset($columns[$tablecolumn])){
						$query = "ALTER TABLE `#__collector_items_history_".$collection->id."` ADD `".$tablecolumn."` TEXT NOT NULL default '' AFTER  `".$lastfield."`";
						$db->setQuery( $query );
						$db->execute();
					}
					
					$lastfield = $tablecolumn;
				}
				
				$query = "SELECT * FROM `#__collector_items`";
				$query .= " WHERE collection = '".$collection->id."'";
				$query .= " AND done != '1'";
				$query .= " LIMIT ".$limit;
				
				$db->setQuery( $query );
				$items = $db->loadObjectList();
				
				foreach ($items AS $item)
				{
					$updated_items = $updated_items + 1;
					$limit = $limit - 1;
					
					$query = "SELECT * FROM `#__collector_items_history`";
					$query .= " WHERE item = '".$item->id."'";
					$db->setQuery( $query );
					$histories = $db->loadObjectList();
					
					foreach ($histories AS $history)
					{
						$query = "INSERT INTO `#__collector_items_history_".$collection->id."`";
						$query .= " (
		`id` ,`item`, `state` ,`modified` ,`modified_by` ,`metakey` ,`metadesc` ,`metadata` ,`modification`";
						foreach ($fields AS $field)
						{
							$query .= " ,`".$field->tablecolumn."`";
						}
						$query .= ") VALUES (";
						$query .= "'',";
						$query .= $db->quote( $db->escape( $item->id ), false ).",";
						$query .= $db->quote( $db->escape( $history->state ), false ).",";
						$query .= $db->quote( $db->escape( $history->modified ), false ).",";
						$query .= $db->quote( $db->escape( $history->modified_by ), false ).",";
						$query .= $db->quote( $db->escape( $history->metakey ), false ).",";
						$query .= $db->quote( $db->escape( $history->metadesc ), false ).",";
						$query .= $db->quote( $db->escape( $history->metadata ), false ).",";
						$query .= $db->quote( $db->escape( $history->modification ), false );
						foreach ($fields AS $field)
						{
							$query2 = "SELECT value FROM `#__collector_items_values` WHERE field = '".$field->id."' AND history = '".$history->id."'";
							$db->setQuery( $query2 );
							$value = $db->loadResult();
							
							$query .= ",".$db->quote( $db->escape( $value ), false );
						}
						$query .= ");";
						
						$db->setQuery( $query );
						$db->execute();
						
						$query = "DELETE FROM `#__collector_items_values WHERE history` = '".$history->id."'";
						$db->setQuery( $query );
						$db->execute();
					}
					$query = "UPDATE `#__collector_items` SET `done` = '1' WHERE id = '".$item->id."'";
					$db->setQuery( $query );
					$db->execute();
				}
			}
		}
		$remaining = $remaining - $updated_items;
		$response = array( 'updated' => $updated_items, 'remaining' => $remaining );
		
		echo json_encode( $response );

	}
}