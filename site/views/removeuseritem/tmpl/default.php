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

// Check to ensure this file is included in Joomla!
defined('_JEXEC') or die( 'Restricted access' );

?>
<div class="modal-header"><h3>
	<?php echo JText::_('remove'); ?>
</h3></div>
<form method="post" action="<?php echo JRoute::_(JFactory::getURI()->toString()); ?>" name="adminForm" id="adminForm" class="form-validate form-vertical">
<div class="modal-body">
		<fieldset>
			<div class="tab-content">
				<?php echo $this->loadTemplate('remove'); ?>
			</div>
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

			<input type="hidden" name="task" value="" />
			<input type="hidden" name="jform[userlist]" id="jform_userlist" value="<?php echo $this->item->id; ?>" />
			<input type="hidden" name="jform[itemid]" id="jform_itemid" value="<?php echo $this->collection->id; ?>" />
			<?php echo JHtml::_( 'form.token' ); ?>
		</fieldset>
	</form>
</div>
