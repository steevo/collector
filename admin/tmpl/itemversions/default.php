<?php
/**
 * Joomla! 3.0 component Collector
 *
 * @package 	Collector
 * @copyright   Copyright (C) 2010 - 2020 Philippe Ousset. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 *
 * Collector is a Multi Purpose Listing Tool.
 * Originaly developped to list Collections
 * it can be used for several purpose.
 */

// no direct access

defined('_JEXEC') or die('Restricted access');

use Joomla\CMS\Factory;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Language\Associations;
use Joomla\CMS\Language\Multilanguage;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Layout\LayoutHelper;
use Joomla\CMS\Router\Route;
use Joomla\CMS\Session\Session;
use Joomla\CMS\Toolbar\Toolbar;

// HTMLHelper::addIncludePath(JPATH_COMPONENT.'/helpers/html');
HTMLHelper::_('bootstrap.tooltip');
HTMLHelper::_('bootstrap.modal');
HTMLHelper::_('behavior.multiselect');
// HTMLHelper::_('formbehavior.chosen', 'select');

$app		= Factory::getApplication();
$user		= Factory::getApplication()->getIdentity();
$userId		= $user->get('id');

$listOrder	= $this->escape($this->state->get('list.ordering'));
$listDirn	= $this->escape($this->state->get('list.direction'));

?>
<script>
var refuseVersionId = 0;
var refuseModal     = null;

document.addEventListener('DOMContentLoaded', function() {
    const btnCompare = document.getElementById('btn-compare');
    const btnPreview = document.getElementById('btn-preview');
	const toutesLesCases = document.querySelectorAll('input[name="cid[]"]');
    const caseToutCocher = document.querySelector('input[name="checkall-toggle"]');
	
    function majEtatBouton() {
        const nbCoches = document.querySelectorAll('input[name="cid[]"]:checked').length;
        if (nbCoches > 0) {
            btnCompare.removeAttribute('disabled');
			btnPreview.removeAttribute('disabled');
        } else {
            btnCompare.setAttribute('disabled', 'disabled');
			btnPreview.setAttribute('disabled', 'disabled');
        }
    }

    toutesLesCases.forEach(caseACocher => caseACocher.addEventListener('change', majEtatBouton));
    if (caseToutCocher) {
        caseToutCocher.addEventListener('change', majEtatBouton);
    }
	
	const tooltipTriggerList = document.querySelectorAll('[data-bs-toggle="tooltip"]');
    const tooltipList = [...tooltipTriggerList].map(tooltipTriggerEl => new bootstrap.Tooltip(tooltipTriggerEl));
	
	const modalEl = document.getElementById('modal-refuse');
    if (modalEl) {
        refuseModal = new bootstrap.Modal(modalEl);
    }
});

function openCompare() {
    const casesCochees = document.querySelectorAll('input[name="cid[]"]:checked');

    if (casesCochees.length !== 2) {
        
        if (window.Joomla && window.Joomla.removeMessages) {
            window.Joomla.removeMessages();
        }
        
        if (window.Joomla && window.Joomla.renderMessages) {
            window.Joomla.renderMessages({
                'error': ["<?php echo Text::_('COM_COLLECTOR_ITEM_HISTORY_COMPARE_ALERT_TWO_ITEMS'); ?>"]
            });
        } else {
            alert("<?php echo Text::_('COM_COLLECTOR_ITEM_HISTORY_COMPARE_ALERT_TWO_ITEMS'); ?>");
        }
        
        window.scrollTo(0, 0);
        return;
    }

    let idA = parseInt(casesCochees[0].value);
    let idB = parseInt(casesCochees[1].value);
    
    const v1 = Math.min(idA, idB);
    const v2 = Math.max(idA, idB);
    
	const item = <?php echo (int) $this->item; ?>;
	const collection = <?php echo (int) $this->collection; ?>;

    const url = `index.php?option=com_collector&view=versionscompare&tmpl=component&collection=${collection}&item=${item}&v1=${v1}&v2=${v2}`;
    window.open(url, '<?php echo Text::_('COM_COLLECTOR_ITEM_HISTORY_COMPARE'); ?>', 'width=1000,height=750,scrollbars=yes,resizable=yes');
}

