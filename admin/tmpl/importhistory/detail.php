<?php
defined('_JEXEC') or die;

use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Router\Route;
use Joomla\CMS\Session\Session;

HTMLHelper::_('behavior.multiselect');

$token      = Session::getFormToken();
$collection = $this->collectionId;
$import     = $this->import;
$items      = $this->items;
$importId   = $this->importId;

$hasModified = count(array_filter($items, fn($i) => $i->modified_since && $i->item_exists)) > 0;
?>

<?php /* ── Résumé de l'import ──────────────────────────────────────────── */ ?>
<div class="card mb-3">
  <div class="card-body">
    <div class="row g-3">
      <div class="col-auto">
        <span class="text-muted small"><?php echo Text::_('COM_COLLECTOR_IMPORTHISTORY_FILE'); ?></span><br>
        <strong><?php echo htmlspecialchars($import->file, ENT_QUOTES); ?></strong>
      </div>
      <div class="col-auto">
        <span class="text-muted small"><?php echo Text::_('JDATE'); ?></span><br>
        <strong><?php echo HTMLHelper::_('date', $import->created, Text::_('DATE_FORMAT_LC2')); ?></strong>
      </div>
      <div class="col-auto">
        <span class="text-muted small"><?php echo Text::_('COM_COLLECTOR_IMPORTHISTORY_USER'); ?></span><br>
        <strong><?php echo htmlspecialchars($import->created_by_name ?? '', ENT_QUOTES); ?></strong>
      </div>
      <div class="col-auto">
        <span class="text-muted small"><?php echo Text::_('COM_COLLECTOR_IMPORT_CNT_IMPORTED'); ?></span><br>
        <span class="badge bg-success"><?php echo (int) $import->imported; ?></span>
      </div>
      <div class="col-auto">
        <span class="text-muted small"><?php echo Text::_('COM_COLLECTOR_IMPORT_CNT_MERGED'); ?></span><br>
        <span class="badge bg-primary"><?php echo (int) $import->merged; ?></span>
      </div>
    </div>
  </div>
</div>

<?php /* ── Alerte objets modifiés ─────────────────────────────────────── */ ?>
<?php if ($hasModified): ?>
<div class="alert alert-warning small">
  <span class="icon-warning me-1"></span>
  <?php echo Text::_('COM_COLLECTOR_IMPORTHISTORY_MODIFIED_WARNING'); ?>
</div>
<?php endif; ?>

