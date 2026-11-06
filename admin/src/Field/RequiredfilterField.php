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

defined('JPATH_BASE') or die;

/**
 * Renders a filter field element
 *
 * @package		Collector
 */
class RequiredfilterField extends FormField
{
	protected $type			= 'Requiredfilter';

	protected function getInput()
	{
		// Initialize variables.
		$html = array();
		$db = Factory::getDBO();
		$doc = Factory::getDocument();

		$js = "
		jQuery(document).ready( function() {
			var form = document.adminForm;
			var action = form.action;
			var reg1=new RegExp('&id=','g');
			var tab = action.split(reg1);
			var itemId = tab[1];
			var collection = jQuery('#jform_request_id_id').val();
			var url='index.php?option=com_collector&format=raw&view=menu&tmpl=component&task=menu.listRequired&collection='+collection+'&itemId='+itemId;
			jQuery.ajax({
				type: 'POST',
				url: url,
				success: function( response ) {
					jQuery('#listRequired').html(response);
					jQuery('.radio.btn-group label').addClass('btn');
					jQuery('.btn-group input[checked=checked]').each(function()
						{
							if (jQuery(this).val() == '') {
								jQuery('label[for=' + jQuery(this).attr('id') + ']').addClass('active btn-primary');
							} else if (jQuery(this).val() == 0) {
								jQuery('label[for=' + jQuery(this).attr('id') + ']').addClass('active btn-danger');
							} else {
								jQuery('label[for=' + jQuery(this).attr('id') + ']').addClass('active btn-success');
							}
					});
				}
			});
		});";
		
		$doc->addScriptDeclaration($js);

		$html[] = '<div id=listRequired class="fltlft">'.Text::_('COM_COLLECTOR_SELECT_COLLECTION');
		$html[] = '<input type="hidden" name="'.$this->name.'" value=""/>';
		$html[] = '</div>';
		return implode($html);
	}
}
