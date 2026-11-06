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

// no direct access

defined('_JEXEC') or die('Restricted access');

use Joomla\CMS\Factory;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Language\Associations;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Layout\LayoutHelper;
use Joomla\CMS\Router\Route;
use Joomla\Registry\Registry;
use Joomla\CMS\Form\Form;

// Include the component HTML helpers.
Form::addFieldPath(JPATH_COMPONENT . '/models/fields');

// Load the tooltip behavior.
HTMLHelper::_('behavior.formvalidator');
HTMLHelper::_('jquery.framework');
// HTMLHelper::_('behavior.keepalive');
// HTMLHelper::_('formbehavior.chosen', 'select');

$app = Factory::getApplication();
$input = $app->input;
?>

<script language="javascript" type="text/javascript">
	// type required validation
	window.addEventListener('DOMContentLoaded', function(){
		document.formvalidator.setHandler('typeverify', function (value) { return (jQuery('#jform_type').value != '0'); }	);
	});
	
	window.addEventListener("DOMContentLoaded",function(){
		jQuery('#jform_type').on('change',function(){
			var log = $('params').empty().addClass('ajax-loading');
			var type = jQuery('#jform_type').val();
			
			if (type != '0') {
				var url = 'index.php?option=com_collector&format=raw&view=field&tmpl=component&task=ajax.params&type='+type+'&collection=<?php echo $this->item->collection;?>&id=<?php echo $this->item->id;?>';
				jQuery.ajax({
					url: url,
					method:'post',
					success: function(result, status, xhr) { jQuery("#paramsupdate").html(result); },
					error: function() { console.log('ajax call failed'); },
				});
				// var myRequest = new Request({
					// url: url,
					// method:'post',
					// onComplete: function( response ) {
						// document.id('params').set('html',response);
					// }
				// });
				// myRequest.send();
			}
		});
	});
	
	/*Joomla.submitbutton = function(task) {
		var form = document.adminForm;
		var type = form.jform_type.get('value');
		if (task == 'field.cancel' || ((type != '0') && (document.formvalidator.isValid(document.id('field-form'))))) {
			<?php
			// foreach ( $this->types as $type ) {
				// $fieldObject = CollectorField::getInstance( $this->item->collection, $type );
				// $fieldObject->onRegisterField($this->form);
			// }
			?>
			Joomla.submitform(task, document.getElementById('field-form'));
		} else {
			alert('<?php // echo $this->escape(Text::_('JGLOBAL_VALIDATION_FORM_FAILED'));?>');
		}
	}*/
	
	displayAttribs = function() {
		var type = $('#jform_type').val();
		<?php
		foreach ($this->types as $type) {
			echo "$('#attribs-".$type->id."').css('display','none');";
		}
		?>
		$('#attribs-'+type).css('display','block');
	}
</script>

<form action="<?php echo Route::_('index.php?option=com_collector&layout=edit&id='.(int) $this->item->id); ?>" method="post" name="adminForm" id="field-form" class="form-validate" >

	<?php echo LayoutHelper::render('joomla.edit.title_alias', $this); ?>

	<div class="form-horizontal">
		<?php echo HTMLHelper::_('uitab.startTabSet', 'myTab', array('active' => 'general')); ?>
		
		<?php echo HTMLHelper::_('uitab.addTab', 'myTab', 'general', Text::_('COM_COLLECTOR_FIELDSET_DETAILS', true)); ?>
		<div class="row">
			<div class="col-lg-9">
				<?php echo $this->form->renderField('description'); ?>
				<?php echo $this->form->renderField('type'); ?>
				<?php echo $this->form->renderField('collection'); ?>
				<?php echo $this->form->renderField('required'); ?>
			</div>
			<div class="col-lg-3">
				<?php echo LayoutHelper::render('joomla.edit.global', $this); ?>
			</div>
		</div>
		<?php echo HTMLHelper::_('uitab.endTab'); ?>
		
		<?php echo HTMLHelper::_('uitab.addTab', 'myTab', 'params', Text::_('COM_COLLECTOR_FIELDSET_PARAMS', true)); ?>
		<div class="row">
			<div class="col-12">
				<div id="paramsupdate">
				<?php echo $this->form->renderField('unik'); ?>
				<?php echo $this->form->renderField('edit'); ?>
				<?php echo $this->form->renderField('listing'); ?>
				<?php echo $this->form->renderField('filter'); ?>
				<?php echo $this->form->renderField('sort'); ?>
				</div>
				<div class="control-group form-inline">
					<?php echo $this->form->getLabel('next_sorted_field'); ?> <div id="next_sorted_field" ><?php echo $this->form->getInput('next_sorted_field'); ?></div>
				</div>
				<?php echo $this->form->renderField('column_width'); ?>
			</div>
		</div>
		<?php echo HTMLHelper::_('uitab.endTab'); ?>
		
		<?php echo HTMLHelper::_('uitab.addTab', 'myTab', 'specific-attributes', Text::_('COM_COLLECTOR_FIELDSET_ATTRIBUTES', true)); ?>
		<div class="row-fluid form-horizontal-desktop">
			<div class="col-12">
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
							<div class="control-group">
								<div class="control-label">
									<?php echo $field->label; ?>
								</div>
								<div class="controls">
									<?php echo $field->input; ?>
								</div>
							</div>
						<?php endforeach;
						} else {
							echo Text::_('COM_COLLECTOR_NO_SPECIFIC_ATTRIBS');
						} ?>
					</div>
				<?php } ?>
			</div>
		</div>
		<?php echo HTMLHelper::_('uitab.endTab'); ?>
		
		<?php echo HTMLHelper::_('uitab.addTab', 'myTab', 'publishing', Text::_('COM_COLLECTOR_FIELDSET_PUBLISHING', true)); ?>
		<div class="row-fluid form-horizontal-desktop">
			<div class="col-12">
				<?php echo LayoutHelper::render('joomla.edit.publishingdata', $this); ?>
			</div>
		</div>
		<?php echo HTMLHelper::_('uitab.endTab'); ?>
		
		<?php if ($this->canDo->get('core.admin')) : ?>
			<?php echo HTMLHelper::_('uitab.addTab', 'myTab', 'permissions', Text::_('COM_COLLECTOR_FIELD_FIELDSET_RULES', true)); ?>
				<?php echo $this->form->getInput('rules'); ?>
			<?php echo HTMLHelper::_('uitab.endTab'); ?>
		<?php endif; ?>
		
		<?php echo HTMLHelper::_('uitab.endTabSet'); ?>
		
		<input type="hidden" name="task" value="" />
		<input type="hidden" name="return" value="<?php echo $input->getCmd('return');?>" />
		<?php echo HTMLHelper::_('form.token'); ?>
	</div>
</form>
