<?php
defined('_JEXEC') or die;

use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Router\Route;
use Joomla\CMS\Session\Session;

$token      = Session::getFormToken();
$collection = $this->collection;
$itemId     = $this->item;
$v1         = $this->v1;
$v2         = $this->v2;
$toValidate = $this->canValidate && $this->meta2 && (int)$this->meta2->state === 0;
$changedCount = count(array_filter($this->comparison ?: [], fn($r) => $r['changed']));

HTMLHelper::_('bootstrap.modal');
?>

<div class="collector-versionscompare p-3">

<form id="validate-form"
      action="<?php echo Route::_('index.php?option=com_collector&task=itemversions.validatefields', false); ?>"
      method="post">

  <input type="hidden" name="collection"     value="<?php echo $collection; ?>">
  <input type="hidden" name="item"           value="<?php echo $itemId; ?>">
  <input type="hidden" name="v1"             value="<?php echo $v1; ?>">
  <input type="hidden" name="v2"             value="<?php echo $v2; ?>">
  <input type="hidden" name="accepted_count" id="accepted-count-input" value="0">
  <?php echo HTMLHelper::_('form.token'); ?>

  <?php /* Champs cachés pour les valeurs → initialisés à v1_raw (conserve la valeur active par défaut) */ ?>
  <?php foreach ($this->comparison as $row): ?>
  <input type="hidden"
         id="field-<?php echo $this->escape($row['column']); ?>"
         name="fields[<?php echo $this->escape($row['column']); ?>]"
         value="<?php echo htmlspecialchars($row['v1_raw'], ENT_QUOTES); ?>">
  <?php endforeach; ?>
  
  <?php /* ── En-têtes des deux versions ─────────────────────────────────── */ ?>
  <table class="table table-sm table-bordered">
    <thead>
      <tr class="table-light">
        <th style="width:20%"><?php echo Text::_('COM_COLLECTOR_FIELD'); ?></th>
        <th style="width:35%" class="table-success">
          <span class="icon-check me-1"></span>
          <?php echo Text::_('COM_COLLECTOR_VERSION_ACTIVE'); ?>
          <?php if ($this->meta1): ?>
          <div class="small text-muted fw-normal">
            <?php echo HTMLHelper::_('date', $this->meta1->modified, Text::_('DATE_FORMAT_LC2')); ?>
            — <?php echo htmlspecialchars($this->meta1->author_name ?? '', ENT_QUOTES); ?>
          </div>
          <?php endif; ?>
        </th>
        <th style="width:35%" class="<?php echo (int)($this->meta2->state ?? -1) === 0 ? 'table-warning' : 'table-secondary'; ?>">
		  <?php if ((int)($this->meta2->state ?? -1) === 0): ?>
			<span class="icon-clock me-1"></span>
			<?php echo Text::_('COM_COLLECTOR_VERSION_PENDING'); ?>
		  <?php elseif ((int)($this->meta2->state ?? -1) === 2): ?>
			<span class="icon-archive me-1"></span>
			<?php echo Text::_('COM_COLLECTOR_ITEM_HISTORY_ARCHIVED'); ?>
		  <?php else: ?>
			<span class="icon-eye me-1"></span>
			<?php echo Text::_('COM_COLLECTOR_VERSION_OTHER'); ?>
		  <?php endif; ?>
		  <?php if ($this->meta2): ?>
		  <div class="small text-muted fw-normal">
			<?php echo HTMLHelper::_('date', $this->meta2->modified, Text::_('DATE_FORMAT_LC2')); ?>
			— <?php echo htmlspecialchars($this->meta2->author_name ?? '', ENT_QUOTES); ?>
			<?php if (!empty($this->meta2->modification)): ?>
			<br><em><?php echo htmlspecialchars($this->meta2->modification, ENT_QUOTES); ?></em>
			<?php endif; ?>
		  </div>
		  <?php endif; ?>
		</th>
		<?php if ($toValidate): ?>
		<th style="width:10%" class="text-center small text-muted">
		  <?php echo Text::_('COM_COLLECTOR_VALID'); ?>
		</th>
		<?php endif; ?>
      </tr>
    </thead>
    <tbody>
      <?php foreach ($this->comparison as $row): 
		$col   = $this->escape($row['column']);
		$label = $this->escape($row['label']);
        $v1raw = htmlspecialchars($row['v1_raw'], ENT_QUOTES);
        $v2raw = htmlspecialchars($row['v2_raw'], ENT_QUOTES);
	    $changed = $row['changed']; ?>
      <tr class="<?php echo $changed ? 'table-danger bg-opacity-10' : ''; ?>">
        <td class="small fw-semibold">
          <?php echo htmlspecialchars($row['label'], ENT_QUOTES); ?>
        </td>
        <td class="small">
          <?php echo $this->renderFieldValue($row, 'v1'); ?>
        </td>
        <td class="small <?php echo $changed ? 'fw-semibold' : ''; ?>">
          <?php echo $this->renderFieldValue($row, 'v2'); ?>
        </td>
		<?php if ($toValidate): ?>
		<td class="text-center align-middle">
			<?php if ($row['changed']): ?>
			<button type="button"
					class="btn btn-sm btn-outline-secondary btn-toggle is-non"
					data-col="<?php echo $col; ?>"
					data-label="<?php echo htmlspecialchars($row['label'], ENT_QUOTES); ?>"
					data-v1="<?php echo $v1raw; ?>"
					data-v2="<?php echo $v2raw; ?>"
					data-active="0"
					onclick="toggleField(this)">
			  <?php echo Text::_('JNO'); ?>
			</button>
			<?php endif; ?>
		</td>
		<?php endif; ?>
      </tr>
      <?php endforeach; ?>
    </tbody>
  </table>

  <?php /* ── Boutons d'action ──────────────────────────────────────────── */ ?>
	<?php if ($this->canValidate): ?>
	<div class="d-flex gap-2 mt-3 align-items-center">

	  <?php if ($toValidate): ?>
	  <button type="button" id="btn-validate" class="btn btn-success"
			  data-bs-toggle="modal" data-bs-target="#modal-confirm" disabled>
		<span class="icon-check me-1"></span>
		<?php echo Text::_('COM_COLLECTOR_ITEM_HISTORY_VALIDATE'); ?>
		<span id="validate-counter" class="badge bg-white text-success ms-1" style="display:none">0</span>
	  </button>
	  <button type="button" id="btn-refuse" class="btn btn-danger"
			  onclick="openRefuseModal(<?php echo $v2; ?>)">
		  <span class="icon-cancel me-1"></span>
		  <?php echo Text::_('COM_COLLECTOR_ITEM_HISTORY_REFUSE'); ?>
	  </button>

	  <?php elseif ($this->meta2 && (int)$this->meta2->state === 2): ?>
	  <a href="#"
		   onclick="doRestore('<?php echo Route::_(
			   'index.php?option=com_collector&task=itemversions.restore'
			   . '&collection=' . $collection
			   . '&item=' . $itemId
			   . '&version_id=' . $v2
			   . '&' . $token . '=1', false); ?>'); return false;"
		   class="btn btn-outline-primary">
		<span class="icon-undo me-1"></span>
		<?php echo Text::_('COM_COLLECTOR_ITEM_HISTORY_RESTORE_THIS'); ?>
	  </a>
	  <?php endif; ?>

	  <button type="button" class="btn btn-outline-secondary ms-auto" onclick="window.close()">
		<?php echo Text::_('JCLOSE'); ?>
	  </button>
	</div>
	<?php endif; ?>
