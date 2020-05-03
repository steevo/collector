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
JHtml::_('behavior.multiselect');

/**
 * Form Field class for the Collector.
 *
 * @package		Collector
 */
class JFormFieldCollection extends JFormFieldList
{
	protected $type 		= 'Collection';
	
	protected function getInput()
	{
		// Initialize JavaScript.
		if ( preg_match("/loadFilter/",$this->element['onchange']) ) {
			$doc = JFactory::getDocument();

			$js = "
			function loadFilter() {
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
			}";
			$doc->addScriptDeclaration($js);
		}
		if ( preg_match("/loadRequired/",$this->element['onchange']) ) {
			$doc = JFactory::getDocument();

			$js = "
			function loadRequired() {
				var form = document.adminForm;
				var action = form.action;
				var reg1=new RegExp('&id=','g');
				var tab = action.split(reg1);
				var itemId = tab[1];
				var collection = jQuery('#jform_request_id').val();
				var url='index.php?option=com_collector&format=raw&view=menu&tmpl=component&task=menu.listRequired&collection='+collection+'&itemId='+itemId;
				jQuery.ajax({
					type: 'POST',
					url: url,
					success: function( response ) {
						jQuery('#listRequired').html(response);
						new Joomla.JMultiSelect('adminForm');
						
						jQuery('.radio.btn-group label').addClass('btn');
						jQuery('.btn-group label:not(.active)').click(function()
						{
							var label = jQuery(this);
							var input = jQuery('#' + label.attr('for'));

							if (!input.prop('checked')) {
								label.closest('.btn-group').find('label').removeClass('active btn-success btn-danger btn-primary');
								if (input.val() == '') {
									label.addClass('active btn-primary');
								} else if (input.val() == 0) {
									label.addClass('active btn-danger');
								} else {
									label.addClass('active btn-success');
								}
								input.prop('checked', true);
								input.trigger('change');
							}
						});
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
			}";
			$doc->addScriptDeclaration($js);
		}
		if ( preg_match("/loadUserlists/",$this->element['onchange']) ) {
			$doc = JFactory::getDocument();

			$js = "
			function loadUserlists() {
				var form = document.adminForm;
				var action = form.action;
				var reg1=new RegExp('&id=','g');
				var tab = action.split(reg1);
				var itemId = tab[1];
				var collection = jQuery('#jform_request_id').val();
				var url='index.php?option=com_collector&format=raw&view=menu&tmpl=component&task=menu.loadUserlists&collection='+collection+'&itemId='+itemId;
				jQuery.ajax({
					type: 'POST',
					url: url,
					success: function( response ) {
						jQuery('#userslists').html(response);
						new Joomla.JMultiSelect('adminForm');
					}
				});
			}";
			$doc->addScriptDeclaration($js);
		}
		if ( preg_match("/loadItem/",$this->element['onchange']) ) {
			$doc = JFactory::getDocument();
			
			$js = "
			function loadItem() {
				var form = document.adminForm;
				var action = form.action;
				var reg1=new RegExp('&id=','g');
				var tab = action.split(reg1);
				var itemId = tab[1];
				var collection = jQuery('jform_request_collection').val();
				var url='index.php?option=com_collector&format=raw&view=menu&tmpl=component&task=menu.listItems&collection='+collection+'&itemId='+itemId;
				jQuery.ajax({
					type: 'POST',
					url: url,
					success: function( response ) {
						jQuery('#listItems').html(response);
					}
				});
			}";
			$doc->addScriptDeclaration($js);
		}
		if ( preg_match("/loadScripts/",$this->element['onchange']) ) {
			$doc = JFactory::getDocument();
			
			$js = "
			function loadScripts() {
				var form = document.adminForm;
				var action = form.action;
				var reg1=new RegExp('&id=','g');
				var tab = action.split(reg1);
				var itemId = tab[1];
				var collection = jQuery('#jform_request_id').val();
				var url='index.php?option=com_collector&format=raw&view=menu&tmpl=component&task=menu.loadScripts&collection='+collection+'&itemId='+itemId;
				jQuery.getScript( url );
			}";
			$doc->addScriptDeclaration($js);
		}
		
		return parent::getInput();
	}
	
	/**
	 * Method to get the field options.
	 *
	 * @return    array    The field option objects.
	 */
	protected function getOptions()
	{
		$options = array();

		$db = JFactory::getDbo();
		$query = $db->getQuery(true)
			->select('id AS value, name AS text')
			->from('#__collector')
			->order('text');

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

		// Merge any additional options in the XML definition.
		$options = array_merge(parent::getOptions(), $options);

		return $options;
	}
}
?>