<?php /* ── Formulaire rollback ──────────────────────────────────────────── */ ?>
<form action="<?php echo Route::_('index.php?option=com_collector&task=importhistory.rollback', false); ?>"
      method="post" id="rollbackForm">

  <input type="hidden" name="import_id"     value="<?php echo $importId; ?>">
  <input type="hidden" name="collection"    value="<?php echo $collection; ?>">
  <input type="hidden" name="force_modified" value="0" id="force_modified">
  <input type="hidden" name="<?php echo $token; ?>" value="1">

  <?php /* ── Toolbar de sélection ─────────────────────────────────────── */ ?>
  <div class="d-flex align-items-center gap-2 mb-2">
    <div class="form-check">
      <input type="checkbox" id="checkAll" class="form-check-input"
             onclick="document.querySelectorAll('.item-chk').forEach(c => c.checked = this.checked)">
      <label for="checkAll" class="form-check-label small fw-semibold">
        <?php echo Text::_('JGLOBAL_CHECK_ALL'); ?>
      </label>
    </div>

    <?php /* Filtres rapides */ ?>
    <div class="ms-3 d-flex gap-1">
      <button type="button" class="btn btn-sm btn-outline-secondary active" id="filter-all"
              onclick="filterItems('all')">
        <?php echo Text::_('JALL'); ?>
      </button>
      <button type="button" class="btn btn-sm btn-outline-success" id="filter-imported"
              onclick="filterItems('imported')">
        <?php echo Text::_('COM_COLLECTOR_IMPORTHISTORY_ACTION_IMPORTED'); ?>
      </button>
      <button type="button" class="btn btn-sm btn-outline-primary" id="filter-merged"
              onclick="filterItems('merged')">
        <?php echo Text::_('COM_COLLECTOR_IMPORTHISTORY_ACTION_MERGED'); ?>
      </button>
      <?php if ($hasModified): ?>
      <button type="button" class="btn btn-sm btn-outline-warning" id="filter-modified"
              onclick="filterItems('modified')">
        <?php echo Text::_('COM_COLLECTOR_IMPORTHISTORY_FILTER_MODIFIED'); ?>
      </button>
      <?php endif; ?>
    </div>

    <div class="ms-auto">
      <button type="button" class="btn btn-danger btn-sm" onclick="confirmRollback()">
        <span class="icon-undo me-1"></span>
        <?php echo Text::_('COM_COLLECTOR_IMPORTHISTORY_ROLLBACK_BTN'); ?>
      </button>
    </div>
  </div>

  <?php /* ── Tableau des items ─────────────────────────────────────────── */ ?>
  <table class="table table-sm table-hover" id="items-table">
    <thead class="table-light">
      <tr>
        <th style="width:30px"></th>
        <th><?php echo Text::_('COM_COLLECTOR_IMPORTHISTORY_ITEM'); ?></th>
        <th><?php echo Text::_('COM_COLLECTOR_IMPORTHISTORY_ACTION'); ?></th>
        <th><?php echo Text::_('COM_COLLECTOR_IMPORTHISTORY_ORIGIN'); ?></th>
        <th><?php echo Text::_('COM_COLLECTOR_IMPORTHISTORY_STATUS'); ?></th>
        <th><?php echo Text::_('COM_COLLECTOR_IMPORTHISTORY_VERSION_BEFORE'); ?></th>
        <th><?php echo Text::_('COM_COLLECTOR_IMPORTHISTORY_VERSION_AFTER'); ?></th>
		<th><?php echo Text::_('COM_COLLECTOR_IMPORTHISTORY_ROLLBACK_STATUS'); ?></th>
      </tr>
    </thead>
    <tbody>
      <?php foreach ($items as $item): ?>
      <?php
        $isModified  = $item->modified_since && $item->item_exists;
        $notExists   = !$item->item_exists;
        $rowClass    = $isModified ? 'table-warning' : ($notExists ? 'table-secondary' : '');
      ?>
      <tr class="<?php echo $rowClass; ?> item-row"
          data-action="<?php echo $item->action; ?>"
          data-modified="<?php echo $isModified ? '1' : '0'; ?>">
        <td>
          <?php if ($item->can_rollback): ?>
          <input type="checkbox"
                 name="item_ids[]"
                 value="<?php echo (int) $item->item_id; ?>"
                 class="form-check-input item-chk">
          <?php endif; ?>
        </td>
        <td>
          <a href="<?php echo Route::_('index.php?option=com_collector&view=itemversions&collection=' . $collection . '&item=' . $item->item_id, false); ?>"
             target="_blank" class="small">
            <?php echo htmlspecialchars($item->item_alias, ENT_QUOTES); ?>
            <span class="icon-out-2 ms-1"></span>
          </a>
          <?php if ($notExists): ?>
          <span class="badge bg-secondary ms-1 small"><?php echo Text::_('COM_COLLECTOR_IMPORTHISTORY_ITEM_DELETED'); ?></span>
          <?php elseif ($isModified): ?>
          <span class="badge bg-warning text-dark ms-1 small"><?php echo Text::_('COM_COLLECTOR_IMPORTHISTORY_MODIFIED'); ?></span>
          <?php endif; ?>
          <?php if ($item->before_missing && !$item->rolled_back): ?>
          <span class="badge bg-danger ms-1 small" title="<?php echo htmlspecialchars(Text::_('COM_COLLECTOR_IMPORTHISTORY_ROLLBACK_IMPOSSIBLE_DESC'), ENT_QUOTES); ?>">
            <?php echo Text::_('COM_COLLECTOR_IMPORTHISTORY_ROLLBACK_IMPOSSIBLE'); ?>
          </span>
          <?php endif; ?>
        </td>
        <td>
          <?php if ($item->action === 'imported'): ?>
          <span class="badge bg-success"><?php echo Text::_('COM_COLLECTOR_IMPORTHISTORY_ACTION_IMPORTED'); ?></span>
          <?php else: ?>
          <span class="badge bg-primary"><?php echo Text::_('COM_COLLECTOR_IMPORTHISTORY_ACTION_MERGED'); ?></span>
          <?php endif; ?>
        </td>
        <td class="small">
          <?php if (!empty($item->origin)): ?>
          <span class="badge <?php echo $item->origin === 'auto' ? 'bg-light text-dark border' : 'bg-warning text-dark'; ?>">
            <?php echo Text::_('COM_COLLECTOR_IMPORTHISTORY_ORIGIN_' . strtoupper($item->origin)); ?>
          </span>
          <?php endif; ?>
          <?php if (!empty($item->file_row)): ?>
          <span class="text-muted ms-1"><?php echo Text::sprintf('COM_COLLECTOR_IMPORTHISTORY_FILE_ROW', (int) $item->file_row); ?></span>
          <?php endif; ?>
          <?php if (!empty($item->intra_merge) && empty($item->rolled_back)): ?>
          <div class="mt-1">
            <span class="badge bg-danger"><?php echo Text::_('COM_COLLECTOR_IMPORTHISTORY_INTRA_MERGE'); ?></span>
            <?php if ($item->can_split): ?>
            <button type="button" class="btn btn-outline-danger btn-sm py-0 px-2 ms-1"
                    onclick="collectorSplit(<?php echo (int) $item->id; ?>, <?php echo (int) $item->item_id; ?>)"
                    title="<?php echo htmlspecialchars(Text::_('COM_COLLECTOR_IMPORTHISTORY_SPLIT_DESC'), ENT_QUOTES); ?>">
              <?php echo Text::_('COM_COLLECTOR_IMPORTHISTORY_SPLIT_BTN'); ?>
            </button>
            <?php else: ?>
            <span class="small text-muted"><?php echo Text::_('COM_COLLECTOR_IMPORTHISTORY_SPLIT_IMPOSSIBLE'); ?></span>
            <?php endif; ?>
          </div>
          <?php endif; ?>
        </td>
        <td class="small">
          <?php if ($notExists): ?>
          <span class="text-muted"><?php echo Text::_('COM_COLLECTOR_IMPORTHISTORY_ITEM_GONE'); ?></span>
          <?php elseif ($isModified): ?>
          <span class="text-warning"><span class="icon-warning me-1"></span><?php echo Text::_('COM_COLLECTOR_IMPORTHISTORY_MODIFIED'); ?></span>
          <?php else: ?>
          <span class="text-success"><span class="icon-check me-1"></span><?php echo Text::_('COM_COLLECTOR_IMPORTHISTORY_INTACT'); ?></span>
          <?php endif; ?>
        </td>
        <td class="small text-muted"><?php echo $item->version_before ? '#' . $item->version_before : '—'; ?></td>
        <td class="small text-muted"><?php echo $item->version_after  ? '#' . $item->version_after  : '—'; ?></td>
		<td>
		  <?php if (!empty($item->rolled_back_at)): ?>
		  <span class="badge bg-dark">
			<span class="icon-undo me-1"></span>
			<?php echo Text::_('COM_COLLECTOR_IMPORTHISTORY_ROLLED_BACK'); ?>
			<small class="ms-1 opacity-75">
			  <?php echo HTMLHelper::_('date', $item->rolled_back_at, Text::_('DATE_FORMAT_LC2')); ?>
			</small>
		  </span>
		  <?php else: ?>
		  <span class="text-muted">—</span>
		  <?php endif; ?>
		</td>
      </tr>
      <?php endforeach; ?>
    </tbody>
  </table>
