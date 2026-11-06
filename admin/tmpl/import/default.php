<?php
/**
 * @package    Collector
 */
defined('_JEXEC') or die('Restricted access');

use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Router\Route;
use Joomla\CMS\Session\Session;

$token      = Session::getFormToken();
$collection = (int) $this->collection;
$step       = (int) $this->step;
$session    = $this->session;
$fields     = $this->fields;

// Libellés des étapes pour le stepper
$stepLabels = [
    1 => Text::_('COM_COLLECTOR_IMPORT_STEP1_LABEL'),
    2 => Text::_('COM_COLLECTOR_IMPORT_STEP2_LABEL'),
    3 => Text::_('COM_COLLECTOR_IMPORT_STEP3_LABEL'),
    4 => Text::_('COM_COLLECTOR_IMPORT_STEP4_LABEL'),
    5 => Text::_('COM_COLLECTOR_IMPORT_STEP5_LABEL'),
];

// Détermine si l'étape 3 est nécessaire (champs define)
$hasDefineFields = false;
foreach ($fields as $field) {
    if ($field->getImportMapping() !== null) { $hasDefineFields = true; break; }
}
if (!$hasDefineFields) {
    unset($stepLabels[3]);
    // Renuméroter visuellement : si on est à l'étape 4, afficher comme "3"
}
$visibleSteps = array_values($stepLabels);
?>

<style>
/* ── Stepper ── */
.import-stepper{
	display:flex;
	align-items:flex-start;
	margin-bottom:1.5rem
}
.import-step-item{
	display:flex;
	flex-direction:column;
	align-items:center;
	flex:1;
	min-width:0;
	position:relative
}
/*.import-step-item:last-child { flex: none; }*/

