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

defined('JPATH_BASE') or die;

JFormHelper::loadFieldClass('list');

/**
 * Form Field class for the Collector.
 *
 * @package		Collector
 */
class JFormFieldListitemParent extends JFormFieldList
{
	/**
	 * The form field type.
	 *
	 * @var        string
	 * @since   1.6
	 */
	protected $type = 'ListitemParent';

	/**
	 * Method to get the field options.
	 *
	 * @return  array  The field option objects.
	 * @since   1.6
	 */
	protected function getOptions()
	{
		$options = array();

		$db = JFactory::getDbo();
		$query = $db->getQuery(true)
			->select('c.id AS value, c.content AS text, c.level')
			->from('#__collector_defined_content AS c')
			->join('LEFT', $db->quoteName('#__collector_defined_content') . ' AS b ON c.lft > b.lft AND c.rgt < b.rgt');

		if ($defined = $this->form->getValue('defined'))
		{
			$query->where('c.defined = ' . $db->quote($defined));
		}
		else
		{
			$query->where('c.defined != ' . $db->quote(''));
		}

		// Prevent parenting to children of this item.
		if ($id = $this->form->getValue('id'))
		{
			$query->join('LEFT', $db->quoteName('#__collector_defined_content') . ' AS p ON p.id = ' . (int) $id)
				->where('NOT(c.lft >= p.lft AND c.rgt <= p.rgt)');
		}

		$query->group('c.id, c.content, c.level, c.lft, c.rgt, c.defined, c.parent_id')
			->order('c.lft ASC');

		// Get the options.
		$db->setQuery($query);

		try
		{
			$options = $db->loadObjectList();
		}
		catch (RuntimeException $e)
		{
			JFactory::getApplication()->enqueueMessage($e->getMessage(),'warning');
		}

		// Pad the option text with spaces using depth level as a multiplier.
		for ($i = 0, $n = count($options); $i < $n; $i++)
		{
			$options[$i]->text = str_repeat('- ', $options[$i]->level-1) . $options[$i]->text;
		}

		// Merge any additional options in the XML definition.
		$options = array_merge(parent::getOptions(), $options);

		return $options;
	}
}