</form>

</div>

<?php if ($toValidate): ?>
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
      <div class="modal-body">
        <p id="confirm-summary"></p>
        <!-- Détail : liste des champs acceptés (vide si 0) -->
        <ul id="confirm-detail" class="list-unstyled mb-0 small text-success"></ul>
        <!-- Message alternatif si 0 modification acceptée -->
        <p id="confirm-no-change" class="text-muted mb-0" style="display:none">
          <?php echo Text::_('COM_COLLECTOR_ITEM_HISTORY_COMPARE_CONFIRM_NO_CHANGE'); ?>
        </p>
      </div>
      <div class="modal-footer">
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

<!-- Modale saisie note de refus -->
<div class="modal fade" id="modal-refuse" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">
                    <?php echo Text::_('COM_COLLECTOR_ITEM_HISTORY_REFUSE_TITLE'); ?>
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p class="text-muted small">
                    <?php echo Text::_('COM_COLLECTOR_ITEM_HISTORY_REFUSE_NOTE_HINT'); ?>
                </p>
                <textarea id="refuse-note-input"
                          class="form-control"
                          rows="4"
                          placeholder="<?php echo Text::_('COM_COLLECTOR_ITEM_HISTORY_REFUSE_NOTE_PLACEHOLDER'); ?>"></textarea>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">
                    <?php echo Text::_('JCANCEL'); ?>
                </button>
                <button type="button" class="btn btn-danger" onclick="doRefuse()">
                    <span class="icon-cancel me-1"></span>
                    <?php echo Text::_('COM_COLLECTOR_ITEM_HISTORY_REFUSE'); ?>
                </button>
            </div>
        </div>
    </div>
</div>
<?php endif; ?>

<script>
var REDIRECT_URL = '<?php echo Route::_('index.php?option=com_collector&view=itemversions&collection=' . $collection . '&item=' . $itemId, false); ?>';

var L = {
    accepted : <?php echo json_encode(Text::_('COM_COLLECTOR_ITEM_HISTORY_COMPARE_ACCEPTED')); ?>,
    oui      : <?php echo json_encode(Text::_('JYES')); ?>,
    non      : <?php echo json_encode(Text::_('JNO')); ?>,
    errHttp  : <?php echo json_encode(Text::_('JERROR_AN_ERROR_HAS_OCCURRED')); ?>,
    summary  : <?php echo json_encode(Text::_('COM_COLLECTOR_ITEM_HISTORY_COMPARE_CONFIRM_SUMMARY')); ?>
    //  Chaîne attendue : "Vous allez accepter %1$d modification(s) sur %2$d différence(s)."
};
var CHANGED_COUNT = <?php echo (int) count(array_filter($this->comparison ?: [], fn($r) => $r['changed'])); ?>;

