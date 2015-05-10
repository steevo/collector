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

defined('_JEXEC') or die;
?>
<fieldset>
	<div class="control-group">
		<div class="control-label">
			<?php echo $this->form->getLabel('width'); ?>
		</div>
		<div class="controls">
			<?php echo $this->form->getInput('width'); ?>
		</div>
	</div>
	<div class="control-group">
		<div class="control-label">
			<?php echo $this->form->getLabel('height'); ?>
		</div>
		<div class="controls">
			<?php echo $this->form->getInput('height'); ?>
		</div>
	</div>
</fieldset>