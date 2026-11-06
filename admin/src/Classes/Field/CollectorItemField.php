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

namespace Steevo\Component\Collector\Administrator\Classes\Field;

use Joomla\CMS\Factory;
use Joomla\CMS\Form\Field\ListField;

defined('_JEXEC') or die;

/**
 * Form Field class for the Collector.
 *
 * @package		Collector
 */
class CollectorItemField extends ListField
{
	protected $type = 'CollectorItem';
	
	protected function getOptions()
	{
		// Get a handle to the Joomla! application object
		$application = Factory::getApplication();
		
		// Get item title
		$collection = $this->element['collection'];
		
		// Create a new query object.
		$db = Factory::getDBO();
		$query = $db->getQuery(true);
		
		// Select the custom title for items.
		$query->select('custom');
		$query->from('#__collector');
		$query->where('id = '.$collection);
		$db->setQuery( $query );
		
		$custom = $db->loadResult();
		
		// Create a new query object.
		$query = $db->getQuery(true);
		
		// Select the required fields from the table.
		$query->select('f.id, f.field, f.tablecolumn, f.attribs');
		$query->from('#__collector_fields as f');
		
		// Join over the type.
		$query->select('t.type AS type');
		$query->join('LEFT', '#__collector_fields_type AS t ON t.id = f.type');
		
		// Filter by collection
		$query->where('f.collection = '.$collection);
		
		// Filter by title
		if ( $custom != '0' )
		{
			$custom = explode('/',$custom);
			$query->where('f.id IN("'.implode('","',$custom).'")');
		}
		else
		{
			$query->where('home = 1');
		}
		
		$db->setQuery( $query );
		
		$fields = $db->loadObjectList();
		
		// Reorder
		$fieldsReordered = $fields;
		if ( $custom != '0' )
		{
			$custom = array_flip($custom);
			foreach( $fields as $field )
			{
				$fieldsReordered[$custom[$field->id]] = $field;
			}
		}
		
		foreach ($fieldsReordered as $field)
		{
			$fieldObjects[] = CollectorField::getInstance( $collection, $field );
		}
		
		// Create a new query object.
		$query = $db->getQuery(true);
		
		// Select the required fields from the table.
		$query->select( 'i.id as value, i.fulltitle as text' );
		$query->from('#__collector_items AS i');
		
		// Join over the values.
		$query->join('LEFT', '#__collector_items_history_'.$collection.' AS h ON h.item = i.id');
		foreach($fieldObjects as $field)
		{
			$field->setQuery($query);
		}
		
		// Filter by collection
		$query->where('i.collection = '.$collection);
		
		// Filter by history
		$query->where('h.state = 1');
		
		$db->setQuery( $query );
		
		$results = $db->loadObjectList();
		//Check for database error
		if (!$db->execute())
		{
			return $application->enqueueMessage($db->getErrorMsg(),'warning' );
		}
		
		array_unshift($results,array('value' => 0, 'text' => ''));
		
		return $results;
	}
}