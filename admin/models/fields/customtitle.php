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
 
jimport('joomla.form.formfield');

/**
 * Form Field class for the Collector.
 *
 * @package		Collector
 */
class JFormFieldCustomTitle extends JFormField
{
	protected $type 		= 'CustomTitle';
	
	protected function getInput()
	{
		// Initialize variables.
		$html = array();
		$attr = '';
		
		// Mise en forme du titre personnalise
		if ( $this->value == 0 )
		{
			$value = array();
		}
		else
		{
			$value = explode( "/", $this->value );
		}
		
		$selected = $this->getFieldsSelected($value);
		$available = $this->getFieldsAvailable($value);
		$number_fields = count($selected) + count($available);
		
		// Initialize some field attributes.
		$attr .= $this->element['class'] ? ' class="'.(string) $this->element['class'].'"' : '';
		
		if ($number_fields == 0)
		{
			$html[] = '<input type="text" value="'.JText::_('COM_COLLECTOR_FIELD_CUSTOM_TITLE_UNABLE').'" disabled="disabled" />';
			$html[] = '<input type="hidden" name="'.$this->name.'" value="0" />';
		}
		else
		{
			$html[] = '<table><tr><td>';
			$html[] = '<a class="btn btn-micro" onclick="custom_up()" href="#"><img src="components/com_collector/assets/images/arrow_up.png" /></a>';
			$html[] = '</td><td rowspan=2 >';
						
			$html[] = '<select id="custom_selected" name="custom_selected" '.$attr.' size="'.$number_fields.'" >';
			foreach ( $selected as $field )
			{
				$html[] = '<option value="'.$field->value.'" >'.$field->text.'</option>';
			}
			$html[] = '</select>';
			$html[] = '</td><td>';
			$html[] = '<a class="btn btn-micro" onclick="custom_add()" href="#"><img src="components/com_collector/assets/images/arrow_left.png" /></a>';
			$html[] = '</td><td rowspan=2 >';

			$html[] = '<select id="custom_available" name="custom_available" '.$attr.' size="'.$number_fields.'" >';
			foreach ( $available as $field )
			{
				$html[] = '<option value="'.$field->value.'" >'.$field->text.'</option>';
			}
			$html[] = '</select>';
			$html[] = '</td></tr><tr><td>';
			
			$html[] = '<a class="btn btn-micro" onclick="custom_down()" href="#"><img src="components/com_collector/assets/images/arrow_down.png" /></a>';
			
			$html[] = '</td><td>';
			$html[] = '<a class="btn btn-micro" onclick="custom_remove()" href="#"><img src="components/com_collector/assets/images/arrow_right.png" /></a>';
			$html[] = '</td></tr></table>';
			
			
			$html[] = '<input type="hidden" id="jform_custom" name="'.$this->name.'" value="'.$this->value.'" />';
		}
		
		// Build the script.
		$script = array();
		$script[] = '    function custom_add(){';
		$script[] = '        var orig = document.getElementById( "custom_available" );';
		$script[] = '        var dest = document.getElementById( "custom_selected" );';
		$script[] = '        if (orig.options.selectedIndex<0) return false;';
		$script[] = '        var newOption = new Option(orig.options[orig.options.selectedIndex].text, orig.options[orig.options.selectedIndex].value);';
		$script[] = '        dest.options[dest.length] = newOption;';
		$script[] = '        orig.options[orig.options.selectedIndex] = null;';
    	$script[] = '        update_form_field();';
		$script[] = '    }';
		$script[] = '    function custom_remove(){';
		$script[] = '        var dest = document.getElementById( "custom_available" );';
		$script[] = '        var orig = document.getElementById( "custom_selected" );';
		$script[] = '        if (orig.options.selectedIndex<0) return false;';
		$script[] = '        var newOption = new Option(orig.options[orig.options.selectedIndex].text, orig.options[orig.options.selectedIndex].value);';
		$script[] = '        dest.options[dest.length] = newOption;';
		$script[] = '        orig.options[orig.options.selectedIndex] = null;';
    	$script[] = '        update_form_field();';
		$script[] = '    }';
		$script[] = '    function custom_up(){';
		$script[] = '        var list = document.getElementById( "custom_selected" );';
		$script[] = '        var index = list.options.selectedIndex;';
		$script[] = '        if (index<0) return false;';
		$script[] = '        if((index - 1) < 0 ) {';
        $script[] = '            return false;';
        $script[] = '        };';
		$script[] = '        var value = list.options[index].value;';
    	$script[] = '        var text = list.options[index].text;';
    	$script[] = '        list.options[index].value = list.options[index - 1].value;';
    	$script[] = '        list.options[index].text = list.options[index - 1].text;';
    	$script[] = '        list.options[index - 1].value = value;';
    	$script[] = '        list.options[index - 1].text = text;';
    	$script[] = '        list.selectedIndex = index - 1;';
    	$script[] = '        update_form_field();';
		$script[] = '    }';
		$script[] = '    function custom_down(){';
		$script[] = '        var list = document.getElementById( "custom_selected" );';
		$script[] = '        var index = list.options.selectedIndex;';
		$script[] = '        if (index<0) return false;';
		$script[] = '        if((index + 1) > (list.options.length-1)) {';
        $script[] = '            return false;';
        $script[] = '        };';
		$script[] = '        var value = list.options[index].value;';
    	$script[] = '        var text = list.options[index].text;';
    	$script[] = '        list.options[index].value = list.options[index + 1].value;';
    	$script[] = '        list.options[index].text = list.options[index + 1].text;';
    	$script[] = '        list.options[index + 1].value = value;';
    	$script[] = '        list.options[index + 1].text = text;';
    	$script[] = '        list.selectedIndex = index + 1;';
    	$script[] = '        update_form_field();';
		$script[] = '    }';
		$script[] = '    function update_form_field(){';
		$script[] = '        var input = document.getElementById( "jform_custom" );';
		$script[] = '        var list = document.getElementById( "custom_selected" );';
		$script[] = '        var total = list.options.length;';
		$script[] = '        if(total == 0) {';
        $script[] = '            input.value = 0';
        $script[] = '        } else {';
		$script[] = '            var valueArray = new Array();';
		$script[] = '            for (var i=0;i<total;i++) {';
        $script[] = '                valueArray[i] = list.options[i].value;';
        $script[] = '            }';
		$script[] = '            input.value = valueArray.join(\'/\');';
        $script[] = '        };';
		$script[] = '    }';

		// Add the script to the document head.
		JFactory::getDocument()->addScriptDeclaration(implode("\n", $script));
		
		return implode($html);
	}
	