/* Ligne de connexion entre les étapes */
.import-step-item:not(:last-child)::after{
	content:'';
	position:absolute;
	top:15px;
	left:calc(50% + 16px);
	width:calc(100% - 32px);
	height:2px;
	background:#dee2e6;
	z-index:0
}
.import-step-item.done:not(:last-child)::after{background:#198754}

.step-circle{
	width:32px; height:32px;
	border-radius:50%;
	display:flex; align-items:center; justify-content:center;
	font-weight:700; font-size:.85rem;
	flex-shrink:0;
	border:2px solid #dee2e6;
	background:#fff; color:#6c757d;
	position:relative; z-index:1
}

.step-circle.done{background:#198754;border-color:#198754;color:#fff}
.step-circle.active{background:#0d6efd;border-color:#0d6efd;color:#fff}

.step-label{
	font-size:.72rem;
	color:#6c757d;
	text-align:center;
	margin-top:.35rem;
	line-height:1.2;
	word-break:break-word;
	padding:0 2px
}

.step-label.active { color: #0d6efd; font-weight: 600; }
.step-label.done   { color: #198754; }

/* ── Divers ── */
.file-row{cursor:pointer}
.file-row:hover td{background:rgba(13,110,253,.05)}
.file-row.selected td{background:rgba(13,110,253,.09);outline:2px solid #0d6efd}
.col-badge{font-family:var(--bs-font-monospace);font-weight:600}
.preview-table{font-size:.8rem}
.progress-log{max-height:120px;overflow-y:auto;font-size:.78rem}
.conflict-changed{background:rgba(253,126,20,.07)}
.cursor-pointer { cursor: pointer; }
.conflict-changed td.choice { cursor: pointer; transition: background .15s; }
.conflict-changed td.choice:hover { background: rgba(13,110,253,.08); }
.conflict-changed td.choice.selected-existing { background: rgba(25,135,84,.12); border-left: 3px solid #198754; }
.conflict-changed td.choice.selected-import  { background: rgba(13,110,253,.12); border-left: 3px solid #0d6efd; }

.cell-match  { background: rgba(25, 135, 84, .08); }
.cell-diff   { background: rgba(220, 53, 69, .08); }
.badge-identical { background: rgba(25,135,84,.15); color: #146c43; font-size:.7rem; }
.badge-similar   { background: rgba(255,193,7,.15);  color: #997404; font-size:.7rem; }
</style>

<!-- ══ STEPPER ════════════════════════════════════════════════════════════ -->
<div class="import-stepper mb-4">
<?php
$stepKeys   = array_keys($stepLabels);
$totalSteps = count($stepKeys);
foreach ($stepKeys as $si => $sKey):
    $visNum      = $si + 1;
    $isDone      = $step > $sKey;
    $isActive    = $step === $sKey;
    $circleClass = $isDone ? 'done' : ($isActive ? 'active' : '');
    $labelClass  = $isDone ? 'done' : ($isActive ? 'active' : '');
    $itemClass   = $isDone ? 'done' : '';
?>
    <div class="import-step-item <?php echo $itemClass; ?>">
        <div class="step-circle <?php echo $circleClass; ?>">
            <?php if ($isDone): ?>
                <span class="icon-check" aria-hidden="true"></span>
            <?php else: ?>
                <?php echo $visNum; ?>
            <?php endif; ?>
        </div>
        <span class="step-label <?php echo $labelClass; ?>">
            <?php echo htmlspecialchars($stepLabels[$sKey], ENT_QUOTES); ?>
        </span>
    </div>
<?php endforeach; ?>
</div>

<!-- Messages d'erreur éventuels -->
<?php if ($app = Joomla\CMS\Factory::getApplication()): ?>
<?php foreach ($app->getMessageQueue() as $msg): ?>
<div class="alert alert-<?php echo $msg['type'] === 'error' ? 'danger' : $msg['type']; ?> mb-3">
    <?php echo htmlspecialchars($msg['message'], ENT_QUOTES); ?>
</div>
<?php endforeach; ?>
<?php endif; ?>

<?php if ($this->hasResumableSession): ?>
<div class="modal fade" id="modal-restart-confirm" tabindex="-1" aria-labelledby="modal-restart-title" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content">

      <div class="modal-header">
        <h3 class="modal-title fs-5" id="modal-restart-title">
          <?php echo Text::_('COM_COLLECTOR_IMPORT_RESTART_CONFIRM_TITLE'); ?>
        </h3>
        <button type="button" class="btn-close" data-bs-dismiss="modal"
                aria-label="<?php echo Text::_('JCLOSE'); ?>"></button>
      </div>

      <div class="modal-body p-3">
		<p class="mb-0"><?php echo Text::_('COM_COLLECTOR_IMPORT_RESTART_CONFIRM_BODY'); ?></p>
	  </div>

	  <div class="modal-footer px-3 pb-3 pt-0 border-0">
		<button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">
		  <?php echo Text::_('JCANCEL'); ?>
		</button>
		<a id="btn-restart-confirm" href="#" class="btn btn-danger">
		  <span class="icon-refresh me-1" aria-hidden="true"></span>
		  <?php echo Text::_('COM_COLLECTOR_IMPORT_RESTART'); ?>
		</a>
	  </div>

    </div>
  </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    // Trouver le bouton reset dans la toolbar (chercher par icône ou texte)
    var btn = document.querySelector('.button-refresh, a[href*="import.reset"]');
    if (!btn) return;

    var modal   = document.getElementById('modal-restart-confirm');
    var confirm = document.getElementById('btn-restart-confirm');

    // Stocker le vrai href avant de le neutraliser
    confirm.setAttribute('href', btn.getAttribute('href'));
    btn.setAttribute('data-original-href', btn.getAttribute('href'));
    btn.setAttribute('href', '#');

    btn.addEventListener('click', function (e) {
        e.preventDefault();
        e.stopPropagation();

        // Afficher la modale manuellement sans bootstrap.Modal
        modal.style.display  = 'block';
        modal.style.paddingRight = '0';
        modal.classList.add('show');
        document.body.classList.add('modal-open');

        // Backdrop
        var bd = document.createElement('div');
        bd.id        = 'restart-modal-backdrop';
        bd.className = 'modal-backdrop fade show';
        document.body.appendChild(bd);
    });

    // Fermer la modale sur les boutons d'annulation et sur le backdrop
    modal.querySelectorAll('[data-bs-dismiss="modal"]').forEach(function(el) {
        el.addEventListener('click', closeModal);
    });
    document.addEventListener('click', function(e) {
        if (e.target === modal) closeModal();
    });

    function closeModal() {
        modal.style.display = '';
        modal.classList.remove('show');
        document.body.classList.remove('modal-open');
        var bd = document.getElementById('restart-modal-backdrop');
        if (bd) bd.remove();
    }
});
</script>

<?php endif; ?>

<?php /* ══════════════════════════════════════════════════════════════════════
   ÉTAPE 1 : Sélection / Upload du fichier
══════════════════════════════════════════════════════════════════════════ */ ?>
<?php if ($step === 1): ?>

<?php if ($this->hasResumableSession): ?>
<?php $ri = $this->resumeInfo; ?>
<div class="alert alert-warning mb-3">
  <div class="d-flex align-items-start gap-3">
    <span class="icon-warning fs-4 mt-1"></span>
    <div class="flex-grow-1">
      <strong><?php echo Text::_('COM_COLLECTOR_IMPORT_RESUME_FOUND'); ?></strong>
      <div class="small mt-1">
        <?php echo Text::sprintf('COM_COLLECTOR_IMPORT_RESUME_INFO',
            htmlspecialchars($ri['file'], ENT_QUOTES),
            $ri['done'],
            $ri['total']
        ); ?>
      </div>
      <div class="small text-muted mt-1">
        <?php echo Text::_('COM_COLLECTOR_IMPORT_CNT_IMPORTED'); ?> : <strong><?php echo $ri['imported']; ?></strong>
        &nbsp;·&nbsp;
        <?php echo Text::_('COM_COLLECTOR_IMPORT_CNT_MERGED'); ?> : <strong><?php echo $ri['merged']; ?></strong>
        &nbsp;·&nbsp;
        <?php echo Text::_('COM_COLLECTOR_IMPORT_CNT_SKIPPED'); ?> : <strong><?php echo $ri['skipped']; ?></strong>
        <?php if ($ri['errors'] > 0): ?>
        &nbsp;·&nbsp;
        <span class="text-danger"><?php echo Text::_('COM_COLLECTOR_IMPORT_CNT_ERRORS'); ?> : <strong><?php echo $ri['errors']; ?></strong></span>
        <?php endif; ?>
      </div>
    </div>
    <div class="d-flex flex-column gap-2">
      <a href="<?php echo Route::_('index.php?option=com_collector&view=import&collection=' . $collection . '&step=5', false); ?>"
         class="btn btn-warning btn-sm text-nowrap">
        <span class="icon-play me-1"></span>
        <?php echo Text::_('COM_COLLECTOR_IMPORT_RESUME_BTN'); ?>
      </a>
      <a href="<?php echo Route::_('index.php?option=com_collector&task=import.reset&collection=' . $collection . '&' . Session::getFormToken() . '=1', false); ?>"
         class="btn btn-outline-secondary btn-sm text-nowrap">
        <span class="icon-trash me-1"></span>
        <?php echo Text::_('COM_COLLECTOR_IMPORT_RESUME_DISCARD'); ?>
      </a>
    </div>
  </div>
</div>
<?php endif; ?>

<div class="card">
  <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-2">
    <span class="fw-semibold"><?php echo Text::_('COM_COLLECTOR_IMPORT_STEP1_TITLE'); ?></span>
    <?php echo $this->collectionsSelect; ?>
  </div>
  <div class="card-body">

    <?php if (empty($this->files)): ?>
      <div class="alert alert-info"><?php echo Text::_('COM_COLLECTOR_IMPORT_NO_FILES'); ?></div>
    <?php else: ?>
      <p class="text-muted small mb-2"><?php echo Text::_('COM_COLLECTOR_IMPORT_SELECT_FILE_HINT'); ?></p>
      <form method="post"
            action="<?php echo Route::_('index.php?option=com_collector&task=import.selectfile&collection=' . $collection, false); ?>">
        <table class="table table-sm table-hover mb-3">
          <thead class="table-light">
            <tr>
              <th class="w-1"></th>
              <th><?php echo Text::_('JGLOBAL_TITLE'); ?></th>
              <th><?php echo Text::_('COM_COLLECTOR_IMPORT_SIZE'); ?></th>
              <th><?php echo Text::_('JDATE'); ?></th>
            </tr>
          </thead>
          <tbody>
          <?php foreach ($this->files as $file): ?>
            <tr class="file-row" onclick="selectFile(this,'<?php echo htmlspecialchars($file['name'], ENT_QUOTES); ?>')">
              <td><input type="radio" name="file" class="form-check-input"
                         value="<?php echo htmlspecialchars($file['name'], ENT_QUOTES); ?>"></td>
              <td>
                <span class="icon-file-excel text-success me-1"></span>
                <?php echo htmlspecialchars($file['name'], ENT_QUOTES); ?>
                <span class="badge bg-secondary ms-1"><?php echo htmlspecialchars($file['ext'], ENT_QUOTES); ?></span>
              </td>
              <td class="text-muted small"><?php echo htmlspecialchars($file['size'], ENT_QUOTES); ?></td>
              <td class="text-muted small"><?php echo htmlspecialchars($file['modified'], ENT_QUOTES); ?></td>
            </tr>
          <?php endforeach; ?>
          </tbody>
        </table>
        <?php echo HTMLHelper::_('form.token'); ?>
        <button type="submit" id="btn-select" class="btn btn-primary" disabled>
          <?php echo Text::_('COM_COLLECTOR_IMPORT_STEP1_NEXT'); ?>
          <span class="icon-arrow-right ms-1"></span>
        </button>
      </form>
    <?php endif; ?>

    <hr class="my-3">
    <p class="fw-semibold small mb-1"><?php echo Text::_('COM_COLLECTOR_IMPORT_UPLOAD_NEW'); ?></p>

    <form method="post" enctype="multipart/form-data"
          action="<?php echo Route::_('index.php?option=com_collector&task=import.upload&collection=' . $collection, false); ?>">
      <div class="input-group">
        <input type="file" class="form-control form-control-sm" name="upload"
               accept=".xlsx,.csv" required>
        <button type="submit" class="btn btn-sm btn-outline-secondary">
          <span class="icon-upload me-1"></span><?php echo Text::_('COM_COLLECTOR_IMPORT_UPLOAD_BTN'); ?>
        </button>
      </div>
      <?php echo HTMLHelper::_('form.token'); ?>
    </form>

  </div>
</div>

<script>
function selectFile(row, filename) {
    document.querySelectorAll('.file-row').forEach(r => r.classList.remove('selected'));
    row.classList.add('selected');
    row.querySelector('input[type=radio]').checked = true;
    document.getElementById('btn-select').disabled = false;
}
function changeCollection(sel) {
    window.location.href = 'index.php?option=com_collector&view=import&collection=' + sel.value;
}
</script>

<?php /* ══════════════════════════════════════════════════════════════════════
   ÉTAPE 2 : Mapping colonnes → champs
══════════════════════════════════════════════════════════════════════════ */ ?>
<?php elseif ($step === 2):

    $preview   = $this->preview;
    $header    = $preview['header'] ?? [];
    $prevRows  = $preview['rows']   ?? [];
    $colCount  = max(count($header), !empty($prevRows) ? count($prevRows[0]) : 0);
    // Lettres de colonnes (A, B, C…)
    $colLetters = [];
    for ($c = 0; $c < $colCount; $c++) {
        $n = $c + 1; $s = '';
        while ($n > 0) { $r = ($n-1)%26; $s = chr(65+$r).$s; $n = intval(($n-$r-1)/26); }
        $colLetters[$c] = $s;
    }
?>

<div class="card">
  <div class="card-header fw-semibold"><?php echo Text::_('COM_COLLECTOR_IMPORT_STEP2_TITLE'); ?></div>
  <div class="card-body">

    <?php /* Fichier sélectionné */ ?>
    <p class="text-muted small mb-3">
      <span class="icon-file-excel text-success me-1"></span>
      <strong><?php echo htmlspecialchars($session['file'] ?? '', ENT_QUOTES); ?></strong>
      &nbsp;—&nbsp;<?php echo (int) ($session['total_rows'] ?? 0); ?> <?php echo Text::_('COM_COLLECTOR_IMPORT_ROWS'); ?>
    </p>

    <?php /* Prévisualisation des premières lignes */ ?>
    <?php if (!empty($prevRows)): ?>
    <div class="table-responsive mb-3">
      <table class="table table-sm table-bordered table-striped preview-table">
        <thead class="table-light">
          <tr>
            <th>#</th>
            <?php foreach ($colLetters as $c => $letter): ?>
            <th>
              <span class="col-badge"><?php echo $letter; ?></span>
              <?php if (!empty($header[$c])): ?>
                <span class="text-muted small d-block"><?php echo htmlspecialchars((string)$header[$c], ENT_QUOTES); ?></span>
              <?php endif; ?>
            </th>
            <?php endforeach; ?>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($prevRows as $ri => $row): ?>
          <tr>
            <th class="text-muted small"><?php echo ($session['first_line'] ?? 2) + $ri; ?></th>
            <?php for ($c = 0; $c < $colCount; $c++): ?>
            <td><?php echo htmlspecialchars((string)($row[$c] ?? ''), ENT_QUOTES); ?></td>
            <?php endfor; ?>
          </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
    <?php endif; ?>

    <form method="post"
          action="<?php echo Route::_('index.php?option=com_collector&task=import.savemapping&collection=' . $collection, false); ?>">

      <div class="row g-2 align-items-end mb-3">
        <div class="col-auto">
          <label class="form-label small fw-semibold" for="first_line">
            <?php echo Text::_('COM_COLLECTOR_EXCEL_IMPORT_FIRST_LINE_DATA'); ?>
          </label>
          <input type="number" id="first_line" name="first_line" class="form-control form-control-sm"
                 value="<?php echo (int)($session['first_line'] ?? $this->savedConfig['first_line'] ?? 2); ?>" min="1" style="width:80px">
        </div>
      </div>

      <p class="text-muted small mb-2"><?php echo Text::_('COM_COLLECTOR_IMPORT_MAPPING_HINT'); ?></p>

      <div class="row g-2">
      <?php foreach ($fields as $field): ?>
        <?php
        $col        = $field->_field->tablecolumn;
        $label      = $field->_field->field;
        $required   = !empty($field->_field->required);
        // Priorité : session en cours > config sauvegardée > vide
        $savedCol = $session['mapping'][$col]
			?? ($this->savedConfig['col_mapping'][$col] ?? null)
			?? ($this->autoDetectedMapping[$col]        ?? null)
			?? '';
		$isAutoDetected = ($this->autoDetectedMapping[$col] ?? null) !== null
			&& $savedCol === ($this->autoDetectedMapping[$col] ?? '')
			&& empty($session['mapping'][$col])
			&& empty($this->savedConfig['col_mapping'][$col] ?? null);
        ?>
        <div class="col-md-6 col-lg-4">
          <label class="form-label small" for="colmap-<?php echo htmlspecialchars($col, ENT_QUOTES); ?>">
            <?php echo htmlspecialchars($label, ENT_QUOTES); ?>
            <?php if ($required): ?><span class="text-danger">*</span><?php endif; ?>
			<?php if ($isAutoDetected): ?>
				<span class="badge bg-info text-dark ms-1" style="font-size:.65rem"
					  title="<?php echo Text::_('COM_COLLECTOR_IMPORT_AUTODETECT_HINT'); ?>">
					✦ <?php echo Text::_('COM_COLLECTOR_IMPORT_AUTODETECT_BADGE'); ?>
				</span>
			<?php endif; ?>
          </label>
          <div class="input-group input-group-sm">
            <span class="input-group-text col-badge"><?php echo Text::_('COM_COLLECTOR_IMPORT_COL'); ?></span>
            <select class="form-select"
                    id="colmap-<?php echo htmlspecialchars($col, ENT_QUOTES); ?>"
                    name="colmap[<?php echo htmlspecialchars($col, ENT_QUOTES); ?>]">
              <option value="">—</option>
              <?php foreach ($colLetters as $c => $letter): ?>
              <option value="<?php echo $letter; ?>"
                      <?php echo ($savedCol === $letter) ? 'selected' : ''; ?>>
                <?php echo $letter; ?>
                <?php if (!empty($header[$c])): ?>
                  — <?php echo htmlspecialchars((string)$header[$c], ENT_QUOTES); ?>
                <?php endif; ?>
              </option>
              <?php endforeach; ?>
            </select>
          </div>
        </div>
      <?php endforeach; ?>
      </div>
	  
	  <?php
		$savedStrategy  = $this->session['duplicate_strategy']
			?: (($this->savedConfig['duplicate_strategy'] ?? null) ?: 'unik');
		$savedComposite = (array) (
			$this->session['composite_fields']
			?: (($this->savedConfig['composite_fields'] ?? null)
				?: ($this->fulltitleCols ?? []))
		);
		$unikCols       = array_filter(
			array_map(fn($f) => $f->_field->unik ? $f->_field->tablecolumn : null, $this->fields)
		);
		?>

		<div class="card mt-4">
		  <div class="card-header fw-semibold small">
			<?php echo Text::_('COM_COLLECTOR_IMPORT_DUPLICATE_TITLE'); ?>
		  </div>
		  <div class="card-body">

			<?php if (!empty($unikCols)): ?>
			<div class="form-check mb-2">
			  <input type="radio" name="duplicate_strategy" value="unik"
					 id="dup-unik" class="form-check-input"
					 <?php echo $savedStrategy === 'unik' ? 'checked' : ''; ?>>
			  <label for="dup-unik" class="form-check-label">
				<?php echo Text::_('COM_COLLECTOR_IMPORT_DUPLICATE_UNIK'); ?>
				<span class="text-muted small">(<?php echo implode(', ', $unikCols); ?>)</span>
			  </label>
			</div>
			<?php endif; ?>

			<div class="form-check mb-3">
			  <input type="radio" name="duplicate_strategy" value="composite"
					 id="dup-composite" class="form-check-input"
					 <?php echo ($savedStrategy === 'composite' || empty($unikCols)) ? 'checked' : ''; ?>>
			  <label for="dup-composite" class="form-check-label">
				<?php echo Text::_('COM_COLLECTOR_IMPORT_DUPLICATE_COMPOSITE'); ?>
			  </label>
			</div>

			<div id="composite-fields-wrap" class="ms-4 <?php echo ($savedStrategy !== 'composite' && !empty($unikCols)) ? 'd-none' : ''; ?>">
			  <p class="text-muted small mb-2">
				<?php echo Text::_('COM_COLLECTOR_IMPORT_DUPLICATE_COMPOSITE_DESC'); ?>
				<?php if (!empty($this->fulltitleCols)): ?>
				  <span class="badge bg-secondary">
					<?php echo Text::_('COM_COLLECTOR_IMPORT_DUPLICATE_FULLTITLE_HINT'); ?>
				  </span>
				<?php endif; ?>
			  </p>
			  <div class="row g-2">
			  <?php foreach ($this->fields as $field):
				  $col   = $field->_field->tablecolumn;
				  $label = $field->_field->field;
				  $isFt  = in_array($col, $this->fulltitleCols, true);
				  $checked = in_array($col, $savedComposite, true);
			  ?>
				<div class="col-md-4 col-lg-3">
				  <div class="form-check <?php echo $isFt ? 'fw-semibold' : ''; ?>">
					<input type="checkbox" name="composite_fields[]" value="<?php echo $col; ?>"
						   id="cf-<?php echo $col; ?>" class="form-check-input"
						   <?php echo $checked ? 'checked' : ''; ?>>
					<label for="cf-<?php echo $col; ?>" class="form-check-label small">
					  <?php echo htmlspecialchars($label, ENT_QUOTES); ?>
					  <?php if ($isFt): ?>
						<span class="text-primary" title="<?php echo Text::_('COM_COLLECTOR_IMPORT_DUPLICATE_FULLTITLE_FIELD'); ?>">★</span>
					  <?php endif; ?>
					</label>
				  </div>
				</div>
			  <?php endforeach; ?>
			  </div>
			</div>

		  </div>
		</div>

		<script>
		document.querySelectorAll('input[name="duplicate_strategy"]').forEach(r => {
			r.addEventListener('change', () => {
				document.getElementById('composite-fields-wrap')
					.classList.toggle('d-none', r.value !== 'composite');
			});
		});
		</script>

      <?php echo HTMLHelper::_('form.token'); ?>
      <div class="mt-3 d-flex gap-2">
        <a href="<?php echo Route::_('index.php?option=com_collector&view=import&collection=' . $collection . '&step=1', false); ?>"
           class="btn btn-outline-secondary btn-sm">
          <span class="icon-arrow-left me-1"></span><?php echo Text::_('JPREV'); ?>
        </a>
        <button type="submit" class="btn btn-primary btn-sm">
          <?php echo Text::_('COM_COLLECTOR_IMPORT_STEP2_NEXT'); ?>
          <span class="icon-arrow-right ms-1"></span>
        </button>
      </div>
    </form>

  </div>
</div>

<?php /* ══════════════════════════════════════════════════════════════════════
   ÉTAPE 3 : Mapping valeurs (champs define uniquement)
══════════════════════════════════════════════════════════════════════════ */ ?>
<?php elseif ($step === 3):
    $savedValueMap = $session['value_mapping'] ?? [];
?>

<div class="card">
  <div class="card-header fw-semibold"><?php echo Text::_('COM_COLLECTOR_IMPORT_STEP3_TITLE'); ?></div>
  <div class="card-body">
    <p class="text-muted small mb-3"><?php echo Text::_('COM_COLLECTOR_IMPORT_DEFINE_MAPPING_HINT'); ?></p>

    <form method="post"
          action="<?php echo Route::_('index.php?option=com_collector&task=import.savevaluemapping&collection=' . $collection, false); ?>">

	<?php
	// Collecter le JS de chaque type de champ, sans doublons
	$importMappingJs = [];
	foreach ($fields as $field) {
		if ($field->getImportMapping() === null) continue;
		$js = $field->getImportMappingJs();
		if ($js !== '') {
			$importMappingJs[get_class($field)] = $js; // clé = classe → pas de doublon
		}
	}
	if (!empty($importMappingJs)): ?>
	<script>
	<?php echo implode("\n", $importMappingJs); ?>
	</script>
	<?php endif; ?>

    <?php foreach ($fields as $field):
		if ($field->getImportMapping() === null) continue;
		$col      = $field->_field->tablecolumn;
		$colEsc   = htmlspecialchars($col, ENT_QUOTES);
		$savedCol = $savedValueMap[$col]
			?? $this->savedConfig['value_mapping'][$col]
			?? [];
		$html = $field->renderImportMapping($savedCol, $this->fileValues[$col] ?? []);
		if ($html === '') continue;
	?>
	<div class="card mb-3">
	  <div class="card-header py-2 fw-semibold small">
		<?php echo htmlspecialchars($field->_field->field, ENT_QUOTES); ?>
	  </div>
	  <div class="card-body py-2">
		<?php echo $html; ?>
	  </div>
	</div>
	<?php endforeach; ?>

      <?php echo HTMLHelper::_('form.token'); ?>
      <div class="d-flex gap-2">
        <a href="<?php echo Route::_('index.php?option=com_collector&view=import&collection=' . $collection . '&step=2', false); ?>"
           class="btn btn-outline-secondary btn-sm">
          <span class="icon-arrow-left me-1"></span><?php echo Text::_('JPREV'); ?>
        </a>
        <button type="submit" class="btn btn-primary btn-sm">
          <?php echo Text::_('COM_COLLECTOR_IMPORT_STEP3_NEXT'); ?>
          <span class="icon-arrow-right ms-1"></span>
        </button>
      </div>
    </form>
  </div>
</div>

<script>
/**
 * Résout le texte saisi dans un input[list=datalist] en ID DB.
 * Met à jour le hidden associé (data-target).
 */
function resolveDbId(input) {
    const col     = input.dataset.col;
    const target  = input.dataset.target;
    const options = (window._dbOptions && window._dbOptions[col]) || [];
    const val     = input.value.trim().toLowerCase();
    const found   = options.find(o => o.content.toLowerCase() === val);

    const hiddenInput = document.getElementById(target);
    if (hiddenInput) {
        hiddenInput.value = found ? found.id : '';
    }

    if (found) {
        // Extraire l'index de la paire depuis l'id du target (ex: "db-id-fabricant-2")
        const rowId   = target.replace('db-id-', '');
        const chkId   = 'create-chk-' + rowId;
        const labelId = 'create-label-' + rowId;
        const chk     = document.getElementById(chkId);
        const label   = document.getElementById(labelId);

        if (chk) {
            chk.checked = false;
            // Déclencher le change pour masquer le champ label
            chk.dispatchEvent(new Event('change'));
        }
        if (label) {
            label.value = '';
            // Mettre à jour le hidden correspondant
            const hiddenCreate = label.previousElementSibling;
            if (hiddenCreate && hiddenCreate.type === 'hidden') {
                hiddenCreate.value = '';
            }
        }
    }
}
</script>

<?php /* ══════════════════════════════════════════════════════════════════════
   ÉTAPE 4 : Infos de publication
══════════════════════════════════════════════════════════════════════════ */ ?>
<?php elseif ($step === 4): ?>

<form method="post"
      action="<?php echo Route::_('index.php?option=com_collector&task=import.savepublishing&collection=' . $collection, false); ?>">

    <div class="card mb-3">
        <div class="card-header">
            <?php echo Text::_('COM_COLLECTOR_IMPORT_STEP4_TITLE'); ?>
        </div>
        <div class="card-body">
            <p class="text-muted"><?php echo Text::_('COM_COLLECTOR_IMPORT_STEP4_DESC'); ?></p>

            <?php foreach (['state', 'access', 'publish_up', 'publish_down'] as $fieldName): ?>
                <?php echo $this->publishingForm->renderField($fieldName); ?>
            <?php endforeach; ?>
        </div>
    </div>

    <div class="d-flex gap-2">
        <a href="<?php echo Route::_('index.php?option=com_collector&view=import&collection=' . $collection . '&step=3', false); ?>"
           class="btn btn-outline-secondary btn-sm">
          <span class="icon-arrow-left me-1"></span><?php echo Text::_('JPREV'); ?>
        </a>
		<a href="<?php echo Route::_('index.php?option=com_collector&task=import.startdryrun&collection=' . $collection . '&' . Session::getFormToken() . '=1', false); ?>"
		   class="btn btn-outline-secondary btn-sm">
			<span class="icon-eye me-1"></span>
			<?php echo Text::_('COM_COLLECTOR_IMPORT_DRY_RUN_BTN'); ?>
		</a>
        <button type="submit" class="btn btn-primary btn-sm">
          <?php echo Text::_('COM_COLLECTOR_IMPORT_STEP4_NEXT'); ?>
          <span class="icon-arrow-right ms-1"></span>
        </button>
     </div>

    <?php echo HTMLHelper::_('form.token'); ?>
</form>

<?php /* ÉTAPE 5 : Import AJAX + doublons
══════════════════════════════════════════════════════════════════════════ */ ?>
<?php else:
	$totalRows = (int)($session['total_rows'] ?? 0);
	$firstLine = (int)($session['first_line'] ?? 2);
	$dataRows  = max(0, $totalRows - $firstLine + 1);
	$startRow  = (int)($session['current_row'] ?: ($session['first_line'] ?? 2));
    $imported  = (int)($session['imported'] ?? 0);
    $skipped   = (int)($session['skipped']  ?? 0);
    $merged    = (int)($session['merged']   ?? 0);
	$errors    = (int)($session['errors']   ?? 0);
    // Transmettre les champs au JS pour l'affichage des conflits
    $fieldsJs  = json_encode(array_map(
        fn($f) => ['col' => $f->_field->tablecolumn, 'label' => $f->_field->field],
        $fields
    ), JSON_HEX_APOS | JSON_HEX_QUOT);
?>

<div class="card">
  <div class="card-header d-flex justify-content-between align-items-center">
    <span class="fw-semibold" id="import-title">
      <?php echo Text::_('COM_COLLECTOR_IMPORT_STEP5_TITLE'); ?>
    </span>
	<?php if (!empty($session['dry_run'])): ?>
	<span class="badge bg-warning text-dark ms-2">
		<span class="icon-eye me-1"></span>
		<?php echo Text::_('COM_COLLECTOR_IMPORT_DRY_RUN_BADGE'); ?>
	</span>
	<?php endif; ?>
    <div class="d-flex gap-2 align-items-center">
      <small class="text-muted" id="row-indicator"></small>
      <button type="button" id="btn-pause" class="btn btn-sm btn-warning">
        <span class="icon-pause me-1"></span><?php echo Text::_('COM_COLLECTOR_IMPORT_PAUSE_BTN'); ?>
      </button>
    </div>
  </div>

  <div class="card-body">

    <!-- Compteurs -->
    <div class="d-flex gap-4 mb-3 small">
	  <span class="text-success fw-semibold">
		<span id="cnt-imported"><?php echo $imported; ?></span>
		<?php echo Text::_('COM_COLLECTOR_IMPORT_CNT_IMPORTED'); ?>
	  </span>
	  <span class="text-primary fw-semibold<?php echo !empty($session['dry_run']) ? ' d-none' : ''; ?>">
		<span id="cnt-merged"><?php echo $merged; ?></span>
		<?php echo Text::_('COM_COLLECTOR_IMPORT_CNT_MERGED'); ?>
	  </span>
	  <span class="text-muted fw-semibold">
		<span id="cnt-skipped"><?php echo $skipped; ?></span>
		<?php echo Text::_('COM_COLLECTOR_IMPORT_CNT_SKIPPED'); ?>
	  </span>
	  <?php if (!empty($session['dry_run'])): ?>
      <span class="text-warning fw-semibold d-none" id="cnt-dry-conflicts-wrap">
          <span id="cnt-dry-conflicts">0</span>
          <?php echo Text::_('COM_COLLECTOR_IMPORT_CNT_DRY_CONFLICTS'); ?>
      </span>
      <span class="text-danger fw-semibold d-none" id="cnt-dry-multiconflicts-wrap">
          <span id="cnt-dry-multiconflicts">0</span>
          <?php echo Text::_('COM_COLLECTOR_IMPORT_CNT_DRY_MULTICONFLICTS'); ?>
      </span>
      <?php endif; ?>
	  <span class="text-danger fw-semibold<?php echo ($errors ?? 0) === 0 ? ' d-none' : ''; ?>"
			id="cnt-errors-wrap">
		<span id="cnt-errors"><?php echo $errors ?? 0; ?></span>
		<?php echo Text::_('COM_COLLECTOR_IMPORT_CNT_ERRORS'); ?>
	  </span>
	</div>

    <!-- Barre de progression -->
    <div class="progress mb-3" style="height:18px">
      <div id="progress-bar" class="progress-bar progress-bar-striped progress-bar-animated"
           role="progressbar" style="width:0%">0%</div>
    </div>

    <!-- Log compact -->
    <div id="progress-log" class="progress-log bg-light rounded p-2 font-monospace text-muted mb-3"></div>

    <!-- Panneau conflit (masqué par défaut) -->
    <div id="conflict-panel" class="d-none">
      <div class="alert alert-warning d-flex gap-2 mb-2">
        <span class="icon-warning fs-5 mt-1"></span>
        <div>
          <strong id="conflict-title"></strong><br>
          <small class="text-muted" id="conflict-subtitle"></small>
        </div>
      </div>
      <div class="table-responsive mb-3">
        <table class="table table-sm table-bordered">
          <thead class="table-light">
			<tr>
			  <th><?php echo Text::_('COM_COLLECTOR_FIELDS'); ?></th>
			  <th>
				<span class="icon-database me-1"></span>
				<?php echo Text::_('COM_COLLECTOR_IMPORT_CONFLICT_EXISTING'); ?>
			  </th>
			  <th>
				<span class="icon-upload me-1"></span>
				<?php echo Text::_('COM_COLLECTOR_IMPORT_CONFLICT_IMPORTED'); ?>
			  </th>
			</tr>
		  </thead>
          <tbody id="conflict-tbody"></tbody>
        </table>
      </div>
      <div class="d-flex gap-2 flex-wrap">
        <button type="button" class="btn btn-outline-secondary btn-sm" onclick="resolveConflict('skip')">
          <span class="icon-minus me-1"></span><?php echo Text::_('COM_COLLECTOR_IMPORT_CONFLICT_SKIP'); ?>
        </button>
        <button type="button" class="btn btn-primary btn-sm" onclick="resolveConflict('merge')">
          <span class="icon-git-merge me-1"></span><?php echo Text::_('COM_COLLECTOR_IMPORT_CONFLICT_MERGE'); ?>
        </button>
        <button type="button" class="btn btn-success btn-sm" onclick="resolveConflict('import')">
          <span class="icon-plus me-1"></span><?php echo Text::_('COM_COLLECTOR_IMPORT_CONFLICT_IMPORT'); ?>
        </button>
      </div>
    </div>

  </div>

  <!-- Pied : récapitulatif final (masqué jusqu'à la fin) -->
  <div class="card-footer d-none" id="summary-footer">
    <div class="row align-items-center g-2">
      <div class="col">
        <strong><?php echo Text::_('COM_COLLECTOR_IMPORT_SUMMARY'); ?></strong> —
        <span id="summary-text"></span>
      </div>
      <div class="col-auto">
        <a href="<?php echo Route::_('index.php?option=com_collector&view=items&collection=' . $collection, false); ?>"
           class="btn btn-primary btn-sm">
          <span class="icon-list me-1"></span><?php echo Text::_('COM_COLLECTOR_IMPORT_BACK_TO_LIST'); ?>
        </a>
      </div>
    </div>
  </div>
</div>

<script>
(function () {
'use strict';

const TOKEN      = <?php echo json_encode($token); ?>;
const COLLECTION = <?php echo $collection; ?>;
const TOTAL_ROWS = <?php echo $totalRows; ?>;
const FIRST_LINE = <?php echo $firstLine; ?>;
const DATA_ROWS  = <?php echo $dataRows; ?>;
const FIELDS     = <?php echo $fieldsJs; ?>;
const FULLTITLE_COLS = <?php echo json_encode($this->fulltitleCols ?? []); ?>;
const COMPOSITE_COLS = <?php echo json_encode($session['composite_fields'] ?? []); ?>;

const T = {
    paused      : <?php echo json_encode(Text::_('COM_COLLECTOR_IMPORT_PAUSED')); ?>,
    done        : <?php echo json_encode(Text::_('COM_COLLECTOR_IMPORT_DONE')); ?>,
    error       : <?php echo json_encode(Text::_('JERROR_AN_ERROR_HAS_OCCURRED')); ?>,
    identical   : <?php echo json_encode(Text::_('COM_COLLECTOR_IMPORT_CONFLICT_IDENTICAL')); ?>,
    similar     : <?php echo json_encode(Text::_('COM_COLLECTOR_IMPORT_CONFLICT_SIMILAR')); ?>,
    conflictSub : <?php echo json_encode(Text::_('COM_COLLECTOR_IMPORT_CONFLICT_SUBTITLE')); ?>,
    summaryTpl  : <?php echo json_encode(Text::_('COM_COLLECTOR_IMPORT_SUMMARY_TPL')); ?>,
	multiConflict    : <?php echo json_encode(Text::_('COM_COLLECTOR_IMPORT_MULTI_CONFLICT')); ?>,
	multiConflictSub : <?php echo json_encode(Text::_('COM_COLLECTOR_IMPORT_MULTI_CONFLICT_SUB')); ?>,
	selectItem       : <?php echo json_encode(Text::_('COM_COLLECTOR_IMPORT_SELECT_ITEM')); ?>,
    errors           : <?php echo json_encode(Text::_('COM_COLLECTOR_IMPORT_CNT_ERRORS')); ?>,
	dryRunTitle              : <?php echo json_encode(Text::_('COM_COLLECTOR_IMPORT_DRY_RUN_TITLE')); ?>,
    dryRunWouldImport        : <?php echo json_encode(Text::_('COM_COLLECTOR_IMPORT_DRY_RUN_WOULD_IMPORT')); ?>,
    dryRunWouldSkip          : <?php echo json_encode(Text::_('COM_COLLECTOR_IMPORT_DRY_RUN_WOULD_SKIP')); ?>,
    dryRunWouldConflict      : <?php echo json_encode(Text::_('COM_COLLECTOR_IMPORT_DRY_RUN_WOULD_CONFLICT')); ?>,
    dryRunWouldMultiConflict : <?php echo json_encode(Text::_('COM_COLLECTOR_IMPORT_DRY_RUN_WOULD_MULTI_CONFLICT')); ?>,
    dryRunConflictsTitle     : <?php echo json_encode(Text::_('COM_COLLECTOR_IMPORT_DRY_RUN_CONFLICTS_TITLE')); ?>,
    dryRunMultiConflictsTitle: <?php echo json_encode(Text::_('COM_COLLECTOR_IMPORT_DRY_RUN_MULTI_CONFLICTS_TITLE')); ?>,
    dryRunRow                : <?php echo json_encode(Text::_('COM_COLLECTOR_IMPORT_DRY_RUN_ROW')); ?>,
    dryRunImportData         : <?php echo json_encode(Text::_('COM_COLLECTOR_IMPORT_DRY_RUN_IMPORT_DATA')); ?>,
    dryRunExistingData       : <?php echo json_encode(Text::_('COM_COLLECTOR_IMPORT_DRY_RUN_EXISTING_DATA')); ?>,
    dryRunMatchCount         : <?php echo json_encode(Text::_('COM_COLLECTOR_IMPORT_DRY_RUN_MATCH_COUNT')); ?>,
	dryRunExistingId         : <?php echo json_encode(Text::_('COM_COLLECTOR_IMPORT_DRY_RUN_EXISTING_ID')); ?>,
	dryRunLaunchBtn : <?php echo json_encode(Text::_('COM_COLLECTOR_IMPORT_DRY_RUN_LAUNCH_BTN')); ?>,
	dryRunLaunchUrl : <?php echo json_encode(
		Route::_('index.php?option=com_collector&task=import.launchfromdryrun&collection=' . $collection . '&' . Session::getFormToken() . '=1', false)); ?>,
	fields          : <?php echo json_encode(Text::_('COM_COLLECTOR_FIELDS')); ?>,
	conflictExisting: <?php echo json_encode(Text::_('COM_COLLECTOR_IMPORT_CONFLICT_EXISTING')); ?>,
	conflictImported: <?php echo json_encode(Text::_('COM_COLLECTOR_IMPORT_CONFLICT_IMPORTED')); ?>,
	dryRunWarning : <?php echo json_encode(Text::_('COM_COLLECTOR_IMPORT_DRY_RUN_WARNING')); ?>,
	dryRunIntraFile : <?php echo json_encode(Text::_('COM_COLLECTOR_IMPORT_DRY_RUN_INTRA_FILE')); ?>,
};

let state = {
    currentRow : <?php echo $startRow; ?>,
    imported   : <?php echo $imported; ?>,
    skipped    : <?php echo $skipped; ?>,
    merged     : <?php echo $merged; ?>,
    conflict   : null,
};

let isPaused  = false;
let isRunning = false;

// Démarrage automatique
window.addEventListener('DOMContentLoaded', function () {
    if (TOTAL_ROWS > 0 && state.currentRow <= TOTAL_ROWS) {
        isRunning = true;
        runImport();
    } else {
        finishImport(false);
    }
});

/* ── Boucle d'import ─────────────────────────────────────────────────────── */
async function runImport() {
    if (isPaused || !isRunning || state.conflict) return;

    const fd = buildFd();
    fd.append('row', state.currentRow);

    const json = await postJson('index.php?option=com_collector&task=import.insert&format=raw&collection=' + COLLECTION, fd);
    if (!json) { finishImport(true); return; }
    if (!json.success) { appendLog(json.message, 'text-danger'); finishImport(true); return; }

    applyCounters(json);

	if (json.state === 'multiconflict') {
		state.conflict   = json;
		state.currentRow = json.conflictRow ?? state.currentRow;
		applyCounters(json);
		updateBar();
		showMultiConflict(json);
		return;
	}
	
    if (json.state === 'conflict') {
		state.conflict   = json;
		state.currentRow = json.conflictRow ?? state.currentRow;
		applyCounters(json);
		updateBar();
		showConflict(json);
		return;
	}

    state.currentRow = json.next || (state.currentRow + 10);
    updateBar();

	state.dryRunReport = state.dryRunReport || [];
	state.isDryRun     = json.dry_run || false;
	if (json.dry_run_entries) {
		state.dryRunReport.push(...json.dry_run_entries);
	}

    if (json.state === 'end') { finishImport(false); return; }
    setTimeout(runImport, 0);
}

/* ── Conflit ─────────────────────────────────────────────────────────────── */
function showConflict(json) {
    state.conflict = json;
	
	// Toujours rétablir les 3 colonnes standard, quelle que soit l'origine
    const thead = document.querySelector('#conflict-panel table thead tr');
    thead.innerHTML = '<th>' + esc(T.fields) + '</th>'
        + '<th><span class="icon-database me-1"></span>'
        + T.conflictExisting
        + ' <span class="badge bg-secondary ms-1">#' + json.existingId + '</span>'
        + '</th>'
        + '<th><span class="icon-upload me-1"></span>' + T.conflictImported + '</th>';
		
    const titleTpl = json.isIdentical ? T.identical : T.similar;
	document.getElementById('conflict-title').textContent = titleTpl.replace('%s', json.conflictRow);
    document.getElementById('conflict-subtitle').textContent = T.conflictSub.replace('%s', json.conflictRow);

    const tbody = document.getElementById('conflict-tbody');
    tbody.innerHTML = '';

    FIELDS.forEach(({col, label}) => {
        const existing = String(json.existingDisplay[col] ?? '');
        const imported = String(json.importDisplay[col]  ?? '');
        const differs  = (existing !== imported);
        const tr = document.createElement('tr');

        if (!differs) {
            tr.innerHTML = `<td class="small fw-semibold text-muted">${esc(label)}</td>
                            <td colspan="2" class="text-muted small">${esc(existing)}</td>`;
            tbody.appendChild(tr);
            return;
        }

        // Sélection par défaut :
        // - valeur fichier non vide → on préfère le fichier (import)
        // - valeur fichier vide     → on conserve l'existant (existing)
        const defaultChoice = imported !== '' ? 'import' : 'existing';

        tr.classList.add('conflict-changed');
        tr.dataset.col    = col;
        tr.dataset.choice = defaultChoice;

        const tdLabel    = document.createElement('td');
        tdLabel.className = 'small fw-semibold text-muted';
        tdLabel.textContent = label;

        const tdExisting = document.createElement('td');
        tdExisting.className = 'choice small' + (defaultChoice === 'existing' ? ' selected-existing' : '');
        tdExisting.dataset.value = 'existing';
        tdExisting.textContent   = existing || '—';

        const tdImport = document.createElement('td');
        tdImport.className = 'choice small' + (defaultChoice === 'import' ? ' selected-import' : '');
        tdImport.dataset.value = 'import';
        tdImport.textContent   = imported || '—';

        // Clic sur une cellule → bascule la sélection
        [tdExisting, tdImport].forEach(td => {
            td.addEventListener('click', () => {
                tr.dataset.choice = td.dataset.value;
                tdExisting.classList.toggle('selected-existing', td.dataset.value === 'existing');
                tdExisting.classList.remove('selected-import');
                tdImport.classList.toggle('selected-import',   td.dataset.value === 'import');
                tdImport.classList.remove('selected-existing');
            });
        });

        tr.appendChild(tdLabel);
        tr.appendChild(tdExisting);
        tr.appendChild(tdImport);
        tbody.appendChild(tr);
    });

    document.getElementById('conflict-panel').classList.remove('d-none');
    document.getElementById('btn-pause').disabled = true;
}

function showMultiConflict(json) {
    state.conflict = json;
    document.getElementById('conflict-title').textContent =
        T.multiConflict.replace('%s', json.conflictRow);
    document.getElementById('conflict-subtitle').textContent =
        T.multiConflictSub.replace('%d', json.matches.length);

    const compositeColNames = json.compositeCols ?? [];

	// Union des champs composite + fulltitle, dans l'ordre de FIELDS
	const displayColNames = [...new Set([...compositeColNames, ...FULLTITLE_COLS])];

	const DISPLAY_FIELDS = displayColNames.length > 0
		? FIELDS.filter(({col}) => displayColNames.includes(col))
		: FIELDS.slice(0, 6); // fallback

    // Remplacer les en-têtes du tableau
    const thead = document.querySelector('#conflict-panel table thead tr');
    thead.innerHTML = '<th>#</th>'
        + DISPLAY_FIELDS.map(({label}) => '<th class="small">' + esc(label) + '</th>').join('')
        + '<th></th>';

    const tbody = document.getElementById('conflict-tbody');
    tbody.innerHTML = '';

    json.matches.forEach((match, idx) => {
		const isIdentical = match.isIdentical === true;

		const tr = document.createElement('tr');
		tr.style.cursor = 'pointer';

		// Colonne #
		const tdIdx = document.createElement('td');
		tdIdx.className = 'small fw-semibold text-muted align-middle';
		tdIdx.innerHTML = '#' + (idx + 1)
			+ ' <span class="badge bg-secondary">#' + match.id + '</span>'
			+ '<br>'
			+ '<span class="badge ' + (isIdentical ? 'badge-identical' : 'badge-similar') + '">'
			+ (isIdentical ? '✓ identique' : '≈ similaire')
			+ '</span>';
		tr.appendChild(tdIdx);

		// Colonnes champs
		DISPLAY_FIELDS.forEach(({col}) => {
			const val      = match.display[col] ?? '';
			const importVal = json.importDisplay[col] ?? '';
			const differs  = String(val) !== String(importVal);
			const td = document.createElement('td');
			td.className = 'small align-middle';
			td.style.background = differs
				? 'rgba(220, 53, 69, .10)'
				: 'rgba(25, 135, 84, .10)';
			td.textContent = val || '—';
			tr.appendChild(td);
		});

		// Bouton Choisir (masqué si identique)
		const tdBtn = document.createElement('td');
		tdBtn.className = 'align-middle';
		if (!isIdentical) {
			const btn = document.createElement('button');
			btn.className = 'btn btn-sm btn-outline-primary px-3';
			btn.textContent = T.selectItem;
			tdBtn.appendChild(btn);
		} else {
			// Identique → proposer directement ignorer ou fusionner sans comparaison
			tdBtn.innerHTML = '<span class="text-success small">'
				+ '<span class="icon-check me-1"></span>Déjà en base</span>';
		}
		tr.appendChild(tdBtn);

		tr.addEventListener('click', () => {
			showConflict(Object.assign({}, json, {
				state           : 'conflict',
				multipleMatches : false,
				isIdentical     : isIdentical,
				existingId      : match.id,
				existingDisplay : match.display,
			}));
		});

		tbody.appendChild(tr);
	});

    document.getElementById('conflict-panel').classList.remove('d-none');
    document.getElementById('btn-pause').disabled = true;
}
window.showMultiConflict = showMultiConflict;

async function resolveConflict(decision) {
    if (!state.conflict) return;
    const json = state.conflict;

    const fd = buildFd();
    fd.append('decision', decision);
    fd.append('existingId', json.existingId);

    if (decision === 'merge') {
		document.querySelectorAll('#conflict-tbody tr[data-col]').forEach(tr => {
			if (tr.dataset.choice === 'import') {
				const col = tr.dataset.col;
				fd.append('importData[' + col + ']', json.importData[col] ?? '');
			}
			// 'existing' → non envoyé → mergeItem conserve la valeur existante
		});
		fd.append('importData[collection]', COLLECTION);
	} else {
        // Skip ou import complet : envoyer toutes les données
        Object.entries(json.importData).forEach(([k, v]) => fd.append('importData[' + k + ']', v));
    }

    const resp = await postJson(
        'index.php?option=com_collector&task=import.resolve&format=raw&collection=' + COLLECTION, fd
    );

    document.getElementById('conflict-panel').classList.add('d-none');
    document.getElementById('btn-pause').disabled = false;
    state.conflict = null;

    if (!resp?.success) { appendLog(resp?.message || T.error, 'text-danger'); return; }

    applyCounters(resp);
    state.currentRow = resp.next || (state.currentRow + 1);
    updateBar();

    if (resp.state === 'end') { finishImport(false); return; }
    setTimeout(runImport, 0);
}
window.resolveConflict = resolveConflict;

/* ── Pause ───────────────────────────────────────────────────────────────── */
document.getElementById('btn-pause').addEventListener('click', function () {
    if (!isRunning) return;
    isPaused = true;
    this.disabled = true;
    appendLog(T.paused, 'text-warning');
});

/* ── Fin d'import ────────────────────────────────────────────────────────── */
function finishImport(hasError) {
    isRunning = false;
    const bar = document.getElementById('progress-bar');
    bar.style.width = '100%'; bar.textContent = '100%';
    bar.classList.remove('progress-bar-animated', 'progress-bar-striped');
    if (hasError) bar.classList.add('bg-danger');

    document.getElementById('import-title').textContent = hasError ? T.error : T.done;
    document.getElementById('btn-pause').style.display = 'none';

    let summary = T.summaryTpl
    .replace('%1$d', state.imported)
    .replace('%2$d', state.merged)
    .replace('%3$d', state.skipped);

	if (state.isDryRun) {
		const c  = parseInt(document.getElementById('cnt-dry-conflicts')?.textContent)      || 0;
		const mc = parseInt(document.getElementById('cnt-dry-multiconflicts')?.textContent) || 0;
		if (c  > 0) summary += ' — ' + c  + ' ' + T.dryRunWouldConflict;
		if (mc > 0) summary += ' — ' + mc + ' ' + T.dryRunWouldMultiConflict;
	}

	const errCount = parseInt(document.getElementById('cnt-errors')?.textContent) || 0;
	if (errCount > 0) {
		summary += ', ' + errCount + ' ' + T.errors;
	}
	document.getElementById('summary-text').textContent = summary;
    document.getElementById('summary-footer').classList.remove('d-none');
	
	if (state.isDryRun) {
		const log = document.getElementById('progress-log');
        if (log) log.classList.add('d-none');
        renderDryRunReport();
    }
}

function renderDryRunReport() {
    const report         = state.dryRunReport || [];
    const conflicts      = report.filter(e => e.action === 'conflict');
    const multiconflicts = report.filter(e => e.action === 'multiconflict');
    const imports        = report.filter(e => e.action === 'import');
    const skipped        = report.filter(e => e.action === 'skip_identical');

    // Champs à afficher : union composite + fulltitle
    const displayCols = [...new Set([...COMPOSITE_COLS, ...FULLTITLE_COLS])]
        .map(col => FIELDS.find(f => f.col === col))
        .filter(Boolean);

    let html = '<div class="px-3 py-3 border-top">';
    html += '<h6 class="fw-semibold mb-3">' + T.dryRunTitle + '</h6>';

    // Résumé
    html += '<ul class="list-unstyled mb-3 small">';
    html += '<li class="text-success">✓ ' + imports.length + ' ' + T.dryRunWouldImport + '</li>';
    html += '<li class="text-muted">— ' + skipped.length + ' ' + T.dryRunWouldSkip + '</li>';
    if (conflicts.length > 0)
        html += '<li class="text-warning">⚠ ' + conflicts.length + ' ' + T.dryRunWouldConflict + '</li>';
    if (multiconflicts.length > 0)
        html += '<li class="text-danger">⚠ ' + multiconflicts.length + ' ' + T.dryRunWouldMultiConflict + '</li>';
    html += '</ul>';

    // Accordéon conflits simples
    if (conflicts.length > 0) {
		html += buildAccordion('dry-conflicts',
			T.dryRunConflictsTitle + ' <span class="badge bg-warning text-dark ms-1">' + conflicts.length + '</span>',
			'<div class="px-3 pt-2 pb-1 small text-muted">'
			+ '<span class="me-3" style="background:rgba(25,135,84,.15);padding:1px 6px">■</span> Identique &nbsp;'
			+ '<span style="background:rgba(220,53,69,.15);padding:1px 6px">■</span> Différent'
			+ '</div>'
			+ buildConflictTable(conflicts, displayCols, false));
	}

    // Accordéon multiconflits
    if (multiconflicts.length > 0) {
		html += buildAccordion('dry-multiconflicts',
			T.dryRunMultiConflictsTitle + ' <span class="badge bg-danger ms-1">' + multiconflicts.length + '</span>',
			'<div class="px-3 pt-2 pb-1 small text-muted">'
			+ '<span class="me-3" style="background:rgba(25,135,84,.15);padding:1px 6px">■</span> Identique &nbsp;'
			+ '<span style="background:rgba(220,53,69,.15);padding:1px 6px">■</span> Différent'
			+ '</div>'
			+ buildConflictTable(multiconflicts, displayCols, true));
	}
	
	// Note d'avertissement
	html += '<div class="alert alert-info small mt-3 mb-0 d-flex align-items-start gap-2">';
	html += '<span class="icon-info-circle" style="font-size:18px;flex-shrink:0;margin-top:1px" aria-hidden="true"></span>';
	html += '<span>' + T.dryRunWarning + '</span>';
	html += '</div>';

	// Bouton lancer l'import réel
	html += '<div class="mt-3 pt-3 border-top">';
	html += '<a href="' + T.dryRunLaunchUrl + '" class="btn btn-primary">';
	html += '<span class="icon-upload me-1"></span>' + T.dryRunLaunchBtn;
	html += '</a>';
	html += '</div>';

    html += '</div>';

    const footer = document.getElementById('summary-footer');
	if (footer) footer.insertAdjacentHTML('beforebegin', html);
}

function buildAccordion(id, titleHtml, content) {
    return `
    <div class="accordion mb-3" id="acc-${id}">
      <div class="accordion-item">
        <h2 class="accordion-header">
          <button class="accordion-button collapsed py-2 small fw-semibold"
                  type="button" data-bs-toggle="collapse"
                  data-bs-target="#acc-body-${id}">
            ${titleHtml}
          </button>
        </h2>
        <div id="acc-body-${id}" class="accordion-collapse collapse">
          <div class="accordion-body p-0">${content}</div>
        </div>
      </div>
    </div>`;
}

function buildConflictTable(entries, displayCols, isMulti) {
    let html = '<table class="table table-sm table-bordered mb-0">';
    html += '<thead class="table-light"><tr>';
    html += '<th class="small">' + T.dryRunRow + '</th>';
    displayCols.forEach(f => {
        html += '<th class="small">' + esc(f.label) + '</th>';
    });
	html += '<th class="small">' + T.dryRunExistingId + '</th>';
    html += '</tr></thead><tbody>';

    entries.forEach(e => {
        const dataRow = e.row - (FIRST_LINE - 1);

        if (isMulti) {
            // Ligne d'import
            html += '<tr class="table-light">';
            html += '<td class="small fw-semibold align-middle" rowspan="' + (e.matches.length + 1) + '">' + dataRow + '</td>';
            displayCols.forEach(f => {
                html += '<td class="small fw-semibold">' + esc(e.importDisplay[f.col] ?? '—') + '</td>';
            });
			html += '<td></td>';
            html += '</tr>';
            // Lignes des matchs
            e.matches.forEach(match => {
                html += '<tr>';
                displayCols.forEach(f => {
                    const imp = String(e.importDisplay[f.col] ?? '');
                    const ex  = String(match.display[f.col]  ?? '');
                    const differs = imp !== ex;
                    const bg = differs ? 'rgba(220,53,69,.10)' : 'rgba(25,135,84,.10)';
                    html += '<td class="small" style="background:' + bg + '">'
                         + esc(ex || '—') + '</td>';
                });
				html += '<td class="small align-middle">';
				html += '<a href="index.php?option=com_collector&task=item.edit&collection=' + COLLECTION + '&id=' + match.id + '" target="_blank" class="badge bg-secondary text-decoration-none">';
				html += '#' + match.id + ' <span class="icon-out-2"></span></a>';
				html += '</td>';
                html += '</tr>';
            });
        } else {
			// Conflit simple : ligne import + ligne existant
			html += '<tr class="table-light">';
			html += '<td class="small fw-semibold align-middle" rowspan="2">' + dataRow + '</td>';
			displayCols.forEach(f => {
				html += '<td class="small fw-semibold">'
					 + esc((e.importDisplay ?? {})[f.col] ?? '—') + '</td>';
			});
			html += '</td><td>';
			html += '</tr><tr>';
			displayCols.forEach(f => {
				const imp     = String((e.importDisplay   ?? {})[f.col] ?? '');
				const ex      = String((e.existingDisplay ?? {})[f.col] ?? '');  // ← protection
				const differs = imp !== ex;
				const bg      = differs ? 'rgba(220,53,69,.10)' : 'rgba(25,135,84,.10)';
				html += '<td class="small" style="background:' + bg + '">'
					 + esc(ex || '—') + '</td>';
			});
			html += '<td class="small align-middle">';
			if (e.intraFile) {
				html += '<span class="badge bg-info">'
					 + T.dryRunIntraFile.replace('%d', (e.existingRow ?? 0) - (FIRST_LINE - 1))
					 + '</span>';
			} else if (e.existingId) {
				html += '<a href="index.php?option=com_collector&task=item.edit&collection='
					 + COLLECTION + '&id=' + e.existingId
					 + '" target="_blank" class="badge bg-secondary text-decoration-none">'
					 + '#' + e.existingId
					 + ' <span class="icon-out-2"></span></a>';
			}
			html += '</td>';
			html += '</tr>';
		}
    });

    html += '</tbody></table>';
    return html;
}

/* ── Utilitaires ─────────────────────────────────────────────────────────── */
function buildFd() {
    const fd = new FormData();
    fd.append(TOKEN, '1');
    fd.append('imported', state.imported);
    fd.append('skipped',  state.skipped);
    fd.append('merged',   state.merged);
    return fd;
}

function applyCounters(resp) {
    if (resp.imported !== undefined && resp.imported !== null) state.imported = resp.imported;
    if (resp.merged   !== undefined && resp.merged   !== null) state.merged   = resp.merged;
    if (resp.skipped  !== undefined && resp.skipped  !== null) state.skipped  = resp.skipped;
	
	if (resp.dry_run_conflicts !== undefined) {
		const c   = resp.dry_run_conflicts;
		const el  = document.getElementById('cnt-dry-conflicts');
		const wrap = document.getElementById('cnt-dry-conflicts-wrap');
		if (el)   el.textContent = c;
		if (wrap) wrap.classList.toggle('d-none', c === 0);
	}
	if (resp.dry_run_multiconflicts !== undefined) {
		const m    = resp.dry_run_multiconflicts;
		const el   = document.getElementById('cnt-dry-multiconflicts');
		const wrap = document.getElementById('cnt-dry-multiconflicts-wrap');
		if (el)   el.textContent = m;
		if (wrap) wrap.classList.toggle('d-none', m === 0);
	}

    document.getElementById('cnt-imported').textContent = state.imported;
    document.getElementById('cnt-merged').textContent   = state.merged;
    document.getElementById('cnt-skipped').textContent  = state.skipped;

    const errCount = resp.errors ?? (parseInt(document.getElementById('cnt-errors').textContent) || 0);
    document.getElementById('cnt-errors').textContent = errCount;
    document.getElementById('cnt-errors-wrap').classList.toggle('d-none', errCount === 0);
}

function updateBar() {
    if (!TOTAL_ROWS) return;
    const done = state.imported + state.skipped + state.merged;
    const pct = Math.min(100, Math.round(done / DATA_ROWS * 100));
    const bar  = document.getElementById('progress-bar');
    bar.style.width = pct + '%';
    bar.textContent = pct + '%';
	const dataRow = Math.min(state.currentRow - FIRST_LINE + 1, DATA_ROWS);
	document.getElementById('row-indicator').textContent = 'Ligne ' + Math.max(1, dataRow) + ' / ' + DATA_ROWS;
}

function appendLog(msg, cls) {
    const el = document.createElement('div');
    el.textContent = msg;
    if (cls) el.className = cls;
    const log = document.getElementById('progress-log');
    log.appendChild(el);
    log.scrollTop = log.scrollHeight;
}

async function postJson(url, fd) {
    try {
        const resp = await fetch(url, {
            method  : 'POST',
            body    : fd,
            headers : {'X-Requested-With': 'XMLHttpRequest'},
        });
        if (!resp.ok) throw new Error('HTTP ' + resp.status);
        const text = await resp.text();
        // Vérifier qu'on a bien du JSON (pas du HTML Joomla)
        const trimmed = text.trimStart();
        if (!trimmed.startsWith('{') && !trimmed.startsWith('[')) {
            throw new Error('Réponse non-JSON. Voir les logs serveur.');
        }
        return JSON.parse(trimmed);
    } catch (e) {
        appendLog(e.message, 'text-danger');
        return null;
    }
}

function esc(s) {
    return String(s)
        .replace(/&/g,'&amp;').replace(/</g,'&lt;')
        .replace(/>/g,'&gt;').replace(/"/g,'&quot;');
}

})();
</script>

<?php endif; ?>