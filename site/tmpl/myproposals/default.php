<?php
/**
 * @package    Collector
 * @copyright  Copyright (C) 2010 - 2026 Philippe Ousset. All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 *
 * « Mes propositions » : état de chaque proposition du membre (en attente, validée, refusée avec le motif),
 * comparaison avec la fiche actuelle, correction et retrait d'une proposition en attente.
 */

defined('_JEXEC') or die;

use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Router\Route;
use Joomla\CMS\Session\Session;
use Joomla\CMS\Uri\Uri;

/** @var \Steevo\Component\Collector\Site\View\Myproposals\HtmlView $this */

HTMLHelper::stylesheet(Uri::base() . 'components/com_collector/assets/css/collection.css', ['version' => 'auto']);

// Comparaison avec la fiche actuelle : fenêtre intégrée à la page (media/js/version-dialog.js)
$wa = $this->document->getWebAssetManager();
$wa->getRegistry()->addExtensionRegistryFile('com_collector');
$wa->useScript('com_collector.version-dialog');

$token  = Session::getFormToken();
$filter = (string) $this->state->get('filter.state');
$labels = [
	'item'         => Text::_('COM_COLLECTOR_MYPROPOSALS_ITEM'),
	'date'         => Text::_('JDATE'),
	'status'       => Text::_('JSTATUS'),
	'modification' => Text::_('COM_COLLECTOR_ITEM_HISTORY_MODIFICATION'),
	'actions'      => Text::_('COM_COLLECTOR_ACTIONS'),
];

