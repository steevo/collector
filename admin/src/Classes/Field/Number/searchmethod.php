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

namespace Steevo\Component\Collector\Administrator\Classes\Field\Number;

use Joomla\CMS\Form\Field\ListField;

defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\Database\DatabaseInterface;

/**
 * Renders a filter field element
 *
 * @package		Collector
 */
class SearchmethodField extends ListField
{
	protected $type			= 'Searchmethod';

	protected function getInput()
	{
		// Initialize variables.
		$html = array();
		$db = Factory::getContainer()->get(DatabaseInterface::class);
		$doc = Factory::getApplication()->getDocument();

		$js = "
		jQuery(document).ready(function(){
			changeMethod();
		});
		
		changeMethod = function() {
			var type = jQuery('#jform_attribs_number_search_method').val();
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
