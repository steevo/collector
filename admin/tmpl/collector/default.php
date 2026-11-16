<?php
/**
 * Joomla! component Collector
 *
 * @package     Collector
 * @copyright   Copyright (C) 2010 - 2026 Philippe Ousset. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 *
 * Tableau de bord : import en cours, chiffres clés, objets les plus présents dans les listes
 * des membres, collections, objets à valider, activité récente, santé, support et à propos.
 */

defined('_JEXEC') or die('Restricted access');

use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Router\Route;

$canCreate = $this->canDo->get('core.create');
$version   = $this->versionStatus();
$noTop     = !array_filter($this->top);

// Listes des membres : [titre, icône, couleur]
$listTypes = [
	0 => ['COM_COLLECTOR_DASHBOARD_TOP_WANTED', 'icon-heart', 'danger'],
	1 => ['COM_COLLECTOR_DASHBOARD_TOP_EXCHANGE', 'icon-refresh', 'info'],
	2 => ['COM_COLLECTOR_DASHBOARD_TOP_COLLECTED', 'icon-star', 'success'],
];
?>
<style>
.collector-dashboard .cd-metric{border-left:4px solid var(--cd-accent,var(--bs-primary));}
.collector-dashboard .cd-metric .cd-value{font-size:1.9rem;font-weight:600;line-height:1.1}
.collector-dashboard .cd-metric .cd-icon{font-size:1.6rem;opacity:.35}
.collector-dashboard .cd-rank{display:inline-flex;align-items:center;justify-content:center;width:1.5rem;height:1.5rem;border-radius:50%;
  background:var(--bs-secondary-bg);font-size:.75rem;font-weight:600;flex-shrink:0}
