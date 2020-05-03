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
defined('_JEXEC') or die( 'Restricted access' );

$published = $this->state->get('filter.published');
?>

<div class="container-fluid">
	<p><?php echo JText::_('COM_COLLECTOR_BATCH_TIP'); ?></p>
	<div class="row-fluid">
		<div class="control-group span6">
			<div class="controls">
				<?php echo JHtml::_('collections.copy');?>
			</div>
		</div>
		<div class="control-group span6">
			<div class="controls">
				<?php echo JLayoutHelper::render('joomla.html.batch.access', array()); ?>
			</div>
		</div>
	</div>
</div>