var refuseVersionId = 0;
var refuseModal     = null;

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
	
	const modalRefEl = document.getElementById('modal-refuse');
    if (modalRefEl) {
        refuseModal = new bootstrap.Modal(modalRefEl);
    }
    <?php endif; ?>
});

/* ── Toggle Oui / Non ────────────────────────────────────────────────────── */
function toggleField(btn) {
    const col      = btn.dataset.col;
    const hidden   = document.getElementById('field-' + col);
    const isActive = btn.dataset.active === '1';
    const tr       = btn.closest('tr');  // ← récupérer la ligne

    if (isActive) {
        hidden.value       = btn.dataset.v1;
        btn.dataset.active = '0';
        btn.classList.remove('is-oui');
        btn.classList.add('is-non', 'btn-outline-secondary');
        btn.textContent    = '<?php echo Text::_('JNO'); ?>';
        tr?.classList.remove('row-accepted');  // ← retirer la surbrillance
    } else {
        hidden.value       = btn.dataset.v2;
        btn.dataset.active = '1';
        btn.classList.remove('is-non', 'btn-outline-secondary');
        btn.classList.add('is-oui');
        btn.textContent    = '<?php echo Text::_('JYES'); ?>';
        tr?.classList.add('row-accepted');  // ← ajouter la surbrillance
    }

    updateCounter();
}

/* ── Compteur dans l'en-tête ────────────────────────────────────────────── */
function updateCounter() {
    var count   = document.querySelectorAll('.btn-toggle[data-active="1"]').length;
    var counter = document.getElementById('validate-counter');
    var btnVal  = document.getElementById('btn-validate');

    // Synchro hidden input lu par le contrôleur PHP
    document.getElementById('accepted-count-input').value = count;

    counter.textContent    = count;
    counter.style.display  = count > 0 ? 'inline-block' : 'none';
    btnVal.disabled        = count === 0;
}

/* ── Récapitulatif dans la modale de confirmation ────────────────────────── */
function buildConfirmSummary() {
    var total    = CHANGED_COUNT;
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
            var name  = label ? label.childNodes[0].textContent.trim() : btn.dataset.label;
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

/* ── Soumission AJAX (appelée depuis "Confirmer" dans la modale) ─────────── */
async function doSubmit() {
    var confirmBtn = document.getElementById('btn-confirm');
    var origHTML   = confirmBtn.innerHTML;

    // Fermer la modale de confirmation et désactiver le bouton
    bootstrap.Modal.getInstance(document.getElementById('modal-confirm')).hide();
    confirmBtn.disabled  = true;
    confirmBtn.innerHTML = '<span class="icon-loop me-1"></span>...';

    var validateBtn = document.getElementById('btn-validate');
    if (validateBtn) {
        validateBtn.disabled = true;
    }

    try {
        var form     = document.getElementById('validate-form');
        var response = await fetch(form.action, {
            method  : 'POST',
            body    : new FormData(form),
            headers : {'X-Requested-With': 'XMLHttpRequest'}
        });

        if (!response.ok) {
            throw new Error('HTTP ' + response.status);
        }

        var result = await response.json();

        if (result.success) {
			// ── Topologie nouvelle : window.opener = fenêtre itemversions ─────
            // itemversions est une page normale (plus une iframe).
            // On redirige directement window.opener vers la liste items.
            // Le message a déjà été persisté en session PHP côté contrôleur :
            // Joomla l'affichera automatiquement au chargement de items.
            if (window.opener && !window.opener.closed) {
                window.opener.location.href = REDIRECT_URL;
            }
            window.close();

        } else {
            showError(result.message);
            confirmBtn.disabled  = false;
            confirmBtn.innerHTML = origHTML;
            if (validateBtn) validateBtn.disabled = false;
        }

    } catch(e) {
        showError(e.message || L.errHttp);
        confirmBtn.disabled  = false;
        confirmBtn.innerHTML = origHTML;
        if (validateBtn) validateBtn.disabled = false;
    }
}

function doRestore(url) {
    if (window.opener && !window.opener.closed) {
        window.opener.location.href = url;
    } else {
        window.location.href = url;
    }
    window.close();
}

function openRefuseModal(versionId) {
    refuseVersionId = versionId;
    document.getElementById('refuse-note-input').value = '';
    refuseModal.show();
}

function doRefuse() {
    const note = document.getElementById('refuse-note-input').value;
    const url  = 'index.php?option=com_collector&task=itemversions.refuse'
               + '&collection=' + <?php echo (int) $this->collection; ?>
               + '&item='       + <?php echo (int) $this->item; ?>
               + '&version_id=' + <?php echo (int) $this->v2; ?>
               + '&refuse_note=' + encodeURIComponent(note)
               + '&<?php echo Session::getFormToken(); ?>=1';

    if (window.opener && !window.opener.closed) {
        window.opener.location.href = url;
    } else {
        window.location.href = url;
    }
    window.close();
}
</script>