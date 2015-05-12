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
defined('_JEXEC') or die( 'Restricted access' );

jimport('joomla.form.formfield');

/**
 * Renders a filter field element
 *
 * @package		Collector
 */
class JFormFieldRequiredfilter extends JFormField
{
	protected $type			= 'Requiredfilter';

	protected function getInput()
	{
		// Initialize variables.
		$html = array();
		$db = JFactory::getDBO();
		$doc = JFactory::getDocument();

		$js = "
		window.addEvent( 'domready', function() {
			var form = document.adminForm;
			var action = form.action;
			var reg1=new RegExp('&id=','g');
			var tab = action.split(reg1);
			var itemId = tab[1];
			var collection = $('jform_request_id').get('value');
			var url='index.php?option=com_collector&format=raw&view=menu&tmpl=component&task=menu.listRequired&collection='+collection+'&itemId='+itemId;
			var myRequest = new Request({
				url: url,
				method:'post',
				onComplete: function( response ) {
					$('listRequired').set('html',response);
				}
			});
			myRequest.send();
		});";
		
		$doc->addScriptDeclaration($js);

		$html[] = '<div id=listRequired class="fltlft">'.JText::_('COM_COLLECTOR_SELECT_COLLECTION');
		$html[] = '<input type="hidden" name="'.$this->name.'" value=""/>';
		$html[] = '</div>';
		return implode($html);
	}
}
