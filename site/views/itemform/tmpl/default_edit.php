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

JHtml::_('behavior.keepalive');
JHtml::_('behavior.calendar');
JHtml::_('behavior.formvalidation');
JHtml::_('formbehavior.chosen', 'select');

?>
<div class="edit item-page<?php echo $this->pageclass_sfx; ?>" >
	<form method="post" action="<?php echo JRoute::_(JFactory::getURI()->toString()); ?>" name="adminForm" id="adminForm" class="form-validate form-vertical">
		<div class="btn-toolbar">
			<div class="btn-group">
				<button type="button" class="btn btn-primary" onclick="Joomla.submitbutton('item.save')">
					<span class="icon-ok"></span>&#160;<?php echo JText::_('JSAVE') ?>
				</button>
			</div>
			<div class="btn-group">
				<button type="button" class="btn" onclick="Joomla.submitbutton('item.cancel')">
					<span class="icon-cancel"></span>&#160;<?php echo JText::_('JCANCEL') ?>
				</button>
			</div>
			<?php if ( ( $this->item->id != 0 ) && ($this->item->params->get('access-delete')) ) : ?>
			<div class="btn-group">
				<button type="button" class="btn btn-danger" onclick="Joomla.submitbutton('item.delete')">
					<span class="icon-delete"></span>&#160;<?php echo JText::_('JACTION_DELETE') ?>
				</button>
			</div>
			<?php endif; ?>
		</div>
		
		<fieldset>
			<ul class="nav nav-tabs">
				<li class="active"><a href="#editor" data-toggle="tab">
					<?php if ( $this->item->id != 0 ) {
						echo JText::sprintf('COM_COLLECTOR_EDIT_ITEM', $this->item->fulltitle);
					} else {
						echo JText::_('COM_COLLECTOR_ITEM_DETAILS');
					}
					?></a></li>
				<li><a href="#publishing" data-toggle="tab"><?php echo JText::_('COM_COLLECTOR_PUBLISHING') ?></a></li>
				<li><a href="#metadata" data-toggle="tab"><?php echo JText::_('COM_COLLECTOR_METADATA') ?></a></li>
			</ul>
			
			<div class="tab-content">
				<div class="tab-pane active" id="editor">
					<?php
					foreach ( $this->fields as $field )
					{
						echo '<div class="formelm" style="float:left; width:100%;" >';
						echo $this->form->getLabel($field->_field->tablecolumn);
						echo $this->form->getInput($field->_field->tablecolumn);
						echo '</div>';
					} 
					?>
					<?php if ( ( $this->params->get('save_history') ) && ( $this->params->get('show_modification') ) ) :?>
						<div class="formelm">
						<?php echo $this->form->getLabel('modification'); ?>
						<?php echo $this->form->getInput('modification'); ?>
						</div>
					<?php endif; ?>
					<div class="btn-toolbar">
						<div class="btn-group">
							<button type="button" class="btn btn-primary" onclick="Joomla.submitbutton('item.save')">
								<span class="icon-ok"></span>&#160;<?php echo JText::_('JSAVE') ?>
							</button>
						</div>
						<div class="btn-group">
							<button type="button" class="btn" onclick="Joomla.submitbutton('item.cancel')">
								<span class="icon-cancel"></span>&#160;<?php echo JText::_('JCANCEL') ?>
							</button>
						</div>
					</div>
				</div>
				
				<div class="tab-pane" id="publishing">
					<div class="control-group">
						<div class="control-label">
							<?php echo $this->form->getLabel('created_by_alias'); ?>
						</div>
						<div class="controls">
							<?php echo $this->form->getInput('created_by_alias'); ?>
						</div>
					</div>
					<?php if ($this->item->params->get('access-change')) : ?>
						<div class="control-group">
							<div class="control-label">
								<?php echo $this->form->getLabel('state'); ?>
							</div>
							<div class="controls">
								<?php echo $this->form->getInput('state'); ?>
							</div>
						</div>
						<div class="control-group">
							<div class="control-label">
								<?php echo $this->form->getLabel('publish_up'); ?>
							</div>
							<div class="controls">
								<?php echo $this->form->getInput('publish_up'); ?>
							</div>
						</div>
						<div class="control-group">
							<div class="control-label">
								<?php echo $this->form->getLabel('publish_down'); ?>
							</div>
							<div class="controls">
								<?php echo $this->form->getInput('publish_down'); ?>
							</div>
						</div>
					<?php endif; ?>
					<div class="control-group">
						<div class="control-label">
							<?php echo $this->form->getLabel('access'); ?>
						</div>
						<div class="controls">
							<?php echo $this->form->getInput('access'); ?>
						</div>
					</div>
				</div>
				
				<div class="tab-pane" id="metadata">
					<div class="control-group">
						<div class="control-label">
							<?php echo $this->form->getLabel('metadesc'); ?>
						</div>
						<div class="controls">
							<?php echo $this->form->getInput('metadesc'); ?>
						</div>
					</div>
					<div class="control-group">
						<div class="control-label">
							<?php echo $this->form->getLabel('metakey'); ?>
						</div>
						<div class="controls">
							<?php echo $this->form->getInput('metakey'); ?>
						</div>
					</div>

					<input type="hidden" name="task" value="" />
					<input type="hidden" name="jform[id]" id="jform_id" value="<?php echo $this->item->id; ?>" />
					<input type="hidden" name="jform[collection]" id="jform_collection" value="<?php echo $this->collection->id; ?>" />
					<?php echo JHtml::_( 'form.token' ); ?>
				</div>
			</div>
		</fieldset>
	</form>
</div>