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

// Check to ensure this file is included in Joomla!
defined('_JEXEC') or die( 'Restricted access' );

JHtml::stylesheet(Juri::base() . 'components/com_collector/assets/css/icomoon.css');

?>
<script type="text/javascript">
	Joomla.submitbutton = function(task)
	{
		if (document.formvalidator.isValid(document.id('userlistForm')))
		{
			jQuery('#useritemRemoveForm_item').val(jQuery('#jform_itemid').val());
			jQuery('#useritemRemoveForm_userlist').val(jQuery('#jform_userlist').val());
			jQuery.each(jQuery("input[name='cid[]']:checked"), function(){
				var checkbox = document.createElement('input');
				checkbox.type = "hidden";
				checkbox.name = "cid[]";
				checkbox.value = jQuery(this).val();

				jQuery('#useritemRemoveForm').append(checkbox);
			});
			SqueezeBox.close();
			jQuery('#useritemRemoveForm').submit();
		}
	}
</script>

<div class="modal-header"><h3>
	<?php echo JText::sprintf('COM_COLLECTOR_REMOVE_FROM_USERLIST',$this->list->name); ?>
</h3></div>
<form method="post" action="<?php echo JRoute::_(JFactory::getURI()->toString()); ?>" name="userlistForm" id="userlistForm" class="form-validate form-vertical">
	<div class="modal-body">
		<fieldset>
			<legend>
				<?php echo $this->item->fulltitle; ?>
			</legend>
			<div class="tab-content">
				<?php echo $this->loadTemplate('remove'); ?>
			</div>
		</fieldset>
	</div>
	<div class="modal-footer">
		<div class="btn-toolbar">
			<div class="btn-group">
				<button type="button" class="btn btn-danger" onclick="Joomla.submitbutton('useritem.delete')">
					<span class="icon-delete"></span>&#160;<?php echo JText::_('JACTION_DELETE') ?>
				</button>
			</div>
			<div class="btn-group">
				<button type="button" class="btn" onclick="Joomla.submitbutton('useritem.cancel')">
					<span class="icon-cancel"></span>&#160;<?php echo JText::_('JCANCEL') ?>
				</button>
			</div>
		</div>

		<input type="hidden" name="option" value="com_collector" />
		<input type="hidden" name="task" value="" />
		<input type="hidden" name="jform[userlist]" id="jform_userlist" value="<?php echo $this->item->userlist; ?>" />
		<input type="hidden" name="jform[item]" id="jform_itemid" value="<?php echo $this->item->id; ?>" />
		<?php echo JHtml::_( 'form.token' ); ?>
	</div>
</form>
