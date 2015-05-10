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
defined('_JEXEC') or die('Restricted access');
 
jimport('joomla.form.formfield');

/**
 * Form Field class for the Collector.
 *
 * @package		Collector
 */
class JFormFieldYesnoimpossible extends JFormField
{
	protected $type 		= 'Yesnoimpossible';
	
	protected function getInput()
	{
		// Initialize variables.
		$html = array();
		
		// Initialize some field attributes.
		$class = $this->class ? ' class="radio '.(string) $this->element['class'].'"' : ' class="radio"';
		$required  = $this->required ? ' required aria-required="true"' : '';
		$autofocus = $this->autofocus ? ' autofocus' : '';
		$disabled  = $this->disabled ? ' disabled' : '';
		$readonly  = $this->readonly;

		// Display a text with a hidden input to store the value.
		if ( $this->readonly )
		{
			$size         = !empty($this->size) ? ' size="' . $this->size . '"' : '';
			
			$html[] = '<input type="text" name="' . $this->name . 'impossible" id="' . $this->id . '" value="'
			. JText::_('COM_COLLECTOR_PARAM_NOT_AVAILABLE') . '"' . $size . ' readonly' . ' style="text-align:center;" />';
			
			
			// $html[] = '<div style="float: left; margin: 0 0 5px 0; padding: 5px" >'.JText::_('COM_COLLECTOR_PARAM_NOT_AVAILABLE').'</div>';
			$html[] = '<input type="hidden" name="'.$this->name.'" value="0"/>';
		}
		// Create a regular list.
		else {
			// Start the radio field output.
			$html[] = '<fieldset id="' . $this->id . '"' . $class . $required . $autofocus . $disabled . ' >';
			
			// Get the field options.
			$options = $this->getOptions();
			
			// Build the radio field output.
			foreach ($options as $i => $option)
			{
				// Initialize some option attributes.
				$checked = ((string) $option->value == (string) $this->value) ? ' checked="checked"' : '';
				$class = !empty($option->class) ? ' class="'.$option->class.'"' : '';
				
				$disabled = !empty($option->disable) ? ' disabled="disabled"' : '';
	 
				// Initialize some JavaScript option attributes.
				$onclick = !empty($option->onclick) ? ' onclick="'.$option->onclick.'"' : '';
				$onchange = !empty($option->onchange) ? ' onchange="' . $option->onchange . '"' : '';
	 
				$html[] = '<input type="radio" id="' . $this->id . $i . '" name="' . $this->name . '" value="'
					. htmlspecialchars($option->value, ENT_COMPAT, 'UTF-8') . '"' . $checked . $class . $required . $onclick
					. $onchange . $disabled . '/>';
	 
				$html[] = '<label for="' . $this->id . $i . '"' . $class . '>'
					. JText::alt($option->text, preg_replace('/[^a-zA-Z0-9_\-]/', '_', $this->fieldname)) . '</label>';
				
				$required = '';
			}
			
			// End the radio field output.
			$html[] = '</fieldset>';
		}
		
		return implode($html);
	}
	
	/**
     * Method to get the field options.
     *
     * @return    array    The field option objects.
     * @since    1.6
     */
    protected function getOptions()
    {
        // Initialize variables.
        $options = array();
 
        foreach ($this->element->children() as $option)
		{
            // Only add <option /> elements.
            if ($option->getName() != 'option')
			{
                continue;
            }
 
			$disabled = (string) $option['disabled'];
			$disabled = ($disabled == 'true' || $disabled == 'disabled' || $disabled == '1');

            // Create a new option object based on the <option /> element.
            $tmp = JHtml::_(
				'select.option', (string) $option['value'], trim((string) $option), 'value', 'text',
				$disabled
			);
 
            // Set some option attributes.
            $tmp->class = (string) $option['class'];
 
            // Set some JavaScript option attributes.
            $tmp->onclick = (string) $option['onclick'];
			$tmp->onchange = (string) $option['onchange'];
 
            // Add the option object to the result set.
            $options[] = $tmp;
        }
 
        reset($options);
 
        return $options;
    }
}
?>