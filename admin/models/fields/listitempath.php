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
defined('_JEXEC') or die('Restricted access');

JFormHelper::loadFieldClass('list');
// jimport('joomla.form.formfield');

/**
 * Form Field class for the Collector.
 *
 * @package		Collector
 */
class JFormFieldListItemPath extends JFormFieldList
{
	/**
	 * The form field type.
	 *
	 * @var        string
	 * @since   1.6
	 */
	protected $type 		= 'ListItemPath';
	
	/**
	 * Method to get the field input markup for a generic list.
	 * Use the multiple attribute to enable multiselect.
	 *
	 * @return  string  The field input markup.
	 *
	 * @since   11.1
	 */
	protected function getInput()
	{
		$html = array();
		$attr = '';

		// Initialize some field attributes.
		$attr .= !empty($this->class) ? ' class="' . $this->class . '"' : '';
		$attr .= !empty($this->size) ? ' size="' . $this->size . '"' : '';
		$attr .= $this->multiple ? ' multiple' : '';
		$attr .= $this->required ? ' required aria-required="true"' : '';
		$attr .= $this->autofocus ? ' autofocus' : '';

		// To avoid user's confusion, readonly="true" should imply disabled="true".
		if ((string) $this->readonly == '1' || (string) $this->readonly == 'true' || (string) $this->disabled == '1'|| (string) $this->disabled == 'true')
		{
			$attr .= ' disabled="disabled"';
		}

		// Initialize JavaScript field attributes.
		$attr .= $this->onchange ? ' onchange="' . $this->onchange . '"' : '';

		// Get the field options.
		$options = (array) $this->getOptions();
		
		if ( count($options) == 1 )
		{
			$this->value = '';
			$html[] = '<input type="hidden" name="' . $this->name . '" value="' . $this->value . '"/>';
		}
		else
		{
			// Create a read-only list (no name) with a hidden input to store the value.
			if ((string) $this->readonly == '1' || (string) $this->readonly == 'true')
			{
				$html[] = JHtml::_('select.genericlist', $options, '', trim($attr), 'value', 'text', $this->value, $this->id);
				$html[] = '<input type="hidden" name="' . $this->name . '" value="' . $this->value . '"/>';
			}
			else
			// Create a regular list.
			{
				$html[] = JHtml::_('select.genericlist', $options, $this->name, trim($attr), 'value', 'text', $this->value, $this->id);
			}
		}

		return implode($html);
	}
	
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
			->select('c.path AS value, c.content AS text, c.level')
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

		if ($levelMax = $this->form->getValue('filter.level'))
		{
			if ($levelMax != '')
			{
				$query->where('c.level < ' . $db->quote($levelMax));
			}
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
?>