.collector-dashboard .cd-list li{display:flex;align-items:center;gap:.5rem;padding:.35rem 0;border-bottom:1px solid var(--bs-border-color-translucent)}
.collector-dashboard .cd-list li:last-child{border-bottom:0}
.collector-dashboard .cd-list .cd-title{flex:1;min-width:0;overflow:hidden;text-overflow:ellipsis;white-space:nowrap}
.collector-dashboard .cd-progress{height:.5rem}
.collector-dashboard .card-header{font-weight:600}
.collector-dashboard .alert a.btn-primary{color:#fff;text-decoration:none}
.collector-dashboard .table td,.collector-dashboard .table th{vertical-align:middle}
</style>

<div class="collector-dashboard">

	<?php // En-tête : version et actions rapides ?>
	<div class="d-flex flex-wrap align-items-center gap-2 mb-3">
		<h2 class="h4 mb-0 me-2"><?php echo Text::_('COM_COLLECTOR_DASHBOARD'); ?></h2>
		<span class="badge bg-secondary"><?php echo Text::sprintf('COM_COLLECTOR_DASHBOARD_VERSION', $this->escape($this->installedVersion)); ?></span>
		<?php if ($version) : ?>
			<span class="badge <?php echo $version[0]; ?>"><span class="<?php echo $version[1]; ?>" aria-hidden="true"></span> <?php echo $version[2]; ?></span>
			<?php if ($version[1] === 'icon-warning') : ?>
				<a class="small" href="<?php echo Route::_('index.php?option=com_installer&view=update'); ?>"><?php echo Text::sprintf('COM_COLLECTOR_DASHBOARD_UPDATE_TO', $this->escape($this->lastVersion)); ?></a>
			<?php endif; ?>
		<?php endif; ?>
		<div class="ms-auto d-flex flex-wrap gap-2">
			<?php if ($canCreate) : ?>
				<a class="btn btn-sm btn-success" href="<?php echo Route::_('index.php?option=com_collector&task=collection.add'); ?>">
					<span class="icon-plus" aria-hidden="true"></span> <?php echo Text::_('COM_COLLECTOR_DASHBOARD_NEW_COLLECTION'); ?>
				</a>
				<a class="btn btn-sm btn-primary" href="<?php echo Route::_('index.php?option=com_collector&view=import'); ?>">
					<span class="icon-upload" aria-hidden="true"></span> <?php echo Text::_('COM_COLLECTOR_DASHBOARD_IMPORT'); ?>
				</a>
			<?php endif; ?>
			<a class="btn btn-sm btn-outline-secondary" href="<?php echo Route::_('index.php?option=com_collector&view=tools'); ?>">
				<span class="icon-wrench" aria-hidden="true"></span> <?php echo Text::_('COM_COLLECTOR_TOOLS'); ?>
			</a>
		</div>
	</div>

	<?php // Imports en cours (session non terminée) ?>
	<?php foreach ($this->imports as $import) :
		$percent = $import->total_rows > 0 ? min(100, (int) round($import->current_row * 100 / $import->total_rows)) : 0; ?>
		<div class="alert alert-info d-flex flex-wrap align-items-center gap-3" role="status">
			<span class="icon-upload fs-4" aria-hidden="true"></span>
			<div class="flex-grow-1" style="min-width:14rem">
				<strong><?php echo Text::sprintf('COM_COLLECTOR_DASHBOARD_IMPORT_RUNNING', $this->escape($import->collection_name ?? '#' . $import->collection)); ?></strong>
				<div class="small">
					<?php echo $this->escape(basename((string) $import->file)); ?>
					— <?php echo Text::sprintf('COM_COLLECTOR_DASHBOARD_IMPORT_PROGRESS', (int) $import->current_row, (int) $import->total_rows, (int) $import->imported, (int) $import->merged, (int) $import->skipped); ?>
					<?php if ($import->author) : ?> — <?php echo $this->escape($import->author); ?><?php endif; ?>
					<?php if ($import->updated && $import->updated !== '0000-00-00 00:00:00') : ?> — <?php echo $this->relativeDate($import->updated); ?><?php endif; ?>
				</div>
				<div class="progress cd-progress mt-1" role="progressbar" aria-valuenow="<?php echo $percent; ?>" aria-valuemin="0" aria-valuemax="100">
					<div class="progress-bar" style="width:<?php echo $percent; ?>%"></div>
				</div>
			</div>
			<a class="btn btn-sm btn-primary" href="<?php echo Route::_('index.php?option=com_collector&view=import&collection=' . (int) $import->collection . '&step=5'); ?>">
				<?php echo Text::_('COM_COLLECTOR_DASHBOARD_IMPORT_RESUME'); ?>
			</a>
		</div>
	<?php endforeach; ?>

	<?php // Chiffres clés ?>
	<div class="row g-3 mb-3">
		<div class="col-6 col-lg-3">
			<div class="card h-100 cd-metric" style="--cd-accent:var(--bs-primary)">
				<div class="card-body d-flex justify-content-between">
					<div>
						<div class="cd-value"><?php echo count($this->collections); ?></div>
						<div class="text-body-secondary small"><?php echo Text::_('COM_COLLECTOR_COLLECTIONS'); ?></div>
					</div>
					<span class="icon-folder cd-icon" aria-hidden="true"></span>
				</div>
			</div>
		</div>
		<div class="col-6 col-lg-3">
			<div class="card h-100 cd-metric" style="--cd-accent:var(--bs-success)">
				<div class="card-body d-flex justify-content-between">
					<div>
						<div class="cd-value"><?php echo number_format($this->totals->items, 0, ',', ' '); ?></div>
						<div class="text-body-secondary small">
							<?php echo Text::_('COM_COLLECTOR_ITEMS'); ?>
							· <?php echo Text::sprintf('COM_COLLECTOR_DASHBOARD_PUBLISHED_N', number_format($this->totals->published, 0, ',', ' ')); ?>
						</div>
					</div>
					<span class="icon-list cd-icon" aria-hidden="true"></span>
				</div>
			</div>
		</div>
		<div class="col-6 col-lg-3">
			<div class="card h-100 cd-metric" style="--cd-accent:var(--bs-<?php echo $this->totals->to_validate ? 'warning' : 'secondary'; ?>)">
				<div class="card-body d-flex justify-content-between">
					<div>
						<div class="cd-value"><?php echo $this->totals->to_validate; ?></div>
						<div class="text-body-secondary small">
							<?php echo Text::_('COM_COLLECTOR_ITEM_HISTORY_TO_BE_CHECKED'); ?>
							<?php if ($this->totals->proposals) : ?>
								· <?php echo Text::plural('COM_COLLECTOR_DASHBOARD_PROPOSALS_N', $this->totals->proposals); ?>
							<?php endif; ?>
						</div>
					</div>
					<span class="icon-clock cd-icon" aria-hidden="true"></span>
				</div>
			</div>
		</div>
		<div class="col-6 col-lg-3">
			<div class="card h-100 cd-metric" style="--cd-accent:var(--bs-info)">
				<div class="card-body d-flex justify-content-between">
					<div>
						<div class="cd-value"><?php echo (int) $this->members->members; ?></div>
						<div class="text-body-secondary small">
							<?php echo Text::_('COM_COLLECTOR_DASHBOARD_MEMBERS'); ?>
							· <?php echo Text::plural('COM_COLLECTOR_DASHBOARD_LISTED_N', (int) $this->members->items); ?>
						</div>
					</div>
					<span class="icon-users cd-icon" aria-hidden="true"></span>
				</div>
			</div>
		</div>
	</div>

	<?php // Objets les plus présents dans les listes des membres ?>
	<?php if (!$noTop) : ?>
	<div class="card mb-3">
		<div class="card-header"><?php echo Text::_('COM_COLLECTOR_DASHBOARD_TOP'); ?></div>
		<div class="card-body">
			<div class="row g-4">
				<?php foreach ($listTypes as $type => [$title, $icon, $color]) : ?>
				<div class="col-md-4">
					<h3 class="h6 text-<?php echo $color; ?>-emphasis"><span class="<?php echo $icon; ?>" aria-hidden="true"></span> <?php echo Text::_($title); ?></h3>
					<?php if (empty($this->top[$type])) : ?>
						<p class="text-body-secondary small mb-0"><?php echo Text::_('COM_COLLECTOR_DASHBOARD_TOP_EMPTY'); ?></p>
					<?php else : ?>
						<ol class="list-unstyled cd-list mb-0">
							<?php foreach ($this->top[$type] as $rank => $row) : ?>
							<li>
								<span class="cd-rank"><?php echo $rank + 1; ?></span>
								<a class="cd-title" href="<?php echo $this->itemEditLink((int) $row->collection, (int) $row->item); ?>" title="<?php echo $this->escape($row->fulltitle); ?>">
									<?php echo $this->escape($row->fulltitle); ?>
								</a>
								<span class="badge bg-<?php echo $color; ?>-subtle text-<?php echo $color; ?>-emphasis" title="<?php echo Text::plural('COM_COLLECTOR_DASHBOARD_MEMBERS_N', (int) $row->members); ?>">
									<span class="icon-user" aria-hidden="true"></span> <?php echo (int) $row->members; ?>
								</span>
							</li>
							<?php endforeach; ?>
						</ol>
					<?php endif; ?>
				</div>
				<?php endforeach; ?>
			</div>
		</div>
	</div>
	<?php endif; ?>

	<?php // Collections ?>
	<div class="card mb-3">
		<div class="card-header d-flex align-items-center">
			<?php echo Text::_('COM_COLLECTOR_COLLECTIONS'); ?>
			<a class="ms-auto small fw-normal" href="<?php echo Route::_('index.php?option=com_collector&view=collections'); ?>"><?php echo Text::_('COM_COLLECTOR_DASHBOARD_MANAGE'); ?></a>
		</div>
		<?php if (!$this->collections) : ?>
			<div class="card-body text-body-secondary"><?php echo Text::_('COM_COLLECTOR_DASHBOARD_NO_COLLECTION'); ?></div>
		<?php else : ?>
		<div class="table-responsive">
			<table class="table table-sm table-hover mb-0">
				<thead>
					<tr>
						<th scope="col" class="ps-3"><?php echo Text::_('JGLOBAL_TITLE'); ?></th>
						<th scope="col" class="text-end"><?php echo Text::_('COM_COLLECTOR_ITEMS'); ?></th>
						<th scope="col" class="text-end d-none d-md-table-cell"><?php echo Text::_('COM_COLLECTOR_DASHBOARD_PUBLISHED'); ?></th>
						<th scope="col" class="text-end"><?php echo Text::_('COM_COLLECTOR_ITEM_HISTORY_TO_BE_CHECKED'); ?></th>
						<td class="pe-3"></td>
					</tr>
				</thead>
				<tbody>
				<?php foreach ($this->collections as $c) : ?>
					<tr>
						<th scope="row" class="ps-3 fw-normal">
							<?php if ((int) $c->state !== 1) : ?>
								<span class="icon-unpublish text-body-secondary" title="<?php echo Text::_('JUNPUBLISHED'); ?>" aria-hidden="true"></span>
							<?php endif; ?>
							<a href="<?php echo Route::_('index.php?option=com_collector&task=collection.edit&id=' . (int) $c->id); ?>"><?php echo $this->escape($c->name); ?></a>
						</th>
						<td class="text-end"><?php echo number_format($c->total, 0, ',', ' '); ?></td>
						<td class="text-end d-none d-md-table-cell"><?php echo number_format($c->published, 0, ',', ' '); ?></td>
						<td class="text-end">
							<?php if ($c->to_validate) : ?>
								<span class="badge bg-warning text-dark"><?php echo $c->to_validate; ?></span>
							<?php else : ?>
								<span class="text-body-secondary">0</span>
							<?php endif; ?>
						</td>
						<td class="text-end pe-3 text-nowrap">
							<a class="btn btn-sm btn-outline-secondary" href="<?php echo Route::_('index.php?option=com_collector&view=items&collection=' . (int) $c->id); ?>"><?php echo Text::_('COM_COLLECTOR_ITEMS'); ?></a>
							<a class="btn btn-sm btn-outline-secondary d-none d-sm-inline-block" href="<?php echo Route::_('index.php?option=com_collector&view=fields&collection=' . (int) $c->id); ?>"><?php echo Text::_('COM_COLLECTOR_FIELDS'); ?></a>
							<a class="btn btn-sm btn-outline-secondary" href="<?php echo Route::link('site', 'index.php?option=com_collector&view=collection&id=' . (int) $c->id); ?>" target="_blank" title="<?php echo Text::_('COM_COLLECTOR_DASHBOARD_VIEW_SITE'); ?>">
								<?php echo Text::_('COM_COLLECTOR_DASHBOARD_SITE'); ?>
							</a>
						</td>
					</tr>
				<?php endforeach; ?>
				</tbody>
			</table>
		</div>
		<?php endif; ?>
	</div>

	<div class="row g-3 mb-3">
		<?php // Objets à valider ?>
		<div class="col-lg-6">
			<div class="card h-100">
				<div class="card-header d-flex align-items-center">
					<?php echo Text::_('COM_COLLECTOR_ITEM_HISTORY_TO_BE_CHECKED'); ?>
					<?php if ($this->totals->to_validate) : ?>
						<span class="badge bg-warning text-dark ms-2"><?php echo $this->totals->to_validate; ?></span>
					<?php endif; ?>
				</div>
				<?php if (!$this->pending) : ?>
					<div class="card-body text-body-secondary"><span class="icon-check text-success" aria-hidden="true"></span> <?php echo Text::_('COM_COLLECTOR_DASHBOARD_NOTHING_TO_VALIDATE'); ?></div>
				<?php else : ?>
				<ul class="list-group list-group-flush">
					<?php foreach ($this->pending as $p) :
						$link = $p->proposal ? $this->itemEditLink((int) $p->collection, (int) $p->item) : $this->itemVersionsLink((int) $p->collection, (int) $p->item); ?>
					<li class="list-group-item d-flex align-items-center gap-2">
						<span class="badge <?php echo $p->proposal ? 'bg-primary' : 'bg-secondary'; ?>">
							<?php echo Text::_($p->proposal ? 'COM_COLLECTOR_DASHBOARD_PROPOSAL' : 'COM_COLLECTOR_DASHBOARD_MODIFICATION'); ?>
						</span>
						<div class="flex-grow-1" style="min-width:0">
							<a class="d-block text-truncate" href="<?php echo $link; ?>"><?php echo $this->escape($p->fulltitle); ?></a>
							<span class="small text-body-secondary">
								<?php echo $this->escape($p->collection_name); ?>
								<?php if ($p->author) : ?> · <?php echo $this->escape($p->author); ?><?php endif; ?>
								· <?php echo $this->relativeDate($p->modified); ?>
							</span>
						</div>
						<a class="btn btn-sm btn-primary" href="<?php echo $link; ?>"><?php echo Text::_('COM_COLLECTOR_DASHBOARD_REVIEW'); ?></a>
					</li>
					<?php endforeach; ?>
				</ul>
				<?php endif; ?>
			</div>
		</div>

		<?php // Activité récente ?>
		<div class="col-lg-6">
			<div class="card h-100">
				<div class="card-header"><?php echo Text::_('COM_COLLECTOR_DASHBOARD_ACTIVITY'); ?></div>
				<?php if (!$this->activity) : ?>
					<div class="card-body text-body-secondary"><?php echo Text::_('COM_COLLECTOR_DASHBOARD_NO_ACTIVITY'); ?></div>
				<?php else : ?>
				<ul class="list-group list-group-flush">
					<?php foreach ($this->activity as $a) :
						if ($a->kind === 'version') {
							[$icon, $label] = (int) $a->state === 0
								? ['icon-clock text-warning', 'COM_COLLECTOR_DASHBOARD_ACTIVITY_PENDING']
								: ['icon-pencil text-success', 'COM_COLLECTOR_DASHBOARD_ACTIVITY_VERSION'];
						} else {
							[$icon, $label] = [
								0 => ['icon-heart text-danger', 'COM_COLLECTOR_DASHBOARD_ACTIVITY_WANTED'],
								1 => ['icon-refresh text-info', 'COM_COLLECTOR_DASHBOARD_ACTIVITY_EXCHANGE'],
							][(int) $a->list_type] ?? ['icon-star text-success', 'COM_COLLECTOR_DASHBOARD_ACTIVITY_COLLECTED'];
						} ?>
					<li class="list-group-item d-flex align-items-center gap-2">
						<span class="<?php echo $icon; ?>" aria-hidden="true"></span>
						<div class="flex-grow-1" style="min-width:0">
							<div class="text-truncate">
								<?php echo Text::sprintf($label, '<strong>' . $this->escape($a->author ?: Text::_('COM_COLLECTOR_DASHBOARD_SOMEONE')) . '</strong>'); ?>
								<a href="<?php echo $this->itemEditLink((int) $a->collection, (int) $a->item); ?>"><?php echo $this->escape($a->fulltitle); ?></a>
							</div>
							<span class="small text-body-secondary">
								<?php echo $this->escape($this->collectionNames[(int) $a->collection] ?? ''); ?> · <?php echo $this->relativeDate($a->date); ?>
							</span>
						</div>
					</li>
					<?php endforeach; ?>
				</ul>
				<?php endif; ?>
			</div>
		</div>
	</div>

	<?php // Santé, support, à propos ?>
	<div class="row g-3">
		<div class="col-md-4">
			<div class="card h-100">
				<div class="card-header"><?php echo Text::_('COM_COLLECTOR_DASHBOARD_HEALTH'); ?></div>
				<ul class="list-group list-group-flush small">
					<li class="list-group-item">
						<?php if ($this->mismatches) : ?>
							<span class="icon-warning text-warning" aria-hidden="true"></span>
							<?php echo Text::plural('COM_COLLECTOR_DASHBOARD_MISMATCHES_N', $this->mismatches); ?>
							<a href="<?php echo Route::_('index.php?option=com_collector&view=tools'); ?>"><?php echo Text::_('COM_COLLECTOR_TOOLS'); ?></a>
						<?php else : ?>
							<span class="icon-check text-success" aria-hidden="true"></span> <?php echo Text::_('COM_COLLECTOR_DASHBOARD_COUNTERS_OK'); ?>
						<?php endif; ?>
					</li>
					<li class="list-group-item">
						<span class="icon-upload text-body-secondary" aria-hidden="true"></span>
						<?php if ($this->lastImport) : ?>
							<?php echo Text::sprintf('COM_COLLECTOR_DASHBOARD_LAST_IMPORT', HTMLHelper::_('date', $this->lastImport, Text::_('DATE_FORMAT_LC4'))); ?>
							<a href="<?php echo Route::_('index.php?option=com_collector&view=importhistory'); ?>"><?php echo Text::_('COM_COLLECTOR_DASHBOARD_HISTORY'); ?></a>
						<?php else : ?>
							<?php echo Text::_('COM_COLLECTOR_DASHBOARD_NO_IMPORT'); ?>
						<?php endif; ?>
					</li>
					<li class="list-group-item">
						<span class="icon-info-circle text-body-secondary" aria-hidden="true"></span>
						<?php echo Text::_('COM_COLLECTOR_LAST_VERSION'); ?> : <?php echo $this->escape($this->lastVersion); ?>
					</li>
				</ul>
			</div>
		</div>
		<div class="col-md-4">
			<div class="card h-100">
				<div class="card-header"><?php echo Text::_('COM_COLLECTOR_SUPPORT'); ?></div>
				<ul class="list-group list-group-flush small">
					<li class="list-group-item"><a href="https://www.steevo.fr" target="_blank" rel="noopener"><?php echo Text::_('COM_COLLECTOR_SITE'); ?></a></li>
					<li class="list-group-item"><a href="https://forum.steevo.fr" target="_blank" rel="noopener"><?php echo Text::_('COM_COLLECTOR_FORUM'); ?></a></li>
					<li class="list-group-item"><a href="https://github.com/steevo/collector" target="_blank" rel="noopener"><?php echo Text::_('COM_COLLECTOR_GITHUB'); ?></a></li>
					<li class="list-group-item"><a href="mailto:steevo@steevo.fr"><?php echo Text::_('COM_COLLECTOR_CONTACT'); ?></a></li>
				</ul>
			</div>
		</div>
		<div class="col-md-4">
			<div class="card h-100">
				<div class="card-header"><?php echo Text::_('COM_COLLECTOR_ABOUT'); ?></div>
				<div class="card-body small">
					<p class="mb-2"><strong><?php echo Text::_('COM_COLLECTOR_THANKS'); ?></strong>
						<a href="http://www.cyrill-baur.ch/" target="_blank" rel="noopener">Cyrill Baur</a> (<?php echo Text::_('COM_COLLECTOR_GERMAN_TRANSLATION'); ?>),
						Sebastian Pućko (<?php echo Text::_('COM_COLLECTOR_POLISH_TRANSLATION'); ?>),
						Mora (<?php echo Text::_('COM_COLLECTOR_SPANISH_TRANSLATION'); ?>).
					</p>
					<p class="mb-0"><?php echo Text::_('COM_COLLECTOR_DISTRIBUTED'); ?>
						<a href="https://www.gnu.org/licenses/gpl-2.0.html" target="_blank" rel="noopener"><?php echo Text::_('COM_COLLECTOR_GPLV2'); ?></a>.
					</p>
				</div>
			</div>
		</div>
	</div>

</div>