	protected function getFieldsSelected($value)
	{
		$app = JFactory::getApplication();
		
		// Get collection Id
		$collection = $app->input->get('id', 0, '', 'int' );
		
		// Create a new query object.
		$db = JFactory::getDBO();
		$query = $db->getQuery(true);
		
		// Select the required fields from the table.
		$query->select('f.id AS value, f.field AS text');
		$query->from('#__collector_fields AS f');
		$query->join('LEFT', $db->quoteName('#__collector_fields_type') . ' AS t ON f.type = t.id');
		
		// Filter by collection
		$query->where('f.collection='.$collection);
		
		// Filter by field type
		$query->where('t.intitle = 1');
		
		// Filter by field id
		$in = implode("', '",$value);
		$query->where('f.id IN (\''.$in.'\')');
		
		// Add the list ordering clause.
		$query->order('f.ordering');
		
		$db->setQuery( $query );
		
		$results = $db->loadObjectList('value');
		
		$selected = array();
		foreach ($value as $fieldID) {
			$selected[] = $results[$fieldID];
		}
		return $selected;
	}
	
	protected function getFieldsAvailable($value)
	{
		$app = JFactory::getApplication();
		
		// Get collection Id
		$collection = $app->input->get('id', 0, '', 'int' );
		
		// Create a new query object.
		$db = JFactory::getDBO();
		$query = $db->getQuery(true);
		
		// Select the required fields from the table.
		$query->select('f.id AS value, f.field AS text');
		$query->from('#__collector_fields AS f');
		$query->join('LEFT', $db->quoteName('#__collector_fields_type') . ' AS t ON f.type = t.id');
		
		// Filter by collection
		$query->where('f.collection='.$collection);
		
		// Filter by field type
		$query->where('t.intitle = 1');
		
		// Filter by field id
		$in = implode("', '",$value);
		$query->where('f.id NOT IN (\''.$in.'\')');
		
		// Add the list ordering clause.
		$query->order('f.ordering');
		
		$db->setQuery( $query );
		
		$results = $db->loadObjectList();
		
		return $results;
	}
}
?>