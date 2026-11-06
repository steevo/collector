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

defined('_JEXEC') or die('Restricted access');

use Joomla\CMS\Factory;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Language\Associations;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Layout\LayoutHelper;
use Joomla\CMS\Router\Route;
use Joomla\Registry\Registry;

// Include the component HTML helpers.
HTMLHelper::addIncludePath(JPATH_COMPONENT.'/helpers/html');
JForm::addFieldPath(JPATH_COMPONENT . '/models/fields');

// Load the tooltip behavior.
HTMLHelper::_('bootstrap.tooltip');
HTMLHelper::_('behavior.formvalidator');
// HTMLHelper::_('behavior.keepalive');
// HTMLHelper::_('formbehavior.chosen', 'select');
// HTMLHelper::_('behavior.modal');

// Create shortcut to parameters.
$params = $this->state->get('params');

$app = Factory::getApplication();
$input = $app->input;
?>

<script language="javascript" type="text/javascript">
	<!--
	Joomla.submitbutton = function(task) {
		var form = document.adminForm;
		if (task == 'item.cancel' || document.formvalidator.isValid(document.getElementById('item-form'))) {
			<?php
			foreach ( $this->fieldsCol as $field )
			{
				$field->onSubmit($this->form);
			}
			?>
			
			Joomla.submitform(task, document.getElementById('item-form'));
		} else {
			alert('<?php echo $this->escape(Text::_('JGLOBAL_VALIDATION_FORM_FAILED'));?>');
		}
	}
	//-->
</script>

<form action="<?php echo Route::_('index.php?option=com_collector&layout=edit&id='.(int) $this->item->id); ?>" method="post" name="adminForm" id="item-form" class="form-validate" >
	<div class="form-horizontal">
		<?php echo HTMLHelper::_('uitab.startTabSet', 'myTab', array('active' => 'general')); ?>

		<?php echo HTMLHelper::_('uitab.addTab', 'myTab', 'general', Text::_('COM_COLLECTOR_FIELDSET_DETAILS', true)); ?>
		<div class="row">
			<div class="col-lg-9">
				<?php echo $this->form->renderField('collection'); ?>
				<?php echo $this->form->renderField('alias'); ?>
				<?php foreach ( $this->fieldsCol as $field )
				{
					echo $this->form->renderField($field->_field->tablecolumn);
				} ?>
				<?php echo $this->form->renderField('id'); ?>
				<?php if ( ( $params->get('save_history') ) && ( $params->get('show_modification') ) )
				{
					echo $this->form->renderField('modification');
				} ?>
			</div>
			<div class="col-lg-3">
				<?php echo LayoutHelper::render('joomla.edit.global', $this); ?>
			</div>
		</div>
		<?php echo HTMLHelper::_('uitab.endTab'); ?>
		
		<?php echo HTMLHelper::_('uitab.addTab', 'myTab', 'publishing', Text::_('COM_COLLECTOR_FIELDSET_PUBLISHING', true)); ?>
		<div class="row-fluid form-horizontal-desktop">
			<div class="col-6">
				<?php echo LayoutHelper::render('joomla.edit.publishingdata', $this); ?>
			</div>
			<div class="col-6">
				<?php echo LayoutHelper::render('joomla.edit.metadata', $this); ?>
			</div>
		</div>
		<?php echo HTMLHelper::_('uitab.endTab'); ?>
		
		<?php if ($this->canDo->get('core.admin')) : ?>
			<?php echo HTMLHelper::_('uitab.addTab', 'myTab', 'permissions', Text::_('COM_COLLECTOR_FIELDSET_ITEMS_RULES', true)); ?>
				<?php echo $this->form->getInput('rules'); ?>
			<?php echo HTMLHelper::_('uitab.endTab'); ?>
		<?php endif; ?>
		
		<?php echo HTMLHelper::_('uitab.endTabSet'); ?>
		
		<input type="hidden" name="task" value="" />
		<input type="hidden" name="return" value="<?php echo $input->getCmd('return');?>" />
		<?php echo HTMLHelper::_('form.token'); ?>
	</div>
</form>

<?php
if ($this->item->id) :
    echo HTMLHelper::_('bootstrap.renderModal', 'modal-versions', [
        'title'      => 'Historique des versions',
        'url'        => 'index.php?option=com_collector&view=itemversions&tmpl=component&collection='.$this->item->collection.'&item=' . $this->item->id,
        'modalCss'   => 'modal-xl',
        'bodyHeight' => '75vh',
    ]);
endif;
?>
