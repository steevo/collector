<?php
defined('_JEXEC') or die;

use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Router\Route;
use Joomla\CMS\Session\Session;
use Joomla\CMS\Factory;

$token      = Session::getFormToken();
$collection = $this->collection->id;
$itemId     = $this->item->id ?? 0;
$user       = Factory::getApplication()->getIdentity();
$canEdit    = $user->authorise('core.edit', 'com_collector.collection.' . $collection)
           || $user->authorise('core.edit', 'com_collector');

$baseUrl = 'index.php?option=com_collector&collection=' . $collection
         . '&item=' . $itemId . '&' . $token . '=1';
		 
HTMLHelper::_('bootstrap.modal');
?>

<div class="collector-itemversions">

  <?php /* ── Titre ─────────────────────────────────────────────────────── */ ?>
  <h2>
    <?php echo Text::sprintf('COM_COLLECTOR_ITEM_VERSIONS_TITLE',
        $this->escape($this->item->fulltitle ?: '#' . $itemId)); ?>
  </h2>

  <?php /* ── Bouton retour ────────────────────────────────────────────── */ ?>
  <p class="mb-3">
    <a href="<?php echo Route::_('index.php?option=com_collector&view=item&collection='
              . $collection . '&id=' . $itemId); ?>"
       class="btn btn-sm btn-outline-secondary">
      <span class="icon-arrow-left me-1"></span>
      <?php echo Text::_('COM_COLLECTOR_RETURN_TO_ITEM'); ?>
    </a>
    <a href="<?php echo Route::_('index.php?option=com_collector&view=itemstovalidate&id='
              . $collection); ?>"
       class="btn btn-sm btn-outline-secondary ms-1">
      <span class="icon-list me-1"></span>
      <?php echo Text::_('COM_COLLECTOR_RETURN_TO_VALIDATE_LIST'); ?>
    </a>
  </p>

  <?php if (empty($this->items)): ?>
    <div class="alert alert-info">
      <?php echo Text::_('COM_COLLECTOR_ITEM_HISTORY_EMPTY'); ?>
    </div>

  <?php else: ?>
  <table class="table table-sm table-hover">
    <thead class="table-light">
      <tr>
        <th><?php echo Text::_('JSTATUS'); ?></th>
        <th><?php echo Text::_('JDATE'); ?></th>
        <th><?php echo Text::_('JAUTHOR'); ?></th>
        <th><?php echo Text::_('COM_COLLECTOR_ITEM_HISTORY_MODIFICATION'); ?></th>
        <?php if ($canEdit): ?>
        <th><?php echo Text::_('COM_COLLECTOR_ACTIONS'); ?></th>
        <?php endif; ?>
      </tr>
    </thead>
    <tbody>
      <?php foreach ($this->items as $version): ?>
      <?php
        $isPending  = ((int) $version->state === 0);
        $isActive   = ((int) $version->state === 1);
        $isArchived = ((int) $version->state === 2);
        $isOldest   = ($version->id === $this->oldestPendingId);
        $rowClass   = $isPending ? 'table-warning' : ($isActive ? 'table-success' : '');

        $versionUrl = Route::_(
            'index.php?option=com_collector&task=itemversions.validate'
            . '&collection=' . $collection
            . '&item=' . $itemId
            . '&version_id=' . $version->id
            . '&' . $token . '=1',
            false
        );
      ?>
      <tr class="<?php echo $rowClass; ?>">
        <td class="text-nowrap">
          <?php if ($isPending): ?>
            <span class="badge bg-warning text-dark">
              <span class="icon-clock me-1"></span>
              <?php echo Text::_('COM_COLLECTOR_ITEM_HISTORY_PENDING'); ?>
            </span>
          <?php elseif ($isActive): ?>
            <span class="badge bg-success">
              <span class="icon-check me-1"></span>
              <?php echo Text::_('COM_COLLECTOR_ITEM_HISTORY_ACTIVE'); ?>
            </span>
          <?php else: ?>
            <span class="badge bg-secondary">
              <span class="icon-archive me-1"></span>
              <?php echo Text::_('COM_COLLECTOR_ITEM_HISTORY_ARCHIVED'); ?>
            </span>
          <?php endif; ?>
		  <?php if ($isArchived && !empty($version->refuse_note)): ?>
			<div class="small text-muted fst-italic mt-1">
				<span class="icon-info-circle me-1"></span>
				<?php echo htmlspecialchars($version->refuse_note, ENT_QUOTES); ?>
			</div>
		  <?php endif; ?>
        </td>
        <td class="small">
          <?php echo HTMLHelper::_('date', $version->modified, Text::_('DATE_FORMAT_LC2')); ?>
        </td>
        <td class="small">
          <?php echo $this->escape($version->modified_by_name ?? ''); ?>
        </td>
        <td class="small">
          <?php echo $this->escape($version->modification ?? ''); ?>
        </td>
        <?php if ($canEdit): ?>
        <td class="text-nowrap">
          <?php if ($isPending && $isOldest): ?>
		  
            <?php /* Comparer avec la version active */ ?>
			<?php if ($this->activeVersionId): ?>
			<button type="button"
					onclick="openCompareVersions(<?php echo $this->activeVersionId; ?>, <?php echo $version->id; ?>)"
					class="btn btn-sm btn-outline-secondary me-1"
					title="<?php echo Text::_('COM_COLLECTOR_ITEM_HISTORY_COMPARE_WITH_ACTIVE'); ?>">
				<span class="icon-eye"></span>
			</button>
			<?php endif; ?>
			
            <?php /* Valider */ ?>
            <a href="<?php echo Route::_(
                'index.php?option=com_collector&task=itemversions.validate'
                . '&collection=' . $collection . '&item=' . $itemId
                . '&version_id=' . $version->id . '&' . $token . '=1', false); ?>"
               class="btn btn-sm btn-success me-1"
               title="<?php echo Text::_('COM_COLLECTOR_ITEM_HISTORY_VALIDATE'); ?>">
              <span class="icon-check"></span>
            </a>

            <?php /* Refuser */ ?>
			<!-- Bouton refuser → ouvre modale note -->
			<button type="button"
					class="btn btn-sm btn-danger"
					title="<?php echo Text::_('COM_COLLECTOR_ITEM_HISTORY_REFUSE'); ?>"
					onclick="openRefuseModal(<?php echo $version->id; ?>)">
				<span class="icon-cancel"></span>
			</button>

          <?php elseif ($isPending && !$isOldest): ?>

            <span class="text-muted small"
                  title="<?php echo Text::_('COM_COLLECTOR_ITEM_HISTORY_VALIDATE_BLOCKED'); ?>">
              <span class="icon-lock"></span>
            </span>

          <?php elseif ($isArchived): ?>

			<?php /* Comparer avec la version active */ ?>
			<?php if ($this->activeVersionId): ?>
			<button type="button"
					onclick="openCompareVersions(<?php echo $this->activeVersionId; ?>, <?php echo $version->id; ?>)"
					class="btn btn-sm btn-outline-secondary me-1"
					title="<?php echo Text::_('COM_COLLECTOR_ITEM_HISTORY_COMPARE_WITH_ACTIVE'); ?>">
				<span class="icon-eye"></span>
			</button>
			<?php endif; ?>
			
            <?php /* Rétablir */ ?>
            <a href="<?php echo Route::_(
                'index.php?option=com_collector&task=itemversions.restore'
                . '&collection=' . $collection . '&item=' . $itemId
                . '&version_id=' . $version->id . '&' . $token . '=1', false); ?>"
               class="btn btn-sm btn-outline-primary"
               title="<?php echo Text::_('COM_COLLECTOR_ITEM_HISTORY_RESTORE_THIS'); ?>">
              <span class="icon-undo"></span>
            </a>

          <?php endif; ?>
        </td>
        <?php endif; ?>
      </tr>
      <?php endforeach; ?>
    </tbody>
  </table>

  <?php echo $this->pagination->getListFooter(); ?>
  <?php endif; ?>
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

