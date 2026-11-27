<?php
/**
 * @package    Collector
 * @copyright  Copyright (C) 2010 - 2026 Philippe Ousset. All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 *
 * Versions d'un objet (validation, refus, comparaison, rétablissement) : même présentation que les
 * autres tableaux de Collector (couleurs du template, mode sombre, fiches sur mobile).
 */

defined('_JEXEC') or die;

use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Router\Route;
use Joomla\CMS\Session\Session;
use Joomla\CMS\Factory;
use Joomla\CMS\Uri\Uri;

$token      = Session::getFormToken();
$collection = $this->collection->id;
$itemId     = $this->item->id ?? 0;
$user       = Factory::getApplication()->getIdentity();
// Lien de menu d'où l'on vient (« Bulletins à valider »…) : transmis aux adresses construites en JavaScript
// (refus, fenêtre de comparaison), pour que le retour après validation garde ce menu au lieu de l'accueil
$menuItemid = (int) (Factory::getApplication()->getMenu()->getActive()->id ?? 0);
$canEdit    = $user->authorise('core.edit', 'com_collector.collection.' . $collection)
           || $user->authorise('core.edit', 'com_collector');

HTMLHelper::stylesheet(Uri::base() . 'components/com_collector/assets/css/collection.css', ['version' => 'auto']);

// Comparaison de versions : fenêtre intégrée à la page (media/js/version-dialog.js)
$wa = $this->document->getWebAssetManager();
$wa->getRegistry()->addExtensionRegistryFile('com_collector');
$wa->useScript('com_collector.version-dialog');
HTMLHelper::_('bootstrap.modal');

$labels = [
    'status'       => Text::_('JSTATUS'),
    'date'         => Text::_('JDATE'),
    'author'       => Text::_('JAUTHOR'),
    'modification' => Text::_('COM_COLLECTOR_ITEM_HISTORY_MODIFICATION'),
    'actions'      => Text::_('COM_COLLECTOR_ACTIONS'),
];

// Bouton carré à icône (comme le crayon et la corbeille des listes), libellé au survol
$iconButton = static function (string $tag, string $class, string $icon, string $label, string $attr): string {
    $label = htmlspecialchars($label, ENT_QUOTES, 'UTF-8');

    return '<' . $tag . ' class="btn btn-sm ' . $class . ' collector-icon-btn" title="' . $label . '" aria-label="' . $label . '" ' . $attr . '>'
        . '<span class="' . $icon . '" aria-hidden="true"></span></' . $tag . '>';
};
$taskUrl = fn (string $task, int $versionId) => Route::_('index.php?option=com_collector&task=itemversions.' . $task
    . '&collection=' . $collection . '&item=' . $itemId . '&version_id=' . $versionId . '&' . $token . '=1', false);
?>

