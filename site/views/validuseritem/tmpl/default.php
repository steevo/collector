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

// Check to ensure this file is included in Joomla!
defined('_JEXEC') or die( 'Restricted access' );

JHtml::_('behavior.formvalidation');

?>
<div class="modal-header"><h3>
	<?php echo JText::sprintf('COM_COLLECTOR_ADD_TO_USERLIST',$this->list->name); ?>
</h3></div>
<form method="post" action="<?php echo JRoute::_(JFactory::getURI()->toString()); ?>" name="adminForm" id="adminForm" class="form-validate form-vertical" >
	<div class="modal-body">
		<fieldset>
			<legend>
				<?php echo $this->item->fulltitle; ?>
			</legend>
			<div class="tab-content">
				<?php echo $this->form->getControlGroup('comment'); ?>
			</div>
		</fieldset>
	</div>
	<div class="modal-footer">
		<div class="btn-toolbar">
			<div class="btn-group">
				<button type="button" class="btn btn-primary" onclick="Joomla.submitbutton('useritem.add')">
					<span class="icon-ok"></span>&#160;<?php echo JText::_('JSAVE') ?>
				</button>
			</div>
			<div class="btn-group">
				<button type="button" class="btn" onclick="SqueezeBox.close();">
					<span class="icon-cancel"></span>&#160;<?php echo JText::_('JCANCEL') ?>
				</button>
			</div>
		</div>

		<input type="hidden" name="option" value="com_collector" />
		<input type="hidden" name="task" value="" />
		<input type="hidden" name="jform[userlist]" id="jform_userlist" value="<?php echo $this->item->userlist; ?>" />
		<input type="hidden" name="jform[itemid]" id="jform_itemid" value="<?php echo $this->item->itemid; ?>" />
		<?php echo JHtml::_( 'form.token' ); ?>
	</div>
</form>
