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
//HTMLHelper::_('behavior.tooltip');
HTMLHelper::_('behavior.formvalidator');
//HTMLHelper::_('behavior.keepalive');
//HTMLHelper::_('formbehavior.chosen');
//HTMLHelper::_('formbehavior.chosen', 'select.input-small');

// Create shortcut to parameters.
$params = clone $this->state->get('params');

$app = Factory::getApplication();
$input = $app->input;
?>

<script language="javascript" type="text/javascript">
	/*Joomla.submitbutton = function(task)
	{
		if (task == 'collection.cancel'|| document.formvalidator.isValid(document.id('collection-form'))) {
			<?php // echo $this->form->getField('description')->save(); ?>
			Joomla.submitform( task, document.getElementById('collection-form') );
		} else {
			alert('<?php echo $this->escape(Text::_('JGLOBAL_VALIDATION_FORM_FAILED'));?>');
		}
	}*/
</script>

<form action="<?php echo Route::_('index.php?option=com_collector&layout=edit&id='.(int) $this->item->id); ?>" method="post" name="adminForm" id="collection-form" class="form-validate" >

	<?php echo LayoutHelper::render('joomla.edit.title_alias', $this); ?>
	
	<div class="main-card">
		<?php echo HTMLHelper::_('uitab.startTabSet', 'myTab', array('active' => 'details')); ?>
		
		<?php echo HTMLHelper::_('uitab.addTab', 'myTab', 'details', Text::_('COM_COLLECTOR_FIELDSET_DETAILS', true)); ?>
		<div class="row">
            <div class="col-lg-9">
				<div class="control-group form-inline">
					<?php echo $this->form->getLabel('custom'); ?> <div id="custom" ><?php echo $this->form->getInput('custom'); ?></div>
				</div>
                <div>
                    <fieldset class="adminform">
                        <?php echo $this->form->getLabel('description'); ?>
                        <?php echo $this->form->getInput('description'); ?>
                    </fieldset>
                </div>
            </div>
            <div class="col-lg-3">
                <?php echo LayoutHelper::render('joomla.edit.global', $this); ?>
            </div>
        </div>
		<?php echo HTMLHelper::_('uitab.endTab'); ?>
		
		<?php echo HTMLHelper::_('uitab.addTab', 'myTab', 'publishing', Text::_('COM_COLLECTOR_FIELDSET_PUBLISHING', true)); ?>
		<div class="row">
              <div class="col-12 col-lg-6">
                  <fieldset id="fieldset-publishingdata" class="options-form">
                        <legend><?php echo Text::_('JGLOBAL_FIELDSET_PUBLISHING'); ?></legend>
                        <?php echo LayoutHelper::render('joomla.edit.publishingdata', $this); ?>
                   </fieldset>
                </div>
             <div class="col-12 col-lg-6">
                  <fieldset id="fieldset-metadata" class="options-form">
                        <legend><?php echo Text::_('JGLOBAL_FIELDSET_METADATA_OPTIONS'); ?></legend>
                        <?php echo LayoutHelper::render('joomla.edit.metadata', $this); ?>
                 </fieldset>
                </div>
         </div>
		<?php echo HTMLHelper::_('uitab.endTab'); ?>
		
		<?php if ($this->canDo->get('core.admin') && $params->get('show_permissions', 1) == 1) : ?>
            <?php echo HTMLHelper::_('uitab.addTab', 'myTab', 'permissions', Text::_('COM_COLLECTOR_FIELDSET_RULES')); ?>
            <fieldset id="fieldset-rules" class="options-form">
                <legend><?php echo Text::_('COM_COLLECTOR_FIELDSET_RULES'); ?></legend>
                <div>
                <?php echo $this->form->getInput('rules'); ?>
                </div>
            </fieldset>
            <?php echo HTMLHelper::_('uitab.endTab'); ?>
        <?php endif; ?>
		
		<?php echo HTMLHelper::_('uitab.endTabSet'); ?>
		
		<input type="hidden" name="task" value="" />
		<input type="hidden" name="return" value="<?php echo $input->getCmd('return');?>" />
		<?php echo HTMLHelper::_('form.token'); ?>
	</div>
</form>