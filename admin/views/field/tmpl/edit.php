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

// Include the component HTML helpers.
JHtml::addIncludePath(JPATH_COMPONENT.'/helpers/html');
JForm::addFieldPath(JPATH_COMPONENT . '/models/fields');

// Load the tooltip behavior.
JHtml::_('behavior.formvalidation');
JHtml::_('behavior.keepalive');
JHtml::_('formbehavior.chosen', 'select');

$app = JFactory::getApplication();
$input = $app->input;
?>

<script language="javascript" type="text/javascript">
	// type required validation
	window.addEvent('domready', function(){
		document.formvalidator.setHandler('typeverify', function (value) { return ($('jform_type').value != '0'); }	);
	});
	
	window.addEvent("domready",function(){
		$('jform_type').addEvent('change',function(){
			var log = $('params').empty().addClass('ajax-loading');
			var type = this.get('value');
			
			if (type != '0') {
				var url = 'index.php?option=com_collector&format=raw&view=field&tmpl=component&task=field.params&type='+type+'&id=<?php echo $this->item->id;?>';
				var myRequest = new Request({
					url: url,
					method:'post',
					onComplete: function( response ) {
						document.id('params').set('html',response);
					}
				});
				myRequest.send();
			}
		});
	});
	
	Joomla.submitbutton = function(task) {
		var form = document.adminForm;
		var type = form.jform_type.get('value');
		if (task == 'field.cancel' || ((type != '0') && (document.formvalidator.isValid(document.id('field-form'))))) {
			<?php
			foreach ( $this->types as $type ) {
				$fieldObject = CollectorField::getInstance( $this->item->collection, $type );
				$fieldObject->onRegisterField($this->form);
			}
			?>
			Joomla.submitform(task, document.getElementById('field-form'));
		} else {
			alert('<?php echo $this->escape(JText::_('JGLOBAL_VALIDATION_FORM_FAILED'));?>');
		}
	}
	
	displayAttribs = function() {
		var form = document.adminForm;
		var type = form.jform_type.get('value');
		<?php
		foreach ($this->types as $type) {
			echo "document.id('attribs-".$type->id."').style.display = 'none';";
		}
		?>
		document.id('attribs-'+type).style.display = '';
	}
</script>

<form action="<?php echo JRoute::_('index.php?option=com_collector&layout=edit&id='.(int) $this->item->id); ?>" method="post" name="adminForm" id="field-form" class="form-validate" >

	<?php echo JLayoutHelper::render('joomla.edit.title_alias', $this); ?>

	<div class="form-horizontal">
		<?php echo JHtml::_('bootstrap.startTabSet', 'myTab', array('active' => 'general')); ?>
		
		<?php echo JHtml::_('bootstrap.addTab', 'myTab', 'general', JText::_('COM_COLLECTOR_FIELDSET_DETAILS', true)); ?>
		<div class="row-fluid">
			<div class="span9">
				<?php echo $this->form->getControlGroup('description'); ?>
				<?php echo $this->form->getControlGroup('type'); ?>
				<?php echo $this->form->getControlGroup('collection'); ?>
				<?php echo $this->form->getControlGroup('required'); ?>
			</div>
			<div class="span3">
				<?php echo JLayoutHelper::render('joomla.edit.global', $this); ?>
			</div>
		</div>
		<?php echo JHtml::_('bootstrap.endTab'); ?>
		
		<?php echo JHtml::_('bootstrap.addTab', 'myTab', 'params', JText::_('COM_COLLECTOR_FIELDSET_PARAMS', true)); ?>
		<div class="row-fluid form-horizontal-desktop">
			<div class="span12">
				<?php echo $this->form->getControlGroup('unik'); ?>
				<?php echo $this->form->getControlGroup('edit'); ?>
				<?php echo $this->form->getControlGroup('listing'); ?>
				<?php echo $this->form->getControlGroup('filter'); ?>
				<?php echo $this->form->getControlGroup('sort'); ?>
			</div>
		</div>
		<?php echo JHtml::_('bootstrap.endTab'); ?>
		
		<?php echo JHtml::_('bootstrap.addTab', 'myTab', 'specific-attributes', JText::_('COM_COLLECTOR_FIELDSET_ATTRIBUTES', true)); ?>
		<div class="row-fluid form-horizontal-desktop">
			<div class="span12">
				<?php foreach ($this->types as $type) {
					$display = '';
					if (( $this->item->id == 0 ) || ( $this->item->type != $type->id )) {
						$display = 'style="display: none"';
					}
					?>
					<div class="span12" id="attribs-<?php echo $type->id; ?>" <?php echo $display; ?> >
						<?php
						if (sizeof($this->form->getGroup('attribs-'.$type->type)) != 0) {
							foreach($this->form->getGroup('attribs-'.$type->type) as $field): ?>
							<div class="control-group <?php echo $field->class; ?>">
								<div class="control-label">
									<?php echo $field->label; ?>
								</div>
								<div class="controls">
									<?php echo $field->input; ?>
								</div>
							</div>
						<?php endforeach;
						} else {
							echo JText::_('COM_COLLECTOR_NO_SPECIFIC_ATTRIBS');
						} ?>
					</div>
				<?php } ?>
			</div>
		</div>
		<?php echo JHtml::_('bootstrap.endTab'); ?>
		
		<?php echo JHtml::_('bootstrap.addTab', 'myTab', 'publishing', JText::_('COM_COLLECTOR_FIELDSET_PUBLISHING', true)); ?>
		<div class="row-fluid form-horizontal-desktop">
			<div class="span12">
				<?php echo JLayoutHelper::render('joomla.edit.publishingdata', $this); ?>
			</div>
		</div>
		<?php echo JHtml::_('bootstrap.endTab'); ?>
		
		<?php if ($this->canDo->get('core.admin')) : ?>
			<?php echo JHtml::_('bootstrap.addTab', 'myTab', 'permissions', JText::_('COM_COLLECTOR_FIELD_FIELDSET_RULES', true)); ?>
				<?php echo $this->form->getInput('rules'); ?>
			<?php echo JHtml::_('bootstrap.endTab'); ?>
		<?php endif; ?>
		
		<?php echo JHtml::_('bootstrap.endTabSet'); ?>
		
		<input type="hidden" name="task" value="" />
		<input type="hidden" name="return" value="<?php echo $input->getCmd('return');?>" />
		<?php echo JHtml::_('form.token'); ?>
	</div>
</form>
