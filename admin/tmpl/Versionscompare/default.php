<?php
defined('_JEXEC') or die('Restricted access');
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Router\Route;
use Joomla\CMS\Uri\Uri;

HTMLHelper::_('bootstrap.modal');
HTMLHelper::_('bootstrap.tooltip');

$dateFormat   = Text::_('DATE_FORMAT_LC6');
$changedCount = count(array_filter($this->comparison ?: [], fn($r) => $r['changed']));
$totalCount   = count($this->comparison ?: []);
$meta1Date    = $this->meta1 ? HTMLHelper::_('date', $this->meta1->modified, $dateFormat) : '#' . $this->v1;
$meta2Date    = $this->meta2 ? HTMLHelper::_('date', $this->meta2->modified, $dateFormat) : '#' . $this->v2;
$meta1Auth    = $this->meta1->author_name ?? '';
$meta2Auth    = $this->meta2->author_name ?? '';
$formAction   = 'index.php?option=com_collector&task=versionscompare.validate';

switch ($this->meta1->state) {
	case 1:  $badgeV1 = '<span class="text-success" data-bs-toggle="tooltip" title="'.Text::_("COM_COLLECTOR_ITEM_HISTORY_PUBLISHED").'"><span class="icon-publish" aria-hidden="true"></span></span>'; break;
	case 2:  $badgeV1 = '<span class="text-muted"   data-bs-toggle="tooltip" title="'.Text::_("COM_COLLECTOR_ITEM_HISTORY_ARCHIVED" ).'"><span class="icon-archive" aria-hidden="true"></span></span>'; break;
	default: $badgeV1 = '<span class="text-warning" data-bs-toggle="tooltip" title="'.Text::_("COM_COLLECTOR_ITEM_HISTORY_TO_BE_CHECKED").'"><span class="icon-clock" aria-hidden="true"></span></span>';
}
switch ($this->meta2->state) {
	case 1:  $badgeV2 = '<span class="text-success" data-bs-toggle="tooltip" title="'.Text::_("COM_COLLECTOR_ITEM_HISTORY_PUBLISHED").'"><span class="icon-publish" aria-hidden="true"></span></span>'; break;
	case 2:  $badgeV2 = '<span class="text-muted"   data-bs-toggle="tooltip" title="'.Text::_("COM_COLLECTOR_ITEM_HISTORY_ARCHIVED" ).'"><span class="icon-archive" aria-hidden="true"></span></span>'; break;
	default: $badgeV2 = '<span class="text-warning" data-bs-toggle="tooltip" title="'.Text::_("COM_COLLECTOR_ITEM_HISTORY_TO_BE_CHECKED").'"><span class="icon-clock" aria-hidden="true"></span></span>';
}

