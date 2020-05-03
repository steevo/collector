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

JFormHelper::loadFieldClass('hidden');

/**
 * Renders a filter field element
 *
 * @package		Collector
 */
class JFormFieldLoadscripts extends JFormFieldHidden
{
	protected $type			= 'Loadscripts';

	protected function getInput()
	{
		// Initialize variables.
		$doc = JFactory::getDocument();
		
		$js = "
		jQuery(document).ready( function() {
			var form = document.adminForm;
			var action = form.action;
			var reg1=new RegExp('&id=','g');
			var tab = action.split(reg1);
			var itemId = tab[1];
			var collection = jQuery('#jform_request_id').val();
			var url='index.php?option=com_collector&format=raw&view=menu&tmpl=component&task=menu.loadScripts&collection='+collection+'&itemId='+itemId;
			jQuery.getScript( url );
		});";
		
		$doc->addScriptDeclaration($js);

		return;
	}

	/**
	 * Method to get a control group with label and input.
	 *
	 * @param   array  $options  Options to be passed into the rendering of the field
	 *
	 * @return  string  A string containing the html for the control group
	 *
	 * @since   3.2
	 */
	public function renderField($options = array())
	{
		$this->getInput();
		return '';
	}
}
