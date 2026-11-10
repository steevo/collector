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
          <?php if (!$notExists): ?>
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
        </td>
        <td>
          <?php if ($item->action === 'imported'): ?>
          <span class="badge bg-success"><?php echo Text::_('COM_COLLECTOR_IMPORTHISTORY_ACTION_IMPORTED'); ?></span>
          <?php else: ?>
          <span class="badge bg-primary"><?php echo Text::_('COM_COLLECTOR_IMPORTHISTORY_ACTION_MERGED'); ?></span>
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