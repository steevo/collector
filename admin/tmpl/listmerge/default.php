<?php
/**
 * Joomla! component Collector
 *
 * @package     Collector
 * @copyright   Copyright (C) 2010 - 2026 Philippe Ousset. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

defined('_JEXEC') or die('Restricted access');

use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Router\Route;

/** @var \Steevo\Component\Collector\Administrator\View\Listmerge\HtmlView $this */

$wa = $this->getDocument()->getWebAssetManager();
$wa->usePreset('choicesjs')->useScript('webcomponent.field-fancy-select');
$wa->addInlineScript(<<<JS
document.addEventListener('DOMContentLoaded', function () {
	var form = document.getElementById('adminForm');
	var source = document.getElementById('listmerge-source');
	var target = document.getElementById('listmerge-target');
	var reload = function (s, t) {
		window.location.href = form.dataset.url + '&source=' + encodeURIComponent(s) + '&target=' + encodeURIComponent(t);
	};
	source.addEventListener('change', function () { reload(source.value, target.value); });
	target.addEventListener('change', function () { reload(source.value, target.value); });
	document.getElementById('listmerge-swap').addEventListener('click', function () { reload(target.value, source.value); });
	var go = document.getElementById('listmerge-do');
	if (go) {
		go.addEventListener('click', function () {
			if (window.confirm(go.dataset.confirm)) {
				form.task.value = 'listitems.domerge';
				form.submit();
			}
		});
	}
});
JS);

$s = $this->summary;

/**
 * Options de liste : valeurs de la liste en arbre, avec leur nombre d'objets.
 */
$options = function (int $selected) {
	$html = '<option value="0">' . Text::_('COM_COLLECTOR_LISTMERGE_CHOOSE') . '</option>';

	foreach ($this->values as $value) {
		$items  = $this->usage[(int) $value->id]['items'] ?? 0;
		$label  = str_repeat('– ', max(0, (int) $value->level - 1)) . $value->content;
		$label  = Text::sprintf('COM_COLLECTOR_LISTMERGE_OPTION', $label, (int) $value->id, $items);
		$html  .= '<option value="' . (int) $value->id . '"' . ((int) $value->id === $selected ? ' selected' : '') . '>'
			. htmlspecialchars($label, ENT_QUOTES, 'UTF-8') . '</option>';
	}

	return $html;
};

