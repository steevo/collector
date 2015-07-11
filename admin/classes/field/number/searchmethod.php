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

JFormHelper::loadFieldClass('list');

/**
 * Renders a filter field element
 *
 * @package		Collector
 */
class JFormFieldSearchmethod extends JFormFieldList
{
	protected $type			= 'Searchmethod';

	protected function getInput()
	{
		// Initialize variables.
		$html = array();
		$db = JFactory::getDBO();
		$doc = JFactory::getDocument();

		$js = "
		window.addEvent('domready',function(){
			changeMethod();
		});
		
		changeMethod = function() {
			var form = document.adminForm;
			var type = form.jform_attribs_number_search_method.get('value');
			switch (type) {
				case '0':
					jQuery('.slider').css( 'display', 'none' );
					jQuery('.textbox').css( 'display', '' );
					break;
				case '1':
					jQuery('.textbox').css( 'display', 'none' );
					jQuery('.slider').css( 'display', '' );
					break;
			}
		};";
		
		$doc->addScriptDeclaration($js);

		return parent::getInput();
	}
}
