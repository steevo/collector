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

// no direct access

defined('_JEXEC') or die('Restricted access');

// Include the component HTML helpers.
JHtml::addIncludePath(JPATH_COMPONENT.'/helpers/html');
JForm::addFieldPath(JPATH_COMPONENT . '/models/fields');

// Load the tooltip behavior.
JHtml::_('behavior.formvalidation');
JHtml::_('behavior.keepalive');
JHtml::_('formbehavior.chosen', 'select');
?>

<script language="javascript" type="text/javascript">
	Joomla.submitbutton = function(task)
	{
		if (task == 'listitem.cancel' || document.formvalidator.isValid(document.id('item-form')))
		{
			Joomla.submitform(task, document.getElementById('item-form'));
		}
	}
</script>

<form action="<?php echo JRoute::_('index.php?option=com_collector&layout=edit&id='.(int) $this->item->id); ?>" method="post" name="adminForm" id="item-form" class="form-horizontal" >
	<fieldset>
		<?php echo JHtml::_('bootstrap.startTabSet', 'myTab', array('active' => 'basic')); ?>

			<?php echo JHtml::_('bootstrap.addTab', 'myTab', 'basic', empty($this->item->id) ? JText::_('COM_COLLECTOR_NEW_LISTITEM', true) : JText::sprintf('COM_COLLECTOR_EDIT_LISTITEM', $this->item->id, true)); ?>
				<div class="control-group">
					<div class="control-label"><?php echo $this->form->getLabel('content'); ?></div>
					<div class="controls"><?php echo $this->form->getInput('content'); ?></div>
				</div>
				<div class="control-group">
					<div class="control-label"><?php echo $this->form->getLabel('image'); ?></div>
					<div class="controls"><?php echo $this->form->getInput('image'); ?></div>
				</div>
				<div class="control-group">
					<div class="control-label"><?php echo $this->form->getLabel('defined'); ?></div>
					<div class="controls"><?php echo $this->form->getInput('defined'); ?></div>
				</div>
				<div class="control-group">
					<div class="control-label"><?php echo $this->form->getLabel('id'); ?></div>
					<div class="controls"><?php echo $this->form->getInput('id'); ?></div>
				</div>
				<div class="control-group">
					<div class="control-label"><?php echo $this->form->getLabel('parent_id'); ?></div>
					<div class="controls"><?php echo $this->form->getInput('parent_id'); ?></div>
				</div>
				<div class="control-group">
					<div class="control-label"><?php echo $this->form->getLabel('listordering'); ?></div>
					<div class="controls"><?php echo $this->form->getInput('listordering'); ?></div>
				</div>
			<?php echo JHtml::_('bootstrap.endTab'); ?>

		<?php echo JHtml::_('bootstrap.endTabSet'); ?>

		<input type="hidden" name="task" value="" />
		<?php echo JHtml::_('form.token'); ?>
	</fieldset>
</form>