// Mode validation : version A active (state=1) + version B à valider (state=0)
$toValidate = ($this->meta1->state == 1) && ($this->meta2->state == 0);
?>
<!DOCTYPE html>
<html lang="<?php echo $this->document->getLanguage(); ?>" dir="<?php echo $this->document->getDirection(); ?>">
<head>
<meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">
<title><?php echo Text::_('COM_COLLECTOR_ITEM_HISTORY_COMPARE'); ?></title>
<link rel="stylesheet" href="<?php echo Uri::root(true); ?>/media/vendor/bootstrap/css/bootstrap.min.css">
<link rel="stylesheet" href="<?php echo Uri::root(true); ?>/media/system/css/system.css">
<style>
body{font-size:.875rem}
.compare-header{position:sticky;top:0;z-index:10;background:var(--bs-body-bg,#fff);border-bottom:2px solid #dee2e6;padding:.6rem 1rem}
.compare-table{table-layout:fixed;width:100%}
.col-label{width:15%}.col-v1{width:36%}.col-sep{width:5%;text-align:center}.col-v2{width:36%}.col-valid{width:8%;text-align:center}
.row-changed .col-label{border-left:3px solid #fd7e14}
.cell-changed{background:rgba(253,126,20,.07)}.cell-equal{color:#6c757d}
.diff-icon.changed{color:#fd7e14;font-size:1.1rem}.diff-icon.equal{color:#ced4da}
.diff-word ins{background:rgba(25,135,84,.15);text-decoration:none;border-radius:2px;padding:0 2px}
.diff-word del{background:rgba(220,53,69,.15);text-decoration:line-through;border-radius:2px;padding:0 2px}
.value-number{font-family:var(--bs-font-monospace,monospace);font-size:1rem}
.value-number.up{color:#198754}.value-number.down{color:#dc3545}
.value-define{display:inline-block;background:#e9ecef;border-radius:4px;padding:2px 8px;font-size:.82rem}
.cell-changed .value-define{background:rgba(253,126,20,.12);border:1px solid rgba(253,126,20,.3)}
.value-empty{color:#adb5bd;font-style:italic;font-size:.8rem}
.btn-toggle{min-width:52px;font-size:.75rem;padding:2px 6px}
.btn-toggle.is-oui{background:#198754;border-color:#198754;color:#fff}
.btn-toggle.is-non{background:transparent;border-color:#adb5bd;color:#6c757d}
.btn-toggle.is-oui:hover{background:#157347}
.btn-toggle.is-non:hover{border-color:#6c757d;color:#495057}
.summary-bar{background:#f8f9fa;border-top:1px solid #dee2e6;padding:.5rem 1rem;font-size:.8rem;color:#6c757d}
.summary-bar strong{color:#fd7e14}
#modal-refuse-note { z-index: 9999; }
#modal-refuse-note .modal-backdrop { z-index: 9998; }
</style>
</head>
<body>

<form id="validate-form" action="<?php echo $formAction; ?>" method="post">

  <!-- Champs cachés : valeurs de tous les champs + métadonnées + token CSRF -->
  <div hidden aria-hidden="true">
    <?php foreach ($this->comparison as $row): ?>
      <input type="hidden"
             id="field-<?php echo $this->escape($row['column']); ?>"
             name="fields[<?php echo $this->escape($row['column']); ?>]"
             value="<?php echo htmlspecialchars($row['v1_raw'], ENT_QUOTES, 'UTF-8'); ?>">
    <?php endforeach; ?>
    <!-- accepted_count : mis à jour par JS lors de chaque toggle, lu par le contrôleur -->
    <input type="hidden" id="accepted-count-input" name="accepted_count" value="0">
    <input type="hidden" name="collection" value="<?php echo (int) $this->collection; ?>">
    <input type="hidden" name="item"       value="<?php echo (int) $this->item; ?>">
    <input type="hidden" name="v1"         value="<?php echo (int) $this->v1; ?>">
    <input type="hidden" name="v2"         value="<?php echo (int) $this->v2; ?>">
	<input type="hidden" id="refuse-note-hidden" name="refuse_note" value="">
    <?php echo HTMLHelper::_('form.token'); ?>
  </div>

  <!-- ── En-tête ── -->
  <div class="compare-header">
    <div class="row align-items-center g-2 flex-nowrap">
      <div class="col-auto">
        <span class="fw-semibold text-muted"><?php echo Text::_('COM_COLLECTOR_ITEM_HISTORY_COMPARE'); ?></span>
      </div>
      <div class="col text-center">
        <?php echo $badgeV1; ?>
        <span class="text-body-secondary"><?php echo $this->escape($meta1Date); ?></span>
        <?php if ($meta1Auth): ?><span class="text-muted ms-1 small">— <?php echo $this->escape($meta1Auth); ?></span><?php endif; ?>
      </div>
      <div class="col-auto text-muted px-1">↔</div>
      <div class="col text-center">
        <?php echo $badgeV2; ?>
        <span class="text-body-secondary"><?php echo $this->escape($meta2Date); ?></span>
        <?php if ($meta2Auth): ?><span class="text-muted ms-1 small">— <?php echo $this->escape($meta2Auth); ?></span><?php endif; ?>
      </div>
      <?php if ($toValidate): ?>
      <div class="col-auto d-flex align-items-center gap-2">
        <!-- Compteur de modifications acceptées (affiché dès qu'il y en a au moins une) -->
        <span id="validate-counter" class="badge bg-success" style="display:none">
          <span id="validate-count">0</span>
          <?php echo ' ' . Text::_('COM_COLLECTOR_ITEM_HISTORY_COMPARE_ACCEPTED'); ?>
        </span>
        <!-- Ouvre la modale de confirmation — ne soumet pas directement -->
        <button type="button" id="btn-validate" class="btn btn-sm btn-success"
                onclick="onValidateClick()">
          <span class="icon-check me-1" aria-hidden="true"></span>
          <?php echo Text::_('COM_COLLECTOR_VALID'); ?>
        </button>
      </div>
      <?php endif; ?>
      <div class="col-auto">
        <button type="button" class="btn btn-sm btn-outline-secondary" onclick="window.close()">✕</button>
      </div>
    </div>
  </div>

  <!-- ── Zone d'alerte erreurs AJAX ── -->
  <div id="compare-alert" class="alert alert-danger alert-dismissible mx-3 mt-2 mb-0" style="display:none" role="alert">
    <button type="button" class="btn-close" onclick="this.closest('.alert').style.display='none'"></button>
    <span id="compare-alert-msg"></span>
  </div>

  <!-- ── Tableau de comparaison ── -->
  <div class="px-3 py-3">
  <?php if (empty($this->comparison)): ?>
    <div class="alert alert-info"><?php echo Text::_('JGLOBAL_NO_MATCHING_RESULTS'); ?></div>
  <?php else: ?>
    <table class="table table-sm table-bordered compare-table">
      <thead class="table-light">
        <tr>
          <th class="col-label fw-semibold text-muted"><?php echo Text::_('COM_COLLECTOR_MOD'); ?></th>
          <th class="col-v1  fw-semibold text-muted small"><?php echo $this->escape($this->meta1->modification); ?></th>
          <th class="col-sep"></th>
          <th class="col-v2  fw-semibold text-muted small"><?php echo $this->escape($this->meta2->modification); ?></th>
          <?php if ($toValidate): ?><th class="col-valid"></th><?php endif; ?>
        </tr>
        <tr>
          <th class="col-label"><?php echo Text::_('COM_COLLECTOR_FIELDS'); ?></th>
          <th class="col-v1"><?php echo $badgeV1 . ' ' . $this->escape($meta1Date); ?></th>
          <th class="col-sep"></th>
          <th class="col-v2"><?php echo $badgeV2 . ' ' . $this->escape($meta2Date); ?></th>
          <?php if ($toValidate): ?><th class="col-valid small text-muted"><?php echo Text::_('COM_COLLECTOR_VALID'); ?></th><?php endif; ?>
        </tr>
      </thead>
      <tbody>
      <?php foreach ($this->comparison as $row):
        $col   = $this->escape($row['column']);
        $v1raw = htmlspecialchars($row['v1_raw'], ENT_QUOTES, 'UTF-8');
        $v2raw = htmlspecialchars($row['v2_raw'], ENT_QUOTES, 'UTF-8');
      ?>
        <tr class="<?php echo $row['changed'] ? 'row-changed' : ''; ?>">
          <td class="col-label fw-semibold small text-muted">
            <?php echo $this->escape($row['label']); ?>
            <div style="font-size:.7rem;font-weight:400">
              <?php echo Text::_('COM_COLLECTOR_' . strtoupper($this->escape($row['type']))); ?>
            </div>
          </td>
          <td class="col-v1 <?php echo $row['changed'] ? 'cell-changed' : 'cell-equal'; ?>">
            <?php echo $this->renderFieldValue($row, 'v1'); ?>
          </td>
          <td class="col-sep align-middle text-center">
            <?php if ($row['changed']): ?>
              <span class="diff-icon changed" data-bs-toggle="tooltip"
                    title="<?php echo Text::_('COM_COLLECTOR_ITEM_HISTORY_FIELD_CHANGED'); ?>">≠</span>
            <?php else: ?>
              <span class="diff-icon equal">=</span>
            <?php endif; ?>
          </td>
          <td class="col-v2 <?php echo $row['changed'] ? 'cell-changed' : 'cell-equal'; ?>">
            <?php echo $this->renderFieldValue($row, 'v2'); ?>
          </td>
          <?php if ($toValidate): ?>
          <td class="col-valid align-middle text-center">
            <?php if ($row['changed']): ?>
              <button type="button"
                      class="btn btn-toggle is-non"
                      data-col="<?php echo $col; ?>"
                      data-v1="<?php echo $v1raw; ?>"
                      data-v2="<?php echo $v2raw; ?>"
                      data-active="0"
                      onclick="toggleField(this)"
                      data-bs-toggle="tooltip"
                      data-bs-placement="left"
                      title="<?php echo Text::_('COM_COLLECTOR_ITEM_HISTORY_COMPARE_TOGGLE_HINT'); ?>">
                <?php echo Text::_('JNO'); ?>
              </button>
            <?php endif; ?>
          </td>
          <?php endif; ?>
        </tr>
      <?php endforeach; ?>
      </tbody>
    </table>
  <?php endif; ?>
  </div>

  <!-- ── Barre de résumé ── -->
  <div class="summary-bar">
  <?php if ($changedCount === 0): ?>
    <?php echo Text::_('COM_COLLECTOR_ITEM_HISTORY_COMPARE_NO_DIFF'); ?>
  <?php else: ?>
    <strong><?php echo $changedCount; ?></strong>
    <?php echo Text::plural('COM_COLLECTOR_ITEM_HISTORY_COMPARE_N_DIFF', $changedCount); ?>
    <?php echo Text::sprintf('COM_COLLECTOR_ITEM_HISTORY_COMPARE_ON_N_FIELDS', $totalCount); ?>
  <?php endif; ?>
  </div>

</form>

<?php if ($toValidate): ?>
<!-- ── Modale de confirmation ─────────────────────────────────────────────────
     Ouverte par data-bs-toggle sur le bouton "Valider".
     onShow : recalcule le récapitulatif depuis l'état courant des boutons toggle.
     "Confirmer" déclenche le vrai envoi AJAX.
-->
<div class="modal fade" id="modal-confirm" tabindex="-1"
     aria-labelledby="modal-confirm-label" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="modal-confirm-label">
          <?php echo Text::_('COM_COLLECTOR_ITEM_HISTORY_COMPARE_CONFIRM_TITLE'); ?>
        </h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body px-4 py-3">
        <p class="mb-3" id="confirm-summary"></p>
        <!-- Détail : liste des champs acceptés (vide si 0) -->
        <ul id="confirm-detail" class="list-unstyled mb-0 small text-success"></ul>
        <!-- Message alternatif si 0 modification acceptée -->
        <p id="confirm-no-change" class="text-muted mb-3" style="display:none">
          <?php echo Text::_('COM_COLLECTOR_ITEM_HISTORY_COMPARE_CONFIRM_NO_CHANGE'); ?>
        </p>
      </div>
      <div class="modal-footer px-4">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
          <?php echo Text::_('JCANCEL'); ?>
        </button>
        <button type="button" id="btn-confirm" class="btn btn-success" onclick="doSubmit()">
          <span class="icon-check me-1" aria-hidden="true"></span>
          <?php echo Text::_('COM_COLLECTOR_CONFIRM'); ?>
        </button>
      </div>
    </div>
  </div>
</div>

<div class="modal fade" id="modal-refuse-note" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">
                    <?php echo Text::_('COM_COLLECTOR_ITEM_HISTORY_REFUSE_TITLE'); ?>
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body px-4 py-3">
				<p class="text-muted small mb-3">
					<?php echo Text::_('COM_COLLECTOR_ITEM_HISTORY_REFUSE_NOTE_HINT'); ?>
				</p>
				<textarea id="refuse-note-input"
						  class="form-control"
						  rows="4"
						  placeholder="<?php echo Text::_('COM_COLLECTOR_ITEM_HISTORY_REFUSE_NOTE_PLACEHOLDER'); ?>"></textarea>
			</div>
            <div class="modal-footer px-4">
				<button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">
					<?php echo Text::_('JCANCEL'); ?>
				</button>
				<button type="button" class="btn btn-danger d-flex align-items-center gap-1" onclick="doRefuseWithNote()">
					<span class="icon-cancel" aria-hidden="true"></span>
					<?php echo Text::_('COM_COLLECTOR_ITEM_HISTORY_REFUSE'); ?>
				</button>
			</div>
        </div>
    </div>
</div>
<?php endif; ?>

<script src="<?php echo Uri::root(true); ?>/media/vendor/bootstrap/js/bootstrap.bundle.min.js"></script>
<script>
/* ── Textes localisés injectés côté PHP ─────────────────────────────────── */
var L = {
    accepted : <?php echo json_encode(Text::_('COM_COLLECTOR_ITEM_HISTORY_COMPARE_ACCEPTED')); ?>,
    oui      : <?php echo json_encode(Text::_('JYES')); ?>,
    non      : <?php echo json_encode(Text::_('JNO')); ?>,
    errHttp  : <?php echo json_encode(Text::_('JERROR_AN_ERROR_HAS_OCCURRED')); ?>,
    summary  : <?php echo json_encode(Text::_('COM_COLLECTOR_ITEM_HISTORY_COMPARE_CONFIRM_SUMMARY')); ?>
    //  Chaîne attendue : "Vous allez accepter %1$d modification(s) sur %2$d différence(s)."
};

/* ── Initialisation tooltips ─────────────────────────────────────────────── */
document.addEventListener('DOMContentLoaded', function () {
    document.querySelectorAll('[data-bs-toggle="tooltip"]').forEach(function (el) {
        new bootstrap.Tooltip(el);
    });

    <?php if ($toValidate): ?>
    // Renseigne le récapitulatif chaque fois que la modale de confirmation s'ouvre
    var modalEl = document.getElementById('modal-confirm');
    modalEl.addEventListener('show.bs.modal', function () {
        buildConfirmSummary();
    });
    <?php endif; ?>
});

/* ── Toggle Oui / Non ────────────────────────────────────────────────────── */
function toggleField(btn) {
    var col      = btn.dataset.col;
    var hidden   = document.getElementById('field-' + col);
    var isActive = btn.dataset.active === '1';

    if (isActive) {
        hidden.value       = btn.dataset.v1;
        btn.dataset.active = '0';
        btn.className      = 'btn btn-toggle is-non';
        btn.textContent    = L.non;
    } else {
        hidden.value       = btn.dataset.v2;
        btn.dataset.active = '1';
        btn.className      = 'btn btn-toggle is-oui';
        btn.textContent    = L.oui;
    }

    updateCounter();
}

/* ── Compteur dans l'en-tête ────────────────────────────────────────────── */
function updateCounter() {
    var count   = document.querySelectorAll('.btn-toggle[data-active="1"]').length;
    var counter = document.getElementById('validate-counter');
    var span    = document.getElementById('validate-count');

    // Synchro hidden input lu par le contrôleur PHP
    document.getElementById('accepted-count-input').value = count;

    span.textContent      = count;
    counter.style.display = count > 0 ? 'inline-block' : 'none';
}

/* ── Récapitulatif dans la modale de confirmation ────────────────────────── */
function buildConfirmSummary() {
    var total    = parseInt('<?php echo $changedCount; ?>', 10);
    var accepted = document.querySelectorAll('.btn-toggle[data-active="1"]').length;

    // Résumé principal
    var summaryEl   = document.getElementById('confirm-summary');
    var detailEl    = document.getElementById('confirm-detail');
    var noChangeEl  = document.getElementById('confirm-no-change');
    var confirmBtn  = document.getElementById('btn-confirm');

    summaryEl.textContent = L.summary
        .replace('%1$d', accepted)
        .replace('%2$d', total);

    detailEl.innerHTML  = '';
    noChangeEl.style.display = 'none';

    if (accepted > 0) {
        // Liste des champs acceptés
        document.querySelectorAll('.btn-toggle[data-active="1"]').forEach(function (btn) {
            var tr    = btn.closest('tr');
            var label = tr ? tr.querySelector('.col-label') : null;
            var name  = label ? label.childNodes[0].textContent.trim() : btn.dataset.col;
            var li    = document.createElement('li');
            li.innerHTML = '<span class="icon-check text-success me-1"></span>' + name;
            detailEl.appendChild(li);
        });
        confirmBtn.disabled = false;
        confirmBtn.className = 'btn btn-success';
    } else {
        // Aucune modification acceptée : on archive v2 sans l'activer
        noChangeEl.style.display = 'block';
        confirmBtn.disabled = false;
        confirmBtn.className = 'btn btn-warning';
    }
}

function onValidateClick() {
    const accepted = parseInt(document.getElementById('accepted-count-input').value, 10);
    
    if (accepted === 0) {
        // Ouvrir modale de note de refus
        const refuseModal = new bootstrap.Modal(document.getElementById('modal-refuse-note'));
        refuseModal.show();
    } else {
        // Ouvrir modale de confirmation normale
        const confirmModal = new bootstrap.Modal(document.getElementById('modal-confirm'));
        confirmModal.show();
    }
}

/* ── Soumission AJAX (appelée depuis "Confirmer" dans la modale) ─────────── */
async function doSubmit() {
    const accepted = parseInt(document.getElementById('accepted-count-input').value, 10);
    
    // Si 0 modification acceptée → c'est un refus → demander une note
    if (accepted === 0) {
        bootstrap.Modal.getInstance(document.getElementById('modal-confirm')).hide();
        openRefuseNoteModal();
        return;
    }

    // Sinon → validation normale
    await doValidate();
}

function openRefuseNoteModal() {
    document.getElementById('refuse-note-input').value = '';
    new bootstrap.Modal(document.getElementById('modal-refuse-note')).show();
}

async function doRefuseWithNote() {
    const note = document.getElementById('refuse-note-input').value;
    document.getElementById('refuse-note-hidden').value = note;
    
    // Fermer la modale de refus
    bootstrap.Modal.getInstance(document.getElementById('modal-refuse-note'))?.hide();
    
    // Appeler doValidate sans passer par confirmModal.hide()
    await doValidate();
}

async function doValidate() {
    // Fermer les modales ouvertes proprement
    ['modal-confirm', 'modal-refuse-note'].forEach(id => {
        const el = document.getElementById(id);
        if (el) bootstrap.Modal.getInstance(el)?.hide();
    });

    // btn-confirm peut être null si on vient de doRefuseWithNote
    var confirmBtn = document.getElementById('btn-confirm');
    var origHTML   = confirmBtn ? confirmBtn.innerHTML : '';
    if (confirmBtn) {
        confirmBtn.disabled  = true;
        confirmBtn.innerHTML = '<span class="icon-loop me-1"></span>...';
    }

    var validateBtn = document.getElementById('btn-validate');
    if (validateBtn) validateBtn.disabled = true;

    try {
        var form     = document.getElementById('validate-form');
        var response = await fetch(form.action, {
            method  : 'POST',
            body    : new FormData(form),
            headers : {'X-Requested-With': 'XMLHttpRequest'}
        });

        if (!response.ok) throw new Error('HTTP ' + response.status);

        var result = await response.json();

        if (result.success) {
            if (window.opener && !window.opener.closed) {
                window.opener.location.href = result.redirectUrl;
            }
            window.close();
        } else {
            showError(result.message);
            if (confirmBtn) { confirmBtn.disabled = false; confirmBtn.innerHTML = origHTML; }
            if (validateBtn) validateBtn.disabled = false;
        }

    } catch(e) {
        showError(e.message || L.errHttp);
        if (confirmBtn) { confirmBtn.disabled = false; confirmBtn.innerHTML = origHTML; }
        if (validateBtn) validateBtn.disabled = false;
    }
}

/* ── Affichage d'un message d'erreur ────────────────────────────────────── */
function showError(msg) {
    var alert = document.getElementById('compare-alert');
    var span  = document.getElementById('compare-alert-msg');
    span.textContent    = msg;
    alert.style.display = 'block';
    alert.scrollIntoView({behavior: 'smooth', block: 'nearest'});
}
</script>
</body>
</html>