$usageText = function (array $usage) {
	$text = Text::plural('COM_COLLECTOR_LISTMERGE_N_ITEMS', $usage['active']);
	$old  = $usage['items'] - $usage['active'];

	if ($old > 0) {
		$text .= ' ' . Text::plural('COM_COLLECTOR_LISTMERGE_N_OLD_ITEMS', $old);
	}

	return $text . ' — ' . Text::plural('COM_COLLECTOR_LISTMERGE_N_VERSIONS', $usage['versions']);
};
?>
<form action="<?php echo Route::_('index.php?option=com_collector&view=listmerge&defined=' . (int) $this->defined); ?>" method="post" name="adminForm" id="adminForm"
	data-url="<?php echo $this->escape(Route::_('index.php?option=com_collector&view=listmerge&defined=' . (int) $this->defined . ($this->return ? '&return=' . $this->return : ''), false)); ?>">
	<p class="lead"><?php echo Text::_('COM_COLLECTOR_LISTMERGE_INTRO'); ?></p>

	<div class="row align-items-end">
		<div class="col-lg-5 mb-3">
			<label class="form-label fw-bold" for="listmerge-source"><?php echo Text::_('COM_COLLECTOR_LISTMERGE_SOURCE'); ?></label>
			<div class="form-text mb-1"><?php echo Text::_('COM_COLLECTOR_LISTMERGE_SOURCE_DESC'); ?></div>
			<joomla-field-fancy-select search-placeholder="<?php echo $this->escape(Text::_('JSEARCH_FILTER')); ?>">
				<select id="listmerge-source" name="source" class="form-select" data-refresh><?php echo $options($this->source); ?></select>
			</joomla-field-fancy-select>
		</div>
		<div class="col-lg-2 mb-3 text-center">
			<button type="button" id="listmerge-swap" class="btn btn-outline-secondary" title="<?php echo $this->escape(Text::_('COM_COLLECTOR_LISTMERGE_SWAP')); ?>">
				<span class="icon-arrow-right" aria-hidden="true"></span> <?php echo Text::_('COM_COLLECTOR_LISTMERGE_SWAP'); ?>
			</button>
		</div>
		<div class="col-lg-5 mb-3">
			<label class="form-label fw-bold" for="listmerge-target"><?php echo Text::_('COM_COLLECTOR_LISTMERGE_TARGET'); ?></label>
			<div class="form-text mb-1"><?php echo Text::_('COM_COLLECTOR_LISTMERGE_TARGET_DESC'); ?></div>
			<joomla-field-fancy-select search-placeholder="<?php echo $this->escape(Text::_('JSEARCH_FILTER')); ?>">
				<select id="listmerge-target" name="target" class="form-select" data-refresh><?php echo $options($this->target); ?></select>
			</joomla-field-fancy-select>
		</div>
	</div>

	<?php foreach ($s->errors as $error) : ?>
		<div class="alert alert-danger"><?php echo $this->escape($error); ?></div>
	<?php endforeach; ?>

	<?php if ($this->canMerge) : ?>
		<div class="card mb-3">
			<div class="card-header fw-bold"><?php echo Text::_('COM_COLLECTOR_LISTMERGE_SUMMARY'); ?></div>
			<div class="card-body">
				<table class="table table-sm mb-3">
					<thead>
						<tr>
							<th scope="col"></th>
							<th scope="col"><?php echo Text::_('COM_COLLECTOR_LISTMERGE_VALUE'); ?></th>
							<th scope="col"><?php echo Text::_('COM_COLLECTOR_LISTMERGE_USAGE'); ?></th>
						</tr>
					</thead>
					<tbody>
						<tr class="table-danger">
							<th scope="row"><?php echo Text::_('COM_COLLECTOR_LISTMERGE_SOURCE_SHORT'); ?></th>
							<td><?php echo $this->escape($s->source->labelPath); ?> <span class="text-muted">#<?php echo (int) $s->source->id; ?></span></td>
							<td><?php echo $usageText($s->sourceUsage); ?></td>
						</tr>
						<tr class="table-success">
							<th scope="row"><?php echo Text::_('COM_COLLECTOR_LISTMERGE_TARGET_SHORT'); ?></th>
							<td><?php echo $this->escape($s->target->labelPath); ?> <span class="text-muted">#<?php echo (int) $s->target->id; ?></span></td>
							<td><?php echo $usageText($s->targetUsage); ?></td>
						</tr>
					</tbody>
				</table>

				<ul class="mb-0">
					<?php if ($s->sourceFields) : ?>
						<li>
							<?php echo Text::sprintf('COM_COLLECTOR_LISTMERGE_WILL_ITEMS', $this->escape($s->target->content)); ?>
							<ul>
								<?php foreach ($s->sourceFields as $row) : ?>
									<li><?php echo Text::sprintf(
										'COM_COLLECTOR_LISTMERGE_FIELD_LINE',
										$this->escape($row['field']->collection_name),
										$this->escape($row['field']->field),
										$usageText($row)
									); ?><?php if ($row['field']->intitle) : ?>
										<span class="badge bg-info"><?php echo Text::_('COM_COLLECTOR_LISTMERGE_IN_TITLE'); ?></span>
									<?php endif; ?></li>
								<?php endforeach; ?>
							</ul>
						</li>
					<?php else : ?>
						<li><?php echo Text::_('COM_COLLECTOR_LISTMERGE_NO_ITEMS'); ?></li>
					<?php endif; ?>

					<?php if ($s->moves) : ?>
						<li><?php echo Text::sprintf('COM_COLLECTOR_LISTMERGE_WILL_MOVE', count($s->moves), $this->escape(implode(', ', $s->moves)), $this->escape($s->target->content)); ?></li>
					<?php endif; ?>

					<?php foreach ($s->pairs as $pair) : ?>
						<li><?php echo Text::sprintf('COM_COLLECTOR_LISTMERGE_WILL_MERGE_CHILD', $this->escape($pair['from']), $this->escape($pair['to']), Text::plural('COM_COLLECTOR_LISTMERGE_N_ITEMS', $pair['items'])); ?></li>
					<?php endforeach; ?>

					<?php if ($s->import) : ?>
						<li><?php echo Text::plural('COM_COLLECTOR_LISTMERGE_WILL_IMPORT', $s->import); ?></li>
					<?php endif; ?>

					<?php if ($s->sessions) : ?>
						<li><?php echo Text::plural('COM_COLLECTOR_LISTMERGE_WILL_SESSIONS', $s->sessions); ?></li>
					<?php endif; ?>

					<?php if ($s->menus) : ?>
						<li><?php echo Text::plural('COM_COLLECTOR_LISTMERGE_WILL_MENUS', $s->menus); ?></li>
					<?php endif; ?>

					<li><strong><?php echo Text::sprintf('COM_COLLECTOR_LISTMERGE_WILL_DELETE', $this->escape($s->source->content)); ?></strong></li>
				</ul>
			</div>
		</div>

		<div class="alert alert-warning"><?php echo Text::_('COM_COLLECTOR_LISTMERGE_IRREVERSIBLE'); ?></div>

		<button type="button" id="listmerge-do" class="btn btn-danger"
			data-confirm="<?php echo $this->escape(Text::sprintf('COM_COLLECTOR_LISTMERGE_CONFIRM', $s->source->content, $s->target->content)); ?>">
			<span class="icon-shuffle" aria-hidden="true"></span>
			<?php echo Text::sprintf('COM_COLLECTOR_LISTMERGE_DO', $this->escape($s->source->content), $this->escape($s->target->content)); ?>
		</button>
	<?php elseif (!$s->errors) : ?>
		<div class="alert alert-info"><?php echo Text::_('COM_COLLECTOR_LISTMERGE_PICK'); ?></div>
	<?php endif; ?>

	<input type="hidden" name="defined" value="<?php echo (int) $this->defined; ?>">
	<input type="hidden" name="return" value="<?php echo $this->escape($this->return); ?>">
	<input type="hidden" name="task" value="">
	<?php echo HTMLHelper::_('form.token'); ?>
</form>