</form>

<?php /* ── Objets de la base absents du fichier ──────────────────────────── */ ?>
<?php $missing = $this->missing ?? ['items' => []]; ?>
<?php if (!empty($missing['items'])):
	$present = count(array_filter($missing['items'], fn($m) => $m['exists'] && $m['state'] !== -2 && empty($m['merged'])));
	$dupCmp  = []; // comparaisons objet non retrouvé / objet créé par l'import
?>
<div class="card mt-4" id="missing-items">
  <div class="card-header fw-semibold">
    <span class="icon-search me-1"></span>
    <?php echo Text::_('COM_COLLECTOR_IMPORT_MISSING_TITLE'); ?>
    <span class="badge bg-secondary ms-1"><?php echo count($missing['items']); ?></span>
  </div>
  <div class="card-body">
    <p class="small text-muted"><?php echo Text::sprintf('COM_COLLECTOR_IMPORTHISTORY_MISSING_HELP',
        htmlspecialchars(implode(' ; ', $missing['scope']), ENT_QUOTES)); ?></p>
    <?php if (array_filter(array_column($missing['items'], 'similar'))): ?>
    <p class="small text-muted"><span class="icon-arrow-right-4 me-1"></span><?php echo Text::_('COM_COLLECTOR_IMPORTHISTORY_MISSING_SIMILAR_HELP'); ?></p>
    <?php endif; ?>

    <form action="<?php echo Route::_('index.php?option=com_collector&task=importhistory.trashmissing', false); ?>"
          method="post" id="missingForm">
      <input type="hidden" name="import_id"  value="<?php echo $importId; ?>">
      <input type="hidden" name="collection" value="<?php echo $collection; ?>">
      <input type="hidden" name="<?php echo $token; ?>" value="1">

      <?php if ($present): ?>
      <div class="d-flex align-items-center gap-2 mb-2">
        <div class="form-check">
          <input type="checkbox" id="checkAllMissing" class="form-check-input"
                 onclick="document.querySelectorAll('.missing-chk').forEach(c => c.checked = this.checked)">
          <label for="checkAllMissing" class="form-check-label small fw-semibold"><?php echo Text::_('JGLOBAL_CHECK_ALL'); ?></label>
        </div>
        <div class="ms-auto">
          <button type="button" class="btn btn-sm btn-outline-danger" onclick="confirmTrashMissing()">
            <span class="icon-trash me-1"></span><?php echo Text::_('COM_COLLECTOR_IMPORTHISTORY_MISSING_TRASH_BTN'); ?>
          </button>
        </div>
      </div>
      <?php endif; ?>

      <div class="table-responsive">
      <table class="table table-sm table-hover mb-0">
        <thead class="table-light">
          <tr>
            <th style="width:30px"></th>
            <th class="small"><?php echo Text::_('COM_COLLECTOR_IMPORTHISTORY_ITEM'); ?></th>
            <?php foreach ($missing['cols'] as $label): ?>
            <th class="small"><?php echo htmlspecialchars($label, ENT_QUOTES); ?></th>
            <?php endforeach; ?>
            <th class="small"><?php echo Text::_('COM_COLLECTOR_IMPORTHISTORY_STATUS'); ?></th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($missing['items'] as $m):
            $trashed = $m['exists'] && $m['state'] === -2;
          ?>
          <tr class="<?php echo !$m['exists'] || $trashed || !empty($m['merged']) ? 'table-secondary' : ''; ?>">
            <td>
              <?php if ($m['exists'] && !$trashed && empty($m['merged'])): ?>
              <input type="checkbox" name="missing_ids[]" value="<?php echo (int) $m['id']; ?>" class="form-check-input missing-chk">
              <?php endif; ?>
            </td>
            <td class="small">
              <?php if ($m['exists']): ?>
              <a href="<?php echo Route::_('index.php?option=com_collector&task=item.edit&collection=' . $collection . '&id=' . (int) $m['id'], false); ?>"
                 target="_blank" class="badge bg-secondary text-decoration-none">#<?php echo (int) $m['id']; ?> <span class="icon-out-2"></span></a>
              <?php else: ?>
              <span class="badge bg-light text-dark">#<?php echo (int) $m['id']; ?></span>
              <?php endif; ?>
              <?php if (!empty($m['row'])): ?>
              <div class="small text-muted"><?php echo Text::sprintf('COM_COLLECTOR_IMPORT_MISSING_CANDIDATE', (int) $m['row']); ?></div>
              <?php endif; ?>
            </td>
            <?php foreach (array_keys($missing['cols']) as $col): ?>
            <td class="small"><?php echo htmlspecialchars((string) ($m['display'][$col] ?? '') ?: '—', ENT_QUOTES); ?></td>
            <?php endforeach; ?>
            <td class="small">
              <?php if (!empty($m['merged'])): ?>
              <span class="text-success"><span class="icon-check me-1"></span><?php echo Text::sprintf('COM_COLLECTOR_IMPORTHISTORY_DUP_MERGED_STATUS', (int) $m['merged']); ?></span>
              <?php elseif (!$m['exists']): ?>
              <span class="text-muted"><?php echo Text::_('COM_COLLECTOR_IMPORTHISTORY_ITEM_GONE'); ?></span>
              <?php elseif ($trashed): ?>
              <span class="text-muted"><span class="icon-trash me-1"></span><?php echo Text::_('COM_COLLECTOR_IMPORTHISTORY_MISSING_IN_TRASH'); ?></span>
              <?php elseif ($m['state'] === 1): ?>
              <span class="text-success"><?php echo Text::_('JPUBLISHED'); ?></span>
              <?php else: ?>
              <span class="text-muted"><?php echo Text::_('JUNPUBLISHED'); ?></span>
              <?php endif; ?>
              <?php if ($m['exists'] && !$trashed && empty($m['merged'])): ?>
              <div class="input-group input-group-sm mt-1 flex-nowrap" style="max-width:14rem">
                <span class="input-group-text">#</span>
                <input type="number" min="1" class="form-control dup-manual" data-target="<?php echo (int) $m['id']; ?>"
                       placeholder="<?php echo htmlspecialchars(Text::_('COM_COLLECTOR_IMPORTHISTORY_DUP_MANUAL_PH'), ENT_QUOTES); ?>"
                       aria-label="<?php echo htmlspecialchars(Text::_('COM_COLLECTOR_IMPORTHISTORY_DUP_MANUAL'), ENT_QUOTES); ?>"
                       onkeydown="if (event.key === 'Enter') { event.preventDefault(); collectorDupManual(this); }">
                <button type="button" class="btn btn-outline-primary" onclick="collectorDupManual(this.previousElementSibling)"
                        title="<?php echo htmlspecialchars(Text::_('COM_COLLECTOR_IMPORTHISTORY_DUP_MANUAL'), ENT_QUOTES); ?>">
                  <?php echo Text::_('COM_COLLECTOR_IMPORT_COMPARE_BTN'); ?>
                </button>
              </div>
              <?php endif; ?>
            </td>
          </tr>
          <?php foreach ($m['similar'] ?? [] as $s):
            $dupCmp[] = ['target' => (int) $m['id'], 'source' => (int) $s['id'], 'score' => (int) round($s['score'] * 100),
                         'a' => $m['display'], 'b' => $s['display'], 'trashed' => $trashed];
          ?>
          <tr class="table-info">
            <td></td>
            <td class="small text-nowrap">
              <span class="icon-arrow-right-4 me-1" aria-hidden="true"></span>
              <a href="<?php echo Route::_('index.php?option=com_collector&task=item.edit&collection=' . $collection . '&id=' . (int) $s['id'], false); ?>"
                 target="_blank" class="badge bg-info text-dark text-decoration-none">#<?php echo (int) $s['id']; ?> <span class="icon-out-2"></span></a>
              <div class="small text-muted"><?php echo Text::sprintf('COM_COLLECTOR_IMPORTHISTORY_MISSING_SIMILAR', (int) round($s['score'] * 100)); ?>
                <?php if (!empty($s['keys'])): ?><span class="badge bg-primary"><?php echo Text::_('COM_COLLECTOR_IMPORT_SIMILAR_BY_KEYS'); ?></span><?php endif; ?>
              </div>
            </td>
            <?php foreach (array_keys($missing['cols']) as $col): ?>
            <td class="small"><?php echo htmlspecialchars((string) ($s['display'][$col] ?? '') ?: '—', ENT_QUOTES); ?></td>
            <?php endforeach; ?>
            <td>
              <button type="button" class="btn btn-outline-primary btn-sm py-0 px-2"
                      onclick="collectorDupCompare(<?php echo count($dupCmp) - 1; ?>)">
                <span class="icon-search me-1" aria-hidden="true"></span><?php echo Text::_('COM_COLLECTOR_IMPORT_COMPARE_BTN'); ?>
              </button>
            </td>
          </tr>
          <?php endforeach; ?>
          <?php endforeach; ?>
        </tbody>
      </table>
      </div>
    </form>
  </div>