function openPreview() {
    const casesCochees = document.querySelectorAll('input[name="cid[]"]:checked');

    if (casesCochees.length !== 1) {
        if (window.Joomla && window.Joomla.removeMessages) {
            window.Joomla.removeMessages();
        }
        
        if (window.Joomla && window.Joomla.renderMessages) {
            window.Joomla.renderMessages({
                'error': ["<?php echo Text::_('COM_COLLECTOR_ITEM_HISTORY_PREVIEW_ALERT_ONLY_ONE_ITEM'); ?>"]
            });
        } else {
            alert("<?php echo Text::_('COM_COLLECTOR_ITEM_HISTORY_PREVIEW_ALERT_ONLY_ONE_ITEM'); ?>");
        }
        
        window.scrollTo(0, 0);
        return;
    }

    let v_id = parseInt(casesCochees[0].value);
    
    const item = <?php echo (int) $this->item; ?>;
	const collection = <?php echo (int) $this->collection; ?>;

    const url = `index.php?option=com_collector&view=versionpreview&tmpl=component$collection=$(collection)&item=${item}&v_id=${v_id}`;
    window.open(url, '<?php echo Text::_('COM_COLLECTOR_ITEM_HISTORY_PREVIEW'); ?>', 'width=1000,height=750,scrollbars=yes,resizable=yes');
}

function openCompareVersions(v1, v2) {
    const item       = <?php echo (int) $this->item; ?>;
    const collection = <?php echo (int) $this->collection; ?>;
    const vMin = Math.min(v1, v2);
    const vMax = Math.max(v1, v2);
    const url = `index.php?option=com_collector&view=versionscompare&tmpl=component&collection=${collection}&item=${item}&v1=${vMin}&v2=${vMax}`;
    window.open(url, '<?php echo Text::_('COM_COLLECTOR_ITEM_HISTORY_COMPARE'); ?>', 'width=1000,height=750,scrollbars=yes,resizable=yes');
}

function openRefuseModal(versionId) {
    refuseVersionId = versionId;
    document.getElementById('refuse-note-input').value = '';
    refuseModal.show();
}

function doRefuse() {
    const note = document.getElementById('refuse-note-input').value;
    const url  = 'index.php?option=com_collector&task=itemversions.refuse'
               + '&collection=<?php echo $collection ?? 0; ?>'
               + '&item=<?php echo $this->item ?? 0; ?>'
               + '&version_id=' + refuseVersionId
               + '&refuse_note=' + encodeURIComponent(note)
               + '&<?php echo Session::getFormToken(); ?>=1';
    window.location.href = url;
}

// ── Relay postMessage depuis les popups enfants vers item/edit.php ──────
// La popup de comparaison (window.open) a pour opener cette iframe.
// Elle envoie ses messages à window.opener (= ici). On les relaie
// vers window.parent (= item/edit.php) qui porte le vrai listener.
window.addEventListener('message', function (event) {
    if (event.origin !== window.location.origin) { return; }
    if (!event.data || event.data.collectorAction !== 'versionRestored') { return; }
    window.parent.postMessage(event.data, window.location.origin);
});
</script>

