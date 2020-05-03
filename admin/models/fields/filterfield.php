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

jimport('joomla.form.formfield');

/**
 * Renders a filter field element
 *
 * @package		Collector
 */
class JFormFieldFilterfield extends JFormField
{
	protected $type			= 'Filterfield';

	protected function getInput()
	{
		// Initialize variables.
		$html = array();
		$db = JFactory::getDBO();
		$doc = JFactory::getDocument();

		if ( $this->element['view'] == "collection" ) {
			$js = "
			jQuery(document).ready( function() {
				var form = document.adminForm;
				var action = form.action;
				var reg1=new RegExp('&id=','g');
				var tab = action.split(reg1);
				var itemId = tab[1];
				var collection = jQuery('#jform_request_id').val();
				var url='index.php?option=com_collector&format=raw&view=menu&tmpl=component&task=menu.listFilter&collection='+collection+'&itemId='+itemId;
				jQuery.ajax({
					type: 'POST',
					url: url,
					success: function( response ) {
						jQuery('#listFilter').html(response);
						jQuery('select').chosen({
						disable_search_threshold : 10,
						allow_single_deselect : true});
					}
				});
			});";
		} else if ( $this->element['view'] == "item" ) {
			$js = "
			jQuery(document).ready( function() {
				var form = document.adminForm;
				var action = form.action;
				var reg1=new RegExp('&id=','g');
				var tab = action.split(reg1);
				var itemId = tab[1];
				var collection = jQuery('#jform_request_collection').val();
				var url='index.php?option=com_collector&format=raw&view=menu&tmpl=component&task=menu.listFilter&collection='+collection+'&itemId='+itemId;
				jQuery.ajax({
					type: 'POST',
					url: url,
					success: function( response ) {
						jQuery('#listFilter').html(response);
						jQuery('select').chosen({
						disable_search_threshold : 10,
						allow_single_deselect : true});
					}
				});
			});";
		}
		
		$doc->addScriptDeclaration($js);

		$html[] = '<div id=listFilter class="fltlft">'.JText::_('COM_COLLECTOR_SELECT_COLLECTION');
		$html[] = '<input type="hidden" name="'.$this->name.'" value=""/>';
		$html[] = '</div>';
		return implode($html);
	}
}
