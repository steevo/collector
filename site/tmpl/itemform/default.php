<?php
/**
 * @package    Collector
 * @copyright  Copyright (C) 2010 - 2026 Philippe Ousset. All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 */

defined('_JEXEC') or die;

use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Router\Route;

/** @var \Steevo\Component\Collector\Site\View\Itemform\HtmlView $this */

HTMLHelper::_('behavior.formvalidator');
HTMLHelper::_('behavior.keepalive');

$collId          = (int) $this->collection->id;
$showPublishing  = $this->params->get('show_publishfields', 0) || $this->canChangeState;
$showMetadata    = (bool) $this->params->get('show_metafields', 0);

// L'adresse du formulaire garde le lien de menu : ses paramètres (publication automatique…)
// s'appliquent à l'enregistrement
$formAction = Route::_('index.php?option=com_collector&view=itemform&collection=' . $collId, false);

$buttons = function () {
	?>
	<div class="btn-toolbar my-3">
		<div class="btn-group me-2">
			<button type="button" class="btn btn-primary" onclick="Joomla.submitbutton('item.save')">
				<span class="icon-check" aria-hidden="true"></span>&#160;<?php echo Text::_('COM_COLLECTOR_SUBMIT_BUTTON'); ?>
			</button>
		</div>
		<div class="btn-group">
			<button type="button" class="btn btn-secondary" onclick="Joomla.submitbutton('item.cancel')">
				<span class="icon-times" aria-hidden="true"></span>&#160;<?php echo Text::_('JCANCEL'); ?>
			</button>
		</div>
	</div>
	<?php
};
?>
<div class="edit item-page item-submit<?php echo $this->pageclass_sfx; ?>">
	<?php if ($this->params->get('show_page_heading', 1)) : ?>
		<div class="page-header">
			<h1><?php echo $this->escape($this->params->get('page_heading')); ?></h1>
		</div>
	<?php endif; ?>

	<form method="post" action="<?php echo $formAction; ?>" name="adminForm" id="adminForm" class="form-validate form-vertical">
		<input type="hidden" name="task" value="">
		<input type="hidden" name="jform[id]" id="jform_id" value="0">
		<input type="hidden" name="jform[collection]" id="jform_collection" value="<?php echo $collId; ?>">
		<?php echo HTMLHelper::_('form.token'); ?>

		<?php if ($showPublishing || $showMetadata) : ?>
			<?php echo HTMLHelper::_('uitab.startTabSet', 'submission', ['active' => 'details', 'breakpoint' => 768]); ?>
			<?php echo HTMLHelper::_('uitab.addTab', 'submission', 'details', Text::_('COM_COLLECTOR_ITEM_DETAILS')); ?>
		<?php endif; ?>

		<?php foreach ($this->fields as $field) : ?>
			<?php echo $this->form->renderField($field->_field->tablecolumn); ?>
		<?php endforeach; ?>

		<?php if ($showPublishing || $showMetadata) : ?>
			<?php echo HTMLHelper::_('uitab.endTab'); ?>

			<?php if ($showPublishing) : ?>
				<?php echo HTMLHelper::_('uitab.addTab', 'submission', 'publishing', Text::_('COM_COLLECTOR_PUBLISHING')); ?>
					<?php echo $this->form->renderField('created_by_alias'); ?>
					<?php if ($this->canChangeState) : ?>
						<?php echo $this->form->renderField('state'); ?>
						<?php echo $this->form->renderField('publish_up'); ?>
						<?php echo $this->form->renderField('publish_down'); ?>
					<?php endif; ?>
					<?php echo $this->form->renderField('access'); ?>
				<?php echo HTMLHelper::_('uitab.endTab'); ?>
			<?php endif; ?>

			<?php if ($showMetadata) : ?>
				<?php echo HTMLHelper::_('uitab.addTab', 'submission', 'metadata', Text::_('COM_COLLECTOR_METADATA')); ?>
					<?php echo $this->form->renderField('metadesc'); ?>
					<?php echo $this->form->renderField('metakey'); ?>
				<?php echo HTMLHelper::_('uitab.endTab'); ?>
			<?php endif; ?>

			<?php echo HTMLHelper::_('uitab.endTabSet'); ?>
		<?php endif; ?>

		<?php $buttons(); ?>
	</form>
</div>
