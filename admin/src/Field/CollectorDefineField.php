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

namespace Steevo\Component\Collector\Administrator\Field;

use Joomla\CMS\Factory;
use Joomla\CMS\Form\Field\ListField;
use Joomla\Database\DatabaseInterface;

defined('_JEXEC') or die;


/**
 * Form Field class for the Collector.
 *
 * @package		Collector
 */
class CollectorDefineField extends ListField
{
	protected $type = 'CollectorDefine';
	
	protected function getOptions()
	{
		// Get a handle to the Joomla! application object
		$application = Factory::getApplication();

		// Get defined Id
		$defined = $this->element['list'];
		
		// Create a new query object.
		$db = Factory::getContainer()->get(DatabaseInterface::class);
		$query = $db->getQuery(true);
		
		// Select the required fields from the table.
		$query = $db->getQuery(true)
			->select('c.id AS value, c.content AS text, c.level')
			->from('#__collector_defined_content AS c')
			->join('LEFT', $db->quoteName('#__collector_defined_content') . ' AS b ON c.lft > b.lft AND c.rgt < b.rgt')
			->where('c.defined = ' . $db->quote($defined));
		
		$query->group('c.id, c.content, c.level, c.lft, c.rgt, c.defined, c.parent_id')
			->order('c.lft ASC');
		
		// Get the options.
		$db->setQuery( $query );
		
		try
		{
			$options = $db->loadObjectList();
		}
		catch (RuntimeException $e)
		{
			$application->enqueueMessage($e->getMessage(),'warning');
		}

		// Pad the option text with spaces using depth level as a multiplier.
		for ($i = 0, $n = count($options); $i < $n; $i++)
		{
			$options[$i]->text = str_repeat('- ', $options[$i]->level-1) . $options[$i]->text;
		}
		
		array_unshift($options,array('value' => 0, 'text' => ''));
		
		return $options;
	}
}