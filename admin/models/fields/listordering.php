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
class JFormFieldListOrdering extends JFormFieldList
{
	/**
	 * The form field type.
	 *
	 * @var        string
	 * @since   1.7
	 */
	protected $type = 'ListOrdering';

	/**
	 * Method to get the list of siblings in a menu.
	 * The method requires that parent be set.
	 *
	 * @return  array  The field option objects or false if the parent field has not been set
	 * @since   1.7
	 */
	protected function getOptions()
	{
		$options = array();

		// Get the parent
		$parent_id = $this->form->getValue('parent_id', 0);
		
		if (empty($parent_id)&&($parent_id!=1))
		{
			return false;
		}
		$db = JFactory::getDbo();
		$query = $db->getQuery(true)
			->select('c.id AS value, c.content AS text')
			->from('#__collector_defined_content AS c')
			->where('c.parent_id =' . (int) $parent_id);
		if ($defined = $this->form->getValue('defined'))
		{
			$query->where('c.defined = ' . $db->quote($defined));
		}
		else
		{
			$query->where('c.defined != ' . $db->quote(''));
		}

		$query->order('c.lft ASC');

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

		$options = array_merge(
			array(array('value' => '-1', 'text' => JText::_('COM_COLLECTOR_LISTITEM_FIELD_ORDERING_VALUE_FIRST'))),
			$options,
			array(array('value' => '-2', 'text' => JText::_('COM_COLLECTOR_LISTITEM_FIELD_ORDERING_VALUE_LAST')))
		);

		// Merge any additional options in the XML definition.
		$options = array_merge(parent::getOptions(), $options);

		return $options;
	}

	/**
	 * Method to get the field input markup
	 *
	 * @return  string  The field input markup.
	 * @since   1.7
	 */
	protected function getInput()
	{
		if ($this->form->getValue('id', 0) == 0)
		{
			return '<span class="readonly">' . JText::_('COM_COLLECTOR_LISTITEM_FIELD_ORDERING_TEXT') . '</span>';
		}
		else
		{
			return parent::getInput();
		}
	}
}
