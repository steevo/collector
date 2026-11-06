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

use Joomla\CMS\Form\Field\CalendarField;
use DateTime;

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
		
		if (!empty($this->value) && $this->value !== '0000-00-00 00:00:00' && $this->value !== '0000-00-00') {
			$format = str_replace('%', '', $this->format);
			
			if (DateTime::createFromFormat('Y-m-d H:i:s', $this->value) === false) {
				
				$dateObj = DateTime::createFromFormat($format, $this->value);
				
				if ($dateObj !== false) {
					$this->value = $dateObj->format('Y-m-d H:i:s');
				} else {
					$parsedDate = strtotime($this->value);
					if ($parsedDate !== false) {
						$this->value = date('Y-m-d H:i:s', $parsedDate);
					} else {
						$this->value = '';
					}
				}
			}
		} else {
			$this->value = '';
		}
		
		return parent::getInput();
	}
}
