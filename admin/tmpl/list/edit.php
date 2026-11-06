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

// Include the component HTML helpers.
HTMLHelper::addIncludePath(JPATH_COMPONENT.'/helpers/html');
JForm::addFieldPath(JPATH_COMPONENT . '/models/fields');

// Load the tooltip behavior.
HTMLHelper::_('behavior.formvalidator');
// HTMLHelper::_('behavior.keepalive');
// HTMLHelper::_('formbehavior.chosen', 'select');

$app = Factory::getApplication();
$input = $app->input;
?>

<script type="text/javascript">
	/*Joomla.submitbutton = function(task)
	{
		if (task == 'list.cancel' || document.formvalidator.isValid(document.id('item-form')))
		{
			Joomla.submitform(task, document.getElementById('item-form'));
		}
	}*/
</script>

<form action="<?php echo JRoute::_('index.php?option=com_collector&layout=edit&id='.(int) $this->item->id); ?>" method="post" name="adminForm" id="item-form" class="form-validate" >

	<?php echo LayoutHelper::render('joomla.edit.title_alias', $this); ?>
	
	<div class="main-card">
		<?php echo HTMLHelper::_('uitab.startTabSet', 'myTab', array('active' => 'general')); ?>
		
		<?php echo HTMLHelper::_('uitab.addTab', 'myTab', 'general', Text::_('COM_COLLECTOR_FIELDSET_DETAILS', true)); ?>
		<div class="row">
            <div class="col-lg-9">
				<fieldset class="adminform">
					<?php echo $this->loadTemplate('image'); ?>
				</fieldset>
			</div>
			<div class="col-lg-3">
				<?php echo LayoutHelper::render('joomla.edit.global', $this); ?>
			</div>
		</div>
		<?php echo HTMLHelper::_('uitab.endTab'); ?>
				
		<?php echo HTMLHelper::_('uitab.addTab', 'myTab', 'publishing', Text::_('COM_COLLECTOR_FIELDSET_PUBLISHING', true)); ?>
		<div class="row">
              <div class="col-12">
                  <fieldset id="fieldset-publishingdata" class="options-form">
                        <legend><?php echo Text::_('JGLOBAL_FIELDSET_PUBLISHING'); ?></legend>
                        <?php echo LayoutHelper::render('joomla.edit.publishingdata', $this); ?>
                   </fieldset>
                </div>
			</div>
		</div>
		<?php echo HTMLHelper::_('uitab.endTab'); ?>
		
		<input type="hidden" name="task" value="" />
		<input type="hidden" name="return" value="<?php echo $input->getCmd('return'); ?>" />
		<?php echo HTMLHelper::_('form.token'); ?>
	</div>
</form>