<div class="collector-itemversions category-list">

  <h2>
    <?php echo Text::sprintf('COM_COLLECTOR_ITEM_VERSIONS_TITLE', $this->escape($this->item->fulltitle ?: '#' . $itemId)); ?>
  </h2>

  <p class="d-flex flex-wrap gap-2 mb-3">
    <a href="<?php echo Route::_('index.php?option=com_collector&view=item&collection=' . $collection . '&id=' . $itemId); ?>"
       class="btn btn-sm btn-outline-secondary">
      <span class="icon-arrow-left me-1" aria-hidden="true"></span>
      <?php echo Text::_('COM_COLLECTOR_RETURN_TO_ITEM'); ?>
    </a>
    <a href="<?php echo Route::_('index.php?option=com_collector&view=itemstovalidate&id=' . $collection); ?>"
       class="btn btn-sm btn-outline-secondary">
      <span class="icon-list me-1" aria-hidden="true"></span>
      <?php echo Text::_('COM_COLLECTOR_RETURN_TO_VALIDATE_LIST'); ?>
    </a>
  </p>

  <?php if (empty($this->items)): ?>
    <div class="alert alert-info">
      <?php echo Text::_('COM_COLLECTOR_ITEM_HISTORY_EMPTY'); ?>
    </div>

  <?php else: ?>
  <table class="category table table-striped table-bordered table-hover collector-table-stack collector-versions">
    <thead>
      <tr>
        <th><?php echo $labels['status']; ?></th>
        <th><?php echo $labels['date']; ?></th>
        <th><?php echo $labels['author']; ?></th>
        <th><?php echo $labels['modification']; ?></th>
        <?php if ($canEdit): ?>
        <th class="text-end"><?php echo $labels['actions']; ?></th>
        <?php endif; ?>
      </tr>
    </thead>
    <tbody>
      <?php foreach ($this->items as $version): ?>
      <?php
        $isPending  = ((int) $version->state === 0);
        $isActive   = ((int) $version->state === 1);
        $isArchived = ((int) $version->state === 2);
        $isRefused  = ((int) $version->state === 3);
        $isOldest   = ($version->id === $this->oldestPendingId);
        $compare    = $this->activeVersionId
            ? $iconButton('button', 'btn-outline-secondary', 'icon-eye', Text::_('COM_COLLECTOR_ITEM_HISTORY_COMPARE_WITH_ACTIVE'),
                'type="button" onclick="openCompareVersions(' . (int) $this->activeVersionId . ', ' . (int) $version->id . ')"')
            : '';
      ?>
      <tr class="<?php echo $isActive ? 'collector-version-active' : ''; ?>">
        <td data-label="<?php echo $this->escape($labels['status']); ?>" style="vertical-align:middle">
          <span>
          <?php if ($isPending): ?>
            <span class="badge collector-status collector-status-pending">
              <span class="icon-clock me-1" aria-hidden="true"></span><?php echo Text::_('COM_COLLECTOR_ITEM_HISTORY_PENDING'); ?>
            </span>
          <?php elseif ($isActive): ?>
            <span class="badge collector-status collector-status-active">
              <span class="icon-check me-1" aria-hidden="true"></span><?php echo Text::_('COM_COLLECTOR_ITEM_HISTORY_ACTIVE'); ?>
            </span>
          <?php elseif ($isRefused): ?>
            <span class="badge collector-status collector-status-refused">
              <span class="icon-cancel me-1" aria-hidden="true"></span><?php echo Text::_('COM_COLLECTOR_ITEM_HISTORY_REFUSED_STATE'); ?>
            </span>
          <?php else: ?>
            <span class="badge collector-status collector-status-archived">
              <span class="icon-archive me-1" aria-hidden="true"></span><?php echo Text::_('COM_COLLECTOR_ITEM_HISTORY_ARCHIVED'); ?>
            </span>
          <?php endif; ?>
          <?php if (($isArchived || $isRefused) && !empty($version->refuse_note)): ?>
            <span class="d-block small text-muted fst-italic mt-1">
              <span class="icon-info-circle me-1" aria-hidden="true"></span><?php echo $this->escape($version->refuse_note); ?>
            </span>
          <?php endif; ?>
          </span>
        </td>
        <td data-label="<?php echo $this->escape($labels['date']); ?>" style="vertical-align:middle">
          <?php echo HTMLHelper::_('date', $version->modified, Text::_('DATE_FORMAT_LC2')); ?>
        </td>
        <td data-label="<?php echo $this->escape($labels['author']); ?>" style="vertical-align:middle">
          <?php echo $this->escape($version->modified_by_name ?? ''); ?>
        </td>
        <td data-label="<?php echo $this->escape($labels['modification']); ?>"<?php echo trim((string) ($version->modification ?? '')) === '' ? ' class="collector-cell-empty"' : ''; ?> style="vertical-align:middle">
          <?php echo $this->escape($version->modification ?? ''); ?>
        </td>
        <?php if ($canEdit): ?>
        <td data-label="" class="text-end text-nowrap" style="vertical-align:middle">
          <span class="collector-version-actions">
          <?php if ($isPending && $isOldest): ?>
            <?php echo $compare; ?>
            <?php echo $iconButton('a', 'btn-outline-success', 'icon-check', Text::_('COM_COLLECTOR_ITEM_HISTORY_VALIDATE'),
                'href="' . $taskUrl('validate', (int) $version->id) . '"'); ?>
            <?php echo $iconButton('button', 'btn-outline-danger', 'icon-cancel', Text::_('COM_COLLECTOR_ITEM_HISTORY_REFUSE'),
                'type="button" onclick="openRefuseModal(' . (int) $version->id . ')"'); ?>
          <?php elseif ($isPending): ?>
            <span class="text-muted" title="<?php echo Text::_('COM_COLLECTOR_ITEM_HISTORY_VALIDATE_BLOCKED'); ?>">
              <span class="icon-lock" aria-hidden="true"></span>
              <span class="visually-hidden"><?php echo Text::_('COM_COLLECTOR_ITEM_HISTORY_VALIDATE_BLOCKED'); ?></span>
            </span>
          <?php elseif ($isArchived): ?>
            <?php echo $compare; ?>
            <?php echo $iconButton('a', 'btn-outline-secondary', 'icon-undo', Text::_('COM_COLLECTOR_ITEM_HISTORY_RESTORE_THIS'),
                'href="' . $taskUrl('restore', (int) $version->id) . '"'); ?>
          <?php elseif ($isRefused): ?>
            <?php echo $compare; ?>
          <?php endif; ?>
          </span>
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
<div class="modal fade collector-modal" id="modal-refuse" tabindex="-1">
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

    const url = '<?php echo Uri::base(true); ?>/index.php?option=com_collector&task=itemversions.refuse'
              + '&collection=' + collection
              + '&item='       + item
              + '&version_id=' + refuseVersionId
              + '&refuse_note=' + encodeURIComponent(note)
              + '&' + token + '=1'
              + '<?php echo $menuItemid ? '&Itemid=' . $menuItemid : ''; ?>';

    window.location.href = url;
}

function openCompareVersions(v1, v2) {
    const collection = <?php echo (int) ($this->collection->id ?? 0); ?>;
    const item       = <?php echo (int) ($this->item->id       ?? 0); ?>;
    const url = '<?php echo Uri::base(true); ?>/index.php?option=com_collector&view=versionscompare&tmpl=component'
              + '&collection=' + collection
              + '&item='       + item
              + '&v1='         + v1
              + '&v2='         + v2
              + '<?php echo $menuItemid ? '&Itemid=' . $menuItemid : ''; ?>';
    Joomla.collectorVersionDialog.open(url);
}
</script>
