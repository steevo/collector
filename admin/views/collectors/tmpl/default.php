<?php
/**
 * Joomla! 3.0 component Collector
 *
 * @package 	Collector
 * @copyright   Copyright (C) 2010 - 2015 Philippe Ousset. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 *
 * Collector is a Multi Purpose Listing Tool.
 * Originaly developped to list Collections
 * it can be used for several purpose.
 */

// no direct access

defined('_JEXEC') or die('Restricted access');
	
?>
<form action="<?php echo JRoute::_('index.php?option=com_collector&view=collections');?>" method="post" name="adminForm" id="adminForm" >
	<div class="row-fluid">
	<?php if (!empty( $this->sidebar)) : ?>
		<div id="j-sidebar-container" class="span2">
			<?php echo $this->sidebar; ?>
		</div>
		<div id="j-main-container" class="span10">
	<?php else : ?>
		<div id="j-main-container">
	<?php endif;?>
		<table width="100%" ><tr><td>
			<center><img src="components/com_collector/assets/images/collector_logo.png" alt="collector" /></center>
		</td></tr><tr><td>
			<?php echo JHtml::_('bootstrap.startTabSet', 'myTab', array('active' => 'version')); ?>
				<?php echo JHtml::_('bootstrap.addTab', 'myTab', 'version', JText::_('COM_COLLECTOR_VERSION', true)); ?>
					<div class="row-fluid">
						<?php echo $this->compare_version_panel(); ?>
					</div>
				<?php echo JHtml::_('bootstrap.endTab'); ?>
				
				<?php echo JHtml::_('bootstrap.addTab', 'myTab', 'support', JText::_('COM_COLLECTOR_SUPPORT', true)); ?>
					<div class="row-fluid">
						<?php echo $this->support_panel(); ?>
					</div>
				<?php echo JHtml::_('bootstrap.endTab'); ?>
				
				<?php echo JHtml::_('bootstrap.addTab', 'myTab', 'about', JText::_('COM_COLLECTOR_ABOUT', true)); ?>
					<div class="row-fluid">
						<?php echo $this->about_panel(); ?>
					</div>
				<?php echo JHtml::_('bootstrap.endTab'); ?>
			
			<?php echo JHtml::_('bootstrap.endTabSet'); ?>
		</td></tr></table>
		</div>
	</div>
</form>