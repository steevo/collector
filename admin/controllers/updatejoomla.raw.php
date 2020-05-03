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

jimport('joomla.application.component.controller');

/**
 * Update Controller
 *
 * @package  	Collector
 */
class CollectorControllerUpdatejoomla extends JControllerLegacy
{
	/**
	 * Method to display an html select of defined lists
	 * For AJAX request
	 *
	 * @access	public
	 */
	function update()
	{
		$db = JFactory::getDBO();
		
		$updated_items = 0;
		$limit = 50;
		
		// create field 'asset_id' in fields table
		$query = "ALTER TABLE `#__collector_fields` ADD `attribs` varchar(5120) NOT NULL AFTER `template`";
		$db->setQuery( $query );
		$db->execute();
		
		$query = "ALTER TABLE `#__collector_fields` ADD `done` INT NOT NULL DEFAULT '0'";
		$db->setQuery( $query );
		$db->execute();
		
		$query = "ALTER TABLE `#__collector_fields` ADD `required` INT NOT NULL DEFAULT '0' AFTER `checked_out_time`";
		$db->setQuery( $query );
		$db->execute();
		
		$query = "SELECT * FROM `#__collector_fields` WHERE done != '1'";
		$db->setQuery( $query );
		$db->execute();
		$remaining = $db->getNumRows();
		
		if ($remaining == 0)
		{
			$query = "ALTER TABLE `#__collector_fields` DROP `done`";
			$db->setQuery( $query );
			$db->execute();
			
			$query = "ALTER TABLE `#__collector_fields` DROP `template`";
			$db->setQuery( $query );
			$db->execute();
			
			$query = "ALTER TABLE `#__collector_fields` DROP `defined`";
			$db->setQuery( $query );
			$db->execute();
		}
		else
		{
			$query = "SELECT * FROM `#__collector_fields`";
			$query .= " WHERE done != '1'";
			$query .= " LIMIT ".$limit;
			
			$db->setQuery( $query );
			$fields = $db->loadObjectList();
				
			foreach ($fields AS $field)
			{
				$updated_items = $updated_items + 1;
				
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
							'list'=>$field->defined
							);
						break;
					// champ objet
					case "4":
						$attribs = array(
							'collection'=>$field->defined
							);
						break;
					// champ image
					case "5":
						$attribs = array(
							'default'=>'components/com_collector/assets/images/camera.png',
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
				$db->execute();
				
				$query = "UPDATE `#__collector_fields` SET `done` = '1' WHERE id = '".$field->id."'";
				$db->setQuery( $query );
				$db->execute();
			}
		}
		$remaining = $remaining - $updated_items;
		$response = array( 'updated' => $updated_items, 'remaining' => $remaining );
		
		echo json_encode( $response );

	}
}