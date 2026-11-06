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
?>

<script language="javascript" type="text/javascript">
	/*Joomla.submitbutton = function(task)
	{
		if (task == 'listitem.cancel' || document.formvalidator.isValid(document.id('item-form')))
		{
			Joomla.submitform(task, document.getElementById('item-form'));
		}
	}*/
</script>

<form action="<?php echo JRoute::_('index.php?option=com_collector&layout=edit&id='.(int) $this->item->id); ?>" method="post" name="adminForm" id="item-form" class="form-validate" >
	<div class="main-card">
		<?php echo HTMLHelper::_('uitab.startTabSet', 'myTab', array('active' => 'basic')); ?>

			<?php echo HTMLHelper::_('uitab.addTab', 'myTab', 'basic', empty($this->item->id) ? Text::_('COM_COLLECTOR_NEW_LISTITEM', true) : Text::sprintf('COM_COLLECTOR_EDIT_LISTITEM', $this->item->id, true)); ?>
				<div class="col-12">
					<?php echo $this->form->renderField('content'); ?>
				</div>
				<div class="col-12">
					<?php echo $this->form->renderField('image'); ?>
				</div>
				<div class="col-12">
					<?php echo $this->form->renderField('defined'); ?>
				</div>
				<div class="col-12">
					<?php echo $this->form->renderField('id'); ?>
				</div>
				<div class="col-12">
					<?php echo $this->form->renderField('parent_id'); ?>
				</div>
				<div class="col-12">
					<?php echo $this->form->renderField('listordering'); ?>
				</div>
			<?php echo HTMLHelper::_('uitab.endTab'); ?>

		<?php echo HTMLHelper::_('uitab.endTabSet'); ?>

		<input type="hidden" name="task" value="" />
		<?php echo HTMLHelper::_('form.token'); ?>
	</fieldset>
</form>
