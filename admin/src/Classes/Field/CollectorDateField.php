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

namespace Steevo\Component\Collector\Administrator\Classes\Field;

use Joomla\CMS\Form\Field\CalendarField;

defined('_JEXEC') or die;


/**
 * Form Field class for the Collector.
 *
 * @package		Collector
 */
class CollectorDateField extends CalendarField
{
	protected $type = 'CollectorDate';
	
	protected function getInput()
	{
		
		if ($this->value != 0) {
			$format = str_replace('%','',$this->format);
			
			if (DateTime::createFromFormat('Y-m-d H:i:s', $this->value) === FALSE) {
				// it's not a datetime format
				$this->value = DateTime::createFromFormat($format, $this->value)->format('Y-m-d H:i:s');
			}
		}
		
		return parent::getInput();
	}
}