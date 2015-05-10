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
defined('_JEXEC') or die( 'Restricted access' );

/**
 * Collections HTML class.
 *
 * @package	Collector
 */
class JHtmlCollections
{
	/**
	 * Display a batch widget for the copy selector.
	 *
	 * @return  string  The necessary HTML for the widget.
	 *
	 * @since   2.5
	 */
	public static function copy()
	{
		// Create the batch selector to copy a collection on a selection list.
		$lines = array(
			'<label id="batch-copy-lbl" for="batch-copy" class="hasTip" title="'.JText::_('COM_COLLECTOR_BATCH_COPY_LABEL').'::'.JText::_('COM_COLLECTOR_BATCH_COPY_LABEL_DESC').'">',
			JText::_('COM_COLLECTOR_BATCH_COPY_LABEL'),
			'</label>',
			'<select name="batch[copy_id]" class="inputbox" id="batch-copy-id">',
			'<option value="1">'.JText::_('COM_COLLECTOR_BATCH_COPY_ONLY_FIELDS').'</option>',
			'<option value="2">'.JText::_('COM_COLLECTOR_BATCH_COPY_FIELDS_AND_ITEMS_WITHOUT_HISTORY').'</option>',
			'<option value="3">'.JText::_('COM_COLLECTOR_BATCH_COPY_FIELDS_AND_ITEMS_WITH_HISTORY').'</option>',
			'</select>',
			'<label id="batch-copy-name-lbl" for="batch-copy-name" class="hasTip" title="'.JText::_('COM_COLLECTOR_BATCH_COPY_NAME_LABEL').'::'.JText::_('COM_COLLECTOR_BATCH_COPY_NAME_LABEL_DESC').'" style="clear:left;" >',
			JText::_('COM_COLLECTOR_BATCH_COPY_NAME_LABEL'),
			'</label>',
			'<input type="text" name="batch[name_id]" class="inputbox" id="batch-copy-name-id" size="60" />'
		);

		return implode("\n", $lines);
	}

}