</div>
<?php /* ── Comparaison et fusion avec l'objet créé par erreur ───────── */ ?>
<form action="<?php echo Route::_('index.php?option=com_collector&task=importhistory.mergeduplicate', false); ?>"
      method="post" id="dupForm" class="d-none">
  <input type="hidden" name="import_id"  value="<?php echo $importId; ?>">
  <input type="hidden" name="collection" value="<?php echo $collection; ?>">
  <input type="hidden" name="target_id"  value="">
  <input type="hidden" name="source_id"  value="">
  <input type="hidden" name="<?php echo $token; ?>" value="1">
</form>
<dialog id="dup-compare" class="p-0 border-0 rounded shadow" style="max-width:min(1100px,96vw);width:100%"></dialog>
<script>
const DUP_CMP    = <?php echo json_encode($dupCmp, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT); ?>;
const DUP_FIELDS = <?php echo json_encode($missing['fields'] ?? [], JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT); ?>;
const DUP_T = {
    title    : <?php echo json_encode(Text::_('COM_COLLECTOR_IMPORTHISTORY_DUP_TITLE')); ?>,
    help     : <?php echo json_encode(Text::_('COM_COLLECTOR_IMPORTHISTORY_DUP_HELP')); ?>,
    field    : <?php echo json_encode(Text::_('COM_COLLECTOR_FIELDS')); ?>,
    inBase   : <?php echo json_encode(Text::_('COM_COLLECTOR_IMPORTHISTORY_DUP_IN_BASE')); ?>,
    created  : <?php echo json_encode(Text::_('COM_COLLECTOR_IMPORTHISTORY_DUP_CREATED')); ?>,
    take     : <?php echo json_encode(Text::_('COM_COLLECTOR_IMPORTHISTORY_DUP_TAKE')); ?>,
    all      : <?php echo json_encode(Text::_('COM_COLLECTOR_IMPORT_TAKE_ALL')); ?>,
    none     : <?php echo json_encode(Text::_('COM_COLLECTOR_IMPORT_TAKE_NONE')); ?>,
    onlyDiff : <?php echo json_encode(Text::_('COM_COLLECTOR_IMPORT_COMPARE_ONLY_DIFF')); ?>,
    merge    : <?php echo json_encode(Text::_('COM_COLLECTOR_IMPORTHISTORY_DUP_MERGE_BTN')); ?>,
    confirm  : <?php echo json_encode(Text::_('COM_COLLECTOR_IMPORTHISTORY_DUP_CONFIRM')); ?>,
    trashed  : <?php echo json_encode(Text::_('COM_COLLECTOR_IMPORTHISTORY_DUP_TARGET_TRASHED')); ?>,
    close    : <?php echo json_encode(Text::_('JCLOSE')); ?>,
};