// Bouton carré à icône (comme dans les versions d'un objet), libellé au survol
$iconButton = static function (string $tag, string $class, string $icon, string $label, string $attr): string {
	$label = htmlspecialchars($label, ENT_QUOTES, 'UTF-8');

	return '<' . $tag . ' class="btn btn-sm ' . $class . ' collector-icon-btn" title="' . $label . '" aria-label="' . $label . '" ' . $attr . '>'
		. '<span class="' . $icon . '" aria-hidden="true"></span></' . $tag . '>';
};
?>
<div class="collector-myproposals category-list<?php echo $this->pageclass_sfx; ?>">

	<?php if ($this->params->get('show_page_heading', 1)) : ?>
		<div class="page-header">
			<h1><?php echo $this->escape($this->params->get('page_heading')); ?></h1>
		</div>
	<?php endif; ?>

	<form action="<?php echo Route::_('index.php?option=com_collector&view=myproposals'); ?>" method="get" class="mb-3 d-flex gap-2 align-items-center">
		<label for="filter_state" class="visually-hidden"><?php echo Text::_('JSTATUS'); ?></label>
		<select name="filter_state" id="filter_state" class="form-select w-auto" onchange="this.form.submit()">
			<option value=""><?php echo Text::_('COM_COLLECTOR_MYPROPOSALS_ALL'); ?></option>
			<option value="pending"<?php echo $filter === 'pending' ? ' selected' : ''; ?>><?php echo Text::_('COM_COLLECTOR_ITEM_HISTORY_PENDING'); ?></option>
			<option value="validated"<?php echo $filter === 'validated' ? ' selected' : ''; ?>><?php echo Text::_('COM_COLLECTOR_MYPROPOSALS_VALIDATED'); ?></option>
			<option value="refused"<?php echo $filter === 'refused' ? ' selected' : ''; ?>><?php echo Text::_('COM_COLLECTOR_ITEM_HISTORY_REFUSED_STATE'); ?></option>
		</select>
	</form>

	<?php if (empty($this->items)) : ?>
		<div class="alert alert-info"><?php echo Text::_('COM_COLLECTOR_MYPROPOSALS_EMPTY'); ?></div>
	<?php else : ?>
	<table class="category table table-striped table-bordered table-hover collector-table-stack collector-versions">
		<thead>
			<tr>
				<th><?php echo $labels['item']; ?></th>
				<th><?php echo $labels['date']; ?></th>
				<th><?php echo $labels['status']; ?></th>
				<th><?php echo $labels['modification']; ?></th>
				<th class="text-end"><?php echo $labels['actions']; ?></th>
			</tr>
		</thead>
		<tbody>
		<?php foreach ($this->items as $row) : ?>
			<?php
			$state     = (int) $row->state;
			$isPending = $state === 0;
			$itemUrl   = Route::_('index.php?option=com_collector&view=item&collection=' . (int) $row->collection . '&id=' . (int) $row->item);
			// Comparaison avec la fiche actuelle ; proposition d'un nouvel objet (ou version en cours) : elle seule
			$v1         = (int) ($row->active_id ?: $row->version_id);
			$compareUrl = Route::_('index.php?option=com_collector&view=versionscompare&tmpl=component&collection=' . (int) $row->collection
				. '&item=' . (int) $row->item . '&v1=' . $v1 . '&v2=' . (int) $row->version_id, false);
			$withdrawUrl = Route::_('index.php?option=com_collector&task=myproposals.withdraw&collection=' . (int) $row->collection
				. '&version_id=' . (int) $row->version_id . '&' . $token . '=1', false);
			$title = $row->fulltitle !== '' ? $row->fulltitle : '#' . (int) $row->item;
			?>
			<tr>
				<td data-label="<?php echo $this->escape($labels['item']); ?>" style="vertical-align:middle">
					<?php if ((int) $row->item_state >= 0) : ?>
						<a href="<?php echo $itemUrl; ?>"><?php echo $this->escape($title); ?></a>
					<?php else : ?>
						<?php echo $this->escape($title); ?>
					<?php endif; ?>
					<?php if (!$row->active_id && $isPending) : ?>
						<span class="d-block small text-muted"><?php echo Text::_('COM_COLLECTOR_MYPROPOSALS_NEW_ITEM'); ?></span>
					<?php endif; ?>
					<span class="d-block small text-muted"><?php echo $this->escape($row->collection_name); ?></span>
				</td>
				<td data-label="<?php echo $this->escape($labels['date']); ?>" style="vertical-align:middle">
					<?php echo HTMLHelper::_('date', $row->modified, Text::_('DATE_FORMAT_LC2')); ?>
				</td>
				<td data-label="<?php echo $this->escape($labels['status']); ?>" style="vertical-align:middle">
					<span>
					<?php if ($isPending) : ?>
						<span class="badge collector-status collector-status-pending">
							<span class="icon-clock me-1" aria-hidden="true"></span><?php echo Text::_('COM_COLLECTOR_ITEM_HISTORY_PENDING'); ?>
						</span>
					<?php elseif ($state === 3) : ?>
						<span class="badge collector-status collector-status-refused">
							<span class="icon-cancel me-1" aria-hidden="true"></span><?php echo Text::_('COM_COLLECTOR_ITEM_HISTORY_REFUSED_STATE'); ?>
						</span>
						<?php if (!empty($row->refuse_note)) : ?>
						<span class="d-block small text-muted fst-italic mt-1">
							<span class="icon-info-circle me-1" aria-hidden="true"></span><?php echo $this->escape($row->refuse_note); ?>
						</span>
						<?php endif; ?>
					<?php else : ?>
						<span class="badge collector-status collector-status-active">
							<span class="icon-check me-1" aria-hidden="true"></span><?php echo Text::_('COM_COLLECTOR_MYPROPOSALS_VALIDATED'); ?>
						</span>
					<?php endif; ?>
					</span>
				</td>
				<td data-label="<?php echo $this->escape($labels['modification']); ?>"<?php echo trim((string) $row->modification) === '' ? ' class="collector-cell-empty"' : ''; ?> style="vertical-align:middle">
					<?php echo $this->escape((string) $row->modification); ?>
				</td>
				<td data-label="" class="text-end text-nowrap" style="vertical-align:middle">
					<span class="collector-version-actions">
						<?php echo $iconButton('button', 'btn-outline-secondary', 'icon-eye', Text::_('COM_COLLECTOR_MYPROPOSALS_VIEW'),
							'type="button" onclick="Joomla.collectorVersionDialog.open(' . htmlspecialchars(json_encode($compareUrl), ENT_QUOTES) . ')"'); ?>
						<?php if ($isPending) : ?>
							<?php echo $iconButton('a', 'btn-outline-secondary', 'icon-edit', Text::_('COM_COLLECTOR_MYPROPOSALS_EDIT'),
								'href="' . $itemUrl . '"'); ?>
							<?php echo $iconButton('a', 'btn-outline-danger', 'icon-trash', Text::_('COM_COLLECTOR_MYPROPOSALS_WITHDRAW'),
								'href="' . $withdrawUrl . '" onclick="return confirm(' . htmlspecialchars(json_encode(Text::_('COM_COLLECTOR_MYPROPOSALS_WITHDRAW_CONFIRM')), ENT_QUOTES) . ')"'); ?>
						<?php endif; ?>
					</span>
				</td>
			</tr>
		<?php endforeach; ?>
		</tbody>
	</table>

	<?php echo $this->pagination->getListFooter(); ?>
	<?php endif; ?>
</div>
