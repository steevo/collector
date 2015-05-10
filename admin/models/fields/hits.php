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
 
jimport('joomla.form.formfield');

/**
 * Form Field class for the Collector.
 *
 * @package		Collector
 */
class JFormFieldHits extends JFormField
{
	protected $type			= 'Hits';

	protected function getInput()
	{
		$onclick	= ' onclick="document.id(\''.$this->id.'\').value=\'0\';"';

		return '<input class="input-small" type="text" name="' . $this->name . '" id="' . $this->id . '" value="' . htmlspecialchars($this->value, ENT_COMPAT, 'UTF-8') . '" readonly="readonly" /> <a class="btn" ' . $onclick . '><i class="icon-refresh"></i> ' . JText::_('COM_COLLECTOR_RESET_CLICKS') . '</a>';
	}
}