<script>
var refuseVersionId = 0;
var refuseModal     = null;

document.addEventListener('DOMContentLoaded', function() {
    const modalEl = document.getElementById('modal-refuse');
    if (modalEl) {
        refuseModal = new bootstrap.Modal(modalEl);
    }
});

function openRefuseModal(versionId) {
    refuseVersionId = versionId;
    document.getElementById('refuse-note-input').value = '';
    refuseModal.show();
}

function doRefuse() {
    const note       = document.getElementById('refuse-note-input').value;
    const collection = <?php echo (int) ($this->collection->id ?? 0); ?>;
    const item       = <?php echo (int) ($this->item->id       ?? 0); ?>;
    const token      = '<?php echo Session::getFormToken(); ?>';

    const url = '<?php echo \Joomla\CMS\Uri\Uri::base(true); ?>/index.php?option=com_collector&task=itemversions.refuse'
              + '&collection=' + collection
              + '&item='       + item
              + '&version_id=' + refuseVersionId
              + '&refuse_note=' + encodeURIComponent(note)
              + '&' + token + '=1';

    window.location.href = url;
}

function openCompareVersions(v1, v2) {
    const collection = <?php echo (int) ($this->collection->id ?? 0); ?>;
    const item       = <?php echo (int) ($this->item->id       ?? 0); ?>;
    const url = '<?php echo \Joomla\CMS\Uri\Uri::base(true); ?>/index.php?option=com_collector&view=versionscompare&tmpl=component'
              + '&collection=' + collection
              + '&item='       + item
              + '&v1='         + v1
              + '&v2='         + v2;
    window.open(url, 'compare', 'width=900,height=700,scrollbars=yes,resizable=yes');
}
</script>