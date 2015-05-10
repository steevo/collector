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
JHtml::_('behavior.tooltip');
JHtml::_('behavior.formvalidation');
JHtml::_('behavior.keepalive');
JHtml::_('formbehavior.chosen');
JHtml::_('formbehavior.chosen', 'select.input-small');

// Create shortcut to parameters.
$params = $this->state->get('params');
$params = $params->toArray();

$app = JFactory::getApplication();
$input = $app->input;
?>

<script language="javascript" type="text/javascript">
	Joomla.submitbutton = function(task)
	{
		if (task == 'collection.cancel'|| document.formvalidator.isValid(document.id('collection-form'))) {
			<?php echo $this->form->getField('description')->save(); ?>
			Joomla.submitform( task, document.getElementById('collection-form') );
		} else {
			alert('<?php echo $this->escape(JText::_('JGLOBAL_VALIDATION_FORM_FAILED'));?>');
		}
	}
</script>

<form action="<?php echo JRoute::_('index.php?option=com_collector&layout=edit&id='.(int) $this->item->id); ?>" method="post" name="adminForm" id="collection-form" class="form-validate" >

	<?php echo JLayoutHelper::render('joomla.edit.title_alias', $this); ?>
	
	<div class="form-horizontal">
		<?php echo JHtml::_('bootstrap.startTabSet', 'myTab', array('active' => 'general')); ?>
		
		<?php echo JHtml::_('bootstrap.addTab', 'myTab', 'general', JText::_('COM_COLLECTOR_FIELDSET_DETAILS', true)); ?>
		<div class="row-fluid">
			<div class="control-group form-inline">
				<?php echo $this->form->getLabel('custom'); ?> <div id="custom" ><?php echo $this->form->getInput('custom'); ?></div>
			</div>
			<div class="span9">
				<fieldset class="adminform">
					<?php echo $this->form->getLabel('description'); ?>
					<?php echo $this->form->getInput('description'); ?>
				</fieldset>
			</div>
			<div class="span3">
				<?php echo JLayoutHelper::render('joomla.edit.global', $this); ?>
			</div>
		</div>
		<?php echo JHtml::_('bootstrap.endTab'); ?>
		
		<?php echo JHtml::_('bootstrap.addTab', 'myTab', 'publishing', JText::_('COM_COLLECTOR_FIELDSET_PUBLISHING', true)); ?>
		<div class="row-fluid form-horizontal-desktop">
			<div class="span6">
				<?php echo JLayoutHelper::render('joomla.edit.publishingdata', $this); ?>
			</div>
			<div class="span6">
				<?php echo JLayoutHelper::render('joomla.edit.metadata', $this); ?>
			</div>
		</div>
		<?php echo JHtml::_('bootstrap.endTab'); ?>
		
		<?php if ($this->canDo->get('core.admin')) : ?>
			<?php echo JHtml::_('bootstrap.addTab', 'myTab', 'permissions', JText::_('COM_COLLECTOR_FIELDSET_RULES', true)); ?>
				<?php echo $this->form->getInput('rules'); ?>
			<?php echo JHtml::_('bootstrap.endTab'); ?>
		<?php endif; ?>
		
		<?php echo JHtml::_('bootstrap.endTabSet'); ?>
		
		<input type="hidden" name="task" value="" />
		<input type="hidden" name="return" value="<?php echo $input->getCmd('return');?>" />
		<?php echo JHtml::_('form.token'); ?>
	</div>
</form>