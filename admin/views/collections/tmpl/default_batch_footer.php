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
defined('_JEXEC') or die;
?>
<a class="btn" type="button" onclick="document.getElementById('batch-copy-mode').value='';document.getElementById('batch-access').value='';" data-dismiss="modal">
	<?php echo JText::_('JCANCEL'); ?>
</a>
<button class="btn btn-success" type="submit" onclick="Joomla.submitbutton('collection.batch');">
	<?php echo JText::_('JGLOBAL_BATCH_PROCESS'); ?>
</button>