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

defined('_JEXEC') or die;

use Joomla\CMS\Language\Text;

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
			'<label id="batch-copy-lbl" for="batch-copy" class="hasTip" title="'.Text::_('COM_COLLECTOR_BATCH_COPY_LABEL').'::'.Text::_('COM_COLLECTOR_BATCH_COPY_LABEL_DESC').'">',
			Text::_('COM_COLLECTOR_BATCH_COPY_LABEL'),
			'</label>',
			'<select name="batch[copy_mode]" class="inputbox" id="batch-copy-mode">',
			'<option value="0">'.Text::_('COM_COLLECTOR_BATCH_NO_COPY').'</option>',
			'<option value="1">'.Text::_('COM_COLLECTOR_BATCH_COPY_ONLY_FIELDS').'</option>',
			'<option value="2">'.Text::_('COM_COLLECTOR_BATCH_COPY_FIELDS_AND_ITEMS_WITHOUT_HISTORY').'</option>',
			'<option value="3">'.Text::_('COM_COLLECTOR_BATCH_COPY_FIELDS_AND_ITEMS_WITH_HISTORY').'</option>',
			'</select>'
		);

		return implode("\n", $lines);
	}

}
