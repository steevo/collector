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
				var collection = $('jform_request_id').get('value');
				var url='index.php?option=com_collector&format=raw&view=menu&tmpl=component&task=menu.listFilter&collection='+collection+'&itemId='+itemId;
				var myRequest = new Request({
					url: url,
					method:'post',
					onComplete: function( response ) {
						$('listFilter').set('html',response);
						jQuery('select').chosen({
						disable_search_threshold : 10,
						allow_single_deselect : true});
					}
				});
				myRequest.send();
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
				var collection = $('jform_request_id').get('value');
				var url='index.php?option=com_collector&format=raw&view=menu&tmpl=component&task=menu.listRequired&collection='+collection+'&itemId='+itemId;
				var myRequest = new Request({
					url: url,
					method:'post',
					onComplete: function( response ) {
						$('listRequired').set('html',response);
						new Joomla.JMultiSelect('adminForm');
					}
				});
				myRequest.send();
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
				var collection = $('jform_request_collection').get('value');
				var url='index.php?option=com_collector&format=raw&view=menu&tmpl=component&task=menu.listItems&collection='+collection+'&itemId='+itemId;
				var myRequest = new Request({
					url: url,
					method:'post',
					onComplete: function( response ) {
						$('listItems').set('html',response);
					}
				});
				myRequest.send();
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
			JError::raiseWarning(500, $e->getMessage());
		}

		// Merge any additional options in the XML definition.
		$options = array_merge(parent::getOptions(), $options);

		return $options;
	}
}
?>