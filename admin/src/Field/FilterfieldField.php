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
use Joomla\CMS\Form\FormField;
use Joomla\CMS\Language\Text;
use Joomla\Database\DatabaseInterface;

defined('JPATH_BASE') or die;

/**
 * Renders a filter field element
 *
 * @package		Collector
 */
class FilterfieldField extends FormField
{
	protected $type			= 'Filterfield';

	protected function getInput()
	{
		// Initialize variables.
		$html = array();
		$db = Factory::getContainer()->get(DatabaseInterface::class);
		$doc = Factory::getApplication()->getDocument();

		if ( $this->element['view'] == "collection" ) {
			$js = "
			jQuery(document).ready( function() {
				var form = document.adminForm;
				var action = form.action;
				var reg1=new RegExp('&id=','g');
				var tab = action.split(reg1);
				var itemId = tab[1];
				var collection = jQuery('#jform_request_id_id').val();
				var url='index.php?option=com_collector&format=raw&view=menu&tmpl=component&task=menu.listFilter&collection='+collection+'&itemId='+itemId;
				jQuery.ajax({
					type: 'POST',
					url: url,
					success: function( response ) {
						jQuery('#listFilter').html(response);
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
					}
				});
			});";
		}
		
		$doc->addScriptDeclaration($js);

		$html[] = '<div id=listFilter class="fltlft">'.Text::_('COM_COLLECTOR_SELECT_COLLECTION');
		$html[] = '<input type="hidden" name="'.$this->name.'" value=""/>';
		$html[] = '</div>';
		return implode($html);
	}
}