function dupEsc(s) {
    return String(s ?? '').replace(/[&<>"']/g, c => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c]));
}

/** Objet non retrouvé et objet créé par l'import côte à côte, valeurs à reporter cochées. */
function collectorDupCompare(i) {
    const c = DUP_CMP[i];
    if (!c) return;
    const dlg = document.getElementById('dup-compare');
    const link = id => '<a href="index.php?option=com_collector&task=item.edit&collection=<?php echo (int) $collection; ?>&id=' + id
                     + '" target="_blank">#' + id + ' <span class="icon-out-2"></span></a>';

    let rows = '';
    Object.entries(DUP_FIELDS).forEach(([col, label]) => {
        const a = String(c.a[col] ?? ''), b = String(c.b[col] ?? '');
        const differs = a !== b;
        rows += '<tr' + (differs ? '' : ' data-same="1"') + '><th class="small text-muted fw-semibold">' + dupEsc(label) + '</th>'
              + '<td class="small">' + dupEsc(a || '—') + '</td>'
              + '<td class="small"' + (differs ? ' style="background:rgba(220,53,69,.10)"' : '') + '>'
              + (differs
                  ? '<label class="d-flex gap-1 mb-0"><input type="checkbox" class="form-check-input dup-take mt-1" value="' + dupEsc(col) + '"'
                    + (b !== '' ? ' checked' : '') + '><span>' + dupEsc(b || '—') + '</span></label>'
                  : dupEsc(b || '—'))
              + '</td></tr>';
    });

    dlg.innerHTML = '<div class="p-3">'
        + '<div class="d-flex justify-content-between align-items-center mb-2 gap-3">'
        + '<strong>' + dupEsc(DUP_T.title.replaceAll('%1$d', c.target).replaceAll('%2$d', c.source).replaceAll('%3$d', c.score)) + '</strong>'
        + '<label class="small text-nowrap"><input type="checkbox" class="form-check-input me-1" id="dup-only-diff">' + dupEsc(DUP_T.onlyDiff) + '</label></div>'
        + '<p class="small text-muted mb-2">' + dupEsc(DUP_T.help) + '</p>'
        + '<div class="table-responsive" style="max-height:60vh"><table class="table table-sm table-bordered mb-0"><thead class="table-light"><tr>'
        + '<th class="small">' + dupEsc(DUP_T.field) + '</th>'
        + '<th class="small">' + dupEsc(DUP_T.inBase) + ' ' + link(c.target) + '</th>'
        + '<th class="small">' + dupEsc(DUP_T.created) + ' ' + link(c.source)
        + '<div class="fw-normal">' + dupEsc(DUP_T.take) + ' : <a href="#" data-take="all">' + dupEsc(DUP_T.all) + '</a> · <a href="#" data-take="none">' + dupEsc(DUP_T.none) + '</a></div></th>'
        + '</tr></thead><tbody>' + rows + '</tbody></table></div>'
        + '<div class="d-flex justify-content-end gap-2 mt-3">'
        + (c.trashed ? '<span class="small text-muted me-auto">' + dupEsc(DUP_T.trashed) + '</span>'
                     : '<button type="button" class="btn btn-primary btn-sm" id="dup-merge"><span class="icon-git-merge me-1"></span>'
                       + dupEsc(DUP_T.merge.replaceAll('%1$d', c.target).replaceAll('%2$d', c.source)) + '</button>')
        + '<button type="button" class="btn btn-secondary btn-sm" id="dup-close">' + dupEsc(DUP_T.close) + '</button></div></div>';

    dlg.querySelectorAll('[data-take]').forEach(a => a.addEventListener('click', e => {
        e.preventDefault();
        dlg.querySelectorAll('input.dup-take').forEach(x => { x.checked = a.dataset.take === 'all'; });
    }));
    dlg.querySelector('#dup-only-diff').addEventListener('change', function () {
        dlg.querySelectorAll('tbody tr[data-same]').forEach(tr => tr.classList.toggle('d-none', this.checked));
    });
    dlg.querySelector('#dup-close').addEventListener('click', () => dlg.close());
    dlg.querySelector('#dup-merge')?.addEventListener('click', () => {
        if (!confirm(DUP_T.confirm.replaceAll('%1$d', c.target).replaceAll('%2$d', c.source))) return;
        const form = document.getElementById('dupForm');
        form.querySelector('[name="target_id"]').value = c.target;
        form.querySelector('[name="source_id"]').value = c.source;
        form.querySelectorAll('input[name="take[]"]').forEach(x => x.remove());
        dlg.querySelectorAll('input.dup-take:checked').forEach(x => {
            form.insertAdjacentHTML('beforeend', '<input type="hidden" name="take[]" value="' + dupEsc(x.value) + '">');
        });
        form.submit();
    });
    dlg.addEventListener('click', e => { if (e.target === dlg) dlg.close(); });
    dlg.showModal();
}

/** « Comparer avec # » : objet créé par l'import choisi par son numéro. */
async function collectorDupManual(input) {
    const source = parseInt(input.value, 10);
    if (!source) { input.focus(); return; }
    const url = 'index.php?option=com_collector&task=importhistory.dupcompare&format=raw'
              + '&import_id=<?php echo (int) $importId; ?>&collection=<?php echo (int) $collection; ?>'
              + '&target_id=' + encodeURIComponent(input.dataset.target) + '&source_id=' + source
              + '&<?php echo $token; ?>=1';
    let json = null;
    try {
        json = await (await fetch(url, {credentials: 'same-origin'})).json();
    } catch (e) {
        json = {success: false, message: String(e)};
    }
    if (!json || !json.success) {
        alert(json?.message || 'Erreur');
        return;
    }
    DUP_CMP.push(json);
    collectorDupCompare(DUP_CMP.length - 1);
}

function confirmTrashMissing() {
    const n = document.querySelectorAll('.missing-chk:checked').length;
    if (!n) {
        alert(<?php echo json_encode(Text::_('COM_COLLECTOR_IMPORTHISTORY_MISSING_SELECT_FIRST')); ?>);
        return;
    }
    if (confirm(<?php echo json_encode(Text::_('COM_COLLECTOR_IMPORTHISTORY_MISSING_TRASH_CONFIRM')); ?>.replace('%d', n))) {
        document.getElementById('missingForm').submit();
    }
}
</script>
<?php endif; ?>

<?php /* ── Séparation d'une fusion entre lignes du fichier ──────────────── */ ?>
<form action="<?php echo Route::_('index.php?option=com_collector&task=importhistory.split', false); ?>"
      method="post" id="splitForm" class="d-none">
  <input type="hidden" name="import_id"       value="<?php echo $importId; ?>">
  <input type="hidden" name="collection"      value="<?php echo $collection; ?>">
  <input type="hidden" name="history_item_id" value="">
  <input type="hidden" name="<?php echo $token; ?>" value="1">
</form>
<script>
function collectorSplit(historyItemId, itemId) {
    const msg = <?php echo json_encode(Text::_('COM_COLLECTOR_IMPORTHISTORY_SPLIT_CONFIRM')); ?>;
    if (!confirm(msg.replaceAll('%d', itemId))) return;
    const form = document.getElementById('splitForm');
    form.querySelector('[name="history_item_id"]').value = historyItemId;
    form.submit();
}
</script>

<?php /* ── Modale de confirmation rollback ──────────────────────────────── */ ?>
<div class="modal fade" id="modal-rollback" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title text-danger">
          <span class="icon-undo me-1"></span>
          <?php echo Text::_('COM_COLLECTOR_IMPORTHISTORY_ROLLBACK_CONFIRM_TITLE'); ?>
        </h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <p id="modal-rollback-summary"></p>
        <div id="modal-modified-warning" class="alert alert-warning small d-none">
          <span class="icon-warning me-1"></span>
          <span id="modal-modified-count"></span>
          <?php echo Text::_('COM_COLLECTOR_IMPORTHISTORY_ROLLBACK_MODIFIED_IN_SELECTION'); ?>
          <div class="form-check mt-2">
            <input type="checkbox" id="chk-force" class="form-check-input"
                   onchange="document.getElementById('force_modified').value = this.checked ? '1' : '0'">
            <label for="chk-force" class="form-check-label">
              <?php echo Text::_('COM_COLLECTOR_IMPORTHISTORY_ROLLBACK_FORCE'); ?>
            </label>
          </div>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">
          <?php echo Text::_('JCANCEL'); ?>
        </button>
        <button type="button" class="btn btn-danger" onclick="document.getElementById('rollbackForm').submit()">
          <span class="icon-undo me-1"></span>
          <?php echo Text::_('COM_COLLECTOR_IMPORTHISTORY_ROLLBACK_CONFIRM_BTN'); ?>
        </button>
      </div>
    </div>
  </div>
</div>

<script>
function filterItems(type) {
    document.querySelectorAll('.item-row').forEach(row => {
        const action   = row.dataset.action;
        const modified = row.dataset.modified === '1';
        let show = false;
        if (type === 'all')      show = true;
        if (type === 'imported') show = (action === 'imported');
        if (type === 'merged')   show = (action === 'merged');
        if (type === 'modified') show = modified;
        row.style.display = show ? '' : 'none';
    });
    document.querySelectorAll('[id^="filter-"]').forEach(b => b.classList.remove('active'));
    document.getElementById('filter-' + type)?.classList.add('active');
}

function confirmRollback() {
    const checked  = document.querySelectorAll('.item-chk:checked');
    if (!checked.length) {
        alert('<?php echo Text::_('COM_COLLECTOR_IMPORTHISTORY_ROLLBACK_SELECT_FIRST'); ?>');
        return;
    }

    // Compter les modifiés dans la sélection
    let modifiedCount = 0;
    checked.forEach(chk => {
        const row = chk.closest('tr');
        if (row.dataset.modified === '1') modifiedCount++;
    });

    document.getElementById('modal-rollback-summary').textContent =
        '<?php echo Text::_('COM_COLLECTOR_IMPORTHISTORY_ROLLBACK_WILL_PROCESS'); ?>'
        .replace('%d', checked.length);

    const modWarn = document.getElementById('modal-modified-warning');
    if (modifiedCount > 0) {
        modWarn.classList.remove('d-none');
        document.getElementById('modal-modified-count').textContent = modifiedCount;
    } else {
        modWarn.classList.add('d-none');
    }

    // Réinitialiser force
    document.getElementById('force_modified').value = '0';
    document.getElementById('chk-force').checked    = false;

    // Ouvrir la modale Bootstrap
    const modal = document.getElementById('modal-rollback');
    modal.style.display = 'block';
    modal.classList.add('show');
    document.body.classList.add('modal-open');
    const bd = document.createElement('div');
    bd.id = 'rollback-backdrop';
    bd.className = 'modal-backdrop fade show';
    document.body.appendChild(bd);

    modal.querySelectorAll('[data-bs-dismiss="modal"]').forEach(el => {
        el.addEventListener('click', closeRollbackModal);
    });
}

function closeRollbackModal() {
    const modal = document.getElementById('modal-rollback');
    modal.style.display = '';
    modal.classList.remove('show');
    document.body.classList.remove('modal-open');
    document.getElementById('rollback-backdrop')?.remove();
}
</script>