<div class="container-popup">
<form action="<?php echo JRoute::_('index.php?option=com_collector&view=itemversion&collection='.$this->collection.'&item='.$this->item);?>" method="post" name="adminForm" id="adminForm" >

	<div id="system-message-container"></div>
	<div id="j-main-container">
		<?php
		// Search tools bar
		echo LayoutHelper::render('joomla.searchtools.default', array('view' => $this), null, array('debug' => false));
		?>
		<?php if (empty($this->items)) : ?>
			<div class="alert alert-no-items">
				<?php echo Text::_('JGLOBAL_NO_MATCHING_RESULTS'); ?>
			</div>
		<?php else : ?>	
			<table class="table table-sm" id="itemList">
				<thead>
					<tr>
						<td class="w-1 text-center">
							<input class="form-check-input" type="checkbox" name="checkall-toggle" value="" title="<?php echo Text::_('JGLOBAL_CHECK_ALL'); ?>" onclick="Joomla.checkAll(this)">
						</td>
						<th scope="col" style="min-width:85px" class="w-1 text-center">
							<?php echo Text::_( 'JSTATUS' ); ?>
						</th>
						<th scope="col" class="w-20 d-none d-md-table-cell">
							<?php echo HTMLHelper::_('searchtools.sort', 'JDATE', 'h.modified', $listDirn, $listOrder); ?>
						</th>
						<th scope="col">
							<?php echo Text::_('COM_COLLECTOR_ACTIONS'); ?>
						</th>
						<th scope="col" class="w-10 d-none d-md-table-cell">
							<?php echo Text::_( 'JAUTHOR' ); ?>
						</th>
						<th>
							<?php echo Text::_( 'COM_COLLECTOR_MODIFICATION' ); ?>
						</th>
						<th scope="col" class="w-3 d-none d-lg-table-cell">
							<?php echo HTMLHelper::_('searchtools.sort', 'JGRID_HEADING_ID', 'h.id', $listDirn, $listOrder); ?>
						</th>
					</tr>
				</thead>
				<tbody>
				<?php foreach ($this->items as $i => $item) :
					?>
					<tr class="row<?php echo $i % 2; ?>">
						<td class="text-center">
							<?php echo HTMLHelper::_('grid.id', $i, $item->id); ?>
						</td>
						<td class="text-center">
							<?php 
							switch ($item->state) {
								case 1: // Active
									echo '<span class="text-success" data-bs-toggle="tooltip" title="'.Text::_("COM_COLLECTOR_ITEM_HISTORY_PUBLISHED").'"><span class="icon-publish" aria-hidden="true"></span></span>';
									break;
								case 2: // Archivée
									echo '<span class="text-muted" data-bs-toggle="tooltip" title="'.Text::_("COM_COLLECTOR_ITEM_HISTORY_ARCHIVED").'"><span class="icon-archive" aria-hidden="true"></span></span>';
									break;
								case 0: // À valider
								default:
									echo '<span class="text-warning" data-bs-toggle="tooltip" title="'.Text::_("COM_COLLECTOR_ITEM_HISTORY_TO_BE_CHECKED").'"><span class="icon-clock" aria-hidden="true"></span></span>';
									break;
							}
							?>
							<?php // echo HTMLHelper::_('jgrid.published', $item->state, $i, 'itemversions.', false, 'cb'); ?>
						</td>
						<th scope="row">
							<a href="#" class="js-link-open-window save-date" data-url="<?php echo Route::_('index.php?option=com_collector&view=previewhistory&layout=preview&tmpl=component&' . Session::getFormToken() . '=1&item=' . $item->id); ?>">
                                <?php echo HTMLHelper::_('date', $item->modified, Text::_('DATE_FORMAT_LC6')); ?>
                            </a>
							<?php if ($item->state === 2 && !empty($item->refuse_note)): ?>
							<div class="small text-muted fst-italic mt-1">
								<span class="icon-info-circle me-1"></span>
								<?php echo htmlspecialchars($item->refuse_note, ENT_QUOTES); ?>
							</div>
							<?php endif; ?>
						</th>
						<td class="text-nowrap">
						<?php
						$token = Session::getFormToken();
						$baseUrl = 'index.php?option=com_collector&collection=' . (int) $this->collection . '&item=' . (int) $this->item . '&cid[]=' . $item->id . '&' . $token . '=1';
						$compareUrl = 'index.php?option=com_collector&view=versionscompare&tmpl=component&' . $token . '=1&item=' . (int) $this->item . '&collection=' . (int) $this->collection;
						?>

						<?php if ($item->state === 0): // En attente ?>

							<?php if ($item->id === $this->oldestPendingId): // Plus ancienne en attente ?>

								<?php // Comparer avec la version active ?>
								<?php if ($this->activeVersionId): ?>
								<a href="#" onclick="openCompareVersions(<?php echo $this->activeVersionId; ?>, <?php echo $item->id; ?>); return false;"
								   class="btn btn-sm btn-outline-secondary me-1"
								   data-bs-toggle="tooltip" title="<?php echo Text::_('COM_COLLECTOR_ITEM_HISTORY_COMPARE_WITH_ACTIVE'); ?>">
									<span class="icon-eye"></span>
								</a>
								<?php endif; ?>

								<?php // Valider ?>
								<a href="<?php echo Route::_($baseUrl . '&task=itemversions.validate', false); ?>"
								   class="btn btn-sm btn-success me-1"
								   data-bs-toggle="tooltip" title="<?php echo Text::_('COM_COLLECTOR_ITEM_HISTORY_VALIDATE'); ?>">
									<span class="icon-check"></span>
								</a>

								<?php // Refuser ?>
								<!-- Bouton refuser → ouvre modale note -->
								<button type="button"
										class="btn btn-sm btn-danger"
										onclick="openRefuseModal(<?php echo $item->id; ?>)">
									<span class="icon-cancel"></span>
								</button>

							<?php else: // Version en attente mais pas la plus ancienne ?>
								<span class="text-muted small"
									  data-bs-toggle="tooltip"
									  title="<?php echo Text::_('COM_COLLECTOR_ITEM_HISTORY_VALIDATE_BLOCKED'); ?>">
									<span class="icon-lock"></span>
								</span>
							<?php endif; ?>

						<?php elseif ($item->state === 2): // Archivée ?>

							<?php // Comparer avec la version active ?>
							<?php if ($this->activeVersionId): ?>
							<a href="#" onclick="openCompareVersions(<?php echo $this->activeVersionId; ?>, <?php echo $item->id; ?>); return false;"
							   class="btn btn-sm btn-outline-secondary me-1"
							   data-bs-toggle="tooltip" title="<?php echo Text::_('COM_COLLECTOR_ITEM_HISTORY_COMPARE_WITH_ACTIVE'); ?>">
								<span class="icon-eye"></span>
							</a>
							<?php endif; ?>

							<?php // Rétablir ?>
							<a href="<?php echo Route::_($baseUrl . '&task=itemversions.restore', false); ?>"
							   class="btn btn-sm btn-outline-primary"
							   data-bs-toggle="tooltip" title="<?php echo Text::_('COM_COLLECTOR_ITEM_HISTORY_RESTORE_THIS'); ?>">
								<span class="icon-undo"></span>
							</a>

						<?php endif; ?>
						</td>
						<td class="d-none d-md-table-cell">
							<?php echo $this->escape($item->author_name); ?>
						</td>
						<td>
							<?php echo $item->modification; ?>
						</td>
						<td class="d-none d-lg-table-cell">
							<?php echo $item->id; ?>
						</td>
					</tr>
					<?php endforeach; ?>
				</tbody>
			</table>
		<?php endif; ?>
		<?php echo $this->pagination->getListFooter(); ?>

		<input type="hidden" name="task" value="" />
		<input type="hidden" name="boxchecked" value="0" />
		<?php echo HTMLHelper::_( 'form.token' ); ?>
	</div>
</form>


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
            <div class="modal-body px-4 py-3">
                <p class="text-muted small mb-3">
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
                <button type="button" class="btn btn-danger d-flex align-items-center gap-1" onclick="doRefuse()">
                    <span class="icon-cancel me-1" aria-hidden="true"></span>
                    <?php echo Text::_('COM_COLLECTOR_ITEM_HISTORY_REFUSE'); ?>
                </button>
            </div>
        </div>
    </div>
</div>