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
defined('_JEXEC') or die('Restricted access');
 
JFormHelper::loadFieldClass('list');

/**
 * Form Field class for the Collector.
 *
 * @package		Collector
 */
class JFormFieldType extends JFormFieldList
{
	protected $type 		= 'Type';
	
	/**
	 * Method to get the field options.
	 *
	 * @return    array    The field option objects.
	 */
	protected function getOptions()
	{
		// Create a new query object.
		$db = JFactory::getDBO();
		$query = $db->getQuery(true);
		
		// Select the required fields from the table.
		$query->select('id AS value, type AS text');
		$query->from('#__collector_fields_type');
		$query->where('state = 1');
		
		// Add the list ordering clause.
		$query->order('id');
		
		$db->setQuery( $query );
		
		$options = $db->loadObjectList();
		
		foreach($options AS $option)
		{
			// load language
			$extension = 'com_collector';		
			$basePath = JPATH_ROOT.'/administrator/components/com_collector/classes/field/'.strtolower($option->text);
			
			$language = JFactory::getLanguage();
			$language->load($extension, $basePath);
			
			$option->text = JText::_('COM_COLLECTOR_'.$option->text);
		}
		
		// Merge any additional options in the XML definition.
		$options = array_merge(parent::getOptions(), $options);
		
		return $options;
	}
}
?>