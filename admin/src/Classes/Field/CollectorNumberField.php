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

use Joomla\CMS\Form\Field\TextField;
use Steevo\Component\Collector\Administrator\Classes\Field\Number\Searchmethod;

defined('_JEXEC') or die;

/**
 * Form Field class for the Collector.
 *
 * @package		Collector
 */
class CollectorNumberField extends TextField
{
	protected $type = 'CollectorNumber';
	
	protected function getInput()
	{
		$input = parent::getInput();
		
		$html = array();
		
		$template = $this->element['template'] == '' ? '%%' : $this->element['template'];
		$template = explode('%%',$template);
		if ( $template[0] != '' )
		{
			$margin = '5px 5px 5px 0';
		}
		else
		{
			$margin = '5px 0';
		}
		
		$html[] = '<div class="fltlft" style="display:inline; margin: '.$margin.';" >'.$template[0].'</div>';
		$html[] = $input;
		$html[] = '<div class="fltlft" style="display:inline; margin: 5px 0;" >'.$template[1].'</div>';
		
		return implode($html);
	}
}