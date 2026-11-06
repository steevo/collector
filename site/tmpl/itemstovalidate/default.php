<?php
/**
 * @package    Collector
 * @copyright  Copyright (C) 2010 - 2020 Philippe Ousset. All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 */

defined('_JEXEC') or die('Restricted access');

use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Router\Route;
use Joomla\CMS\Uri\Uri;

HTMLHelper::_('bootstrap.tooltip');
?>

<div class="collector-tovalidate<?php echo $this->pageclass_sfx ?? ''; ?>">

  <?php /* ── Titre ────────────────────────────────────────────────────────── */ ?>
  <h2>
    <?php echo Text::sprintf('COM_COLLECTOR_ITEMS_TO_VALIDATE_TITLE', $this->escape($this->collection->name)); ?>
  </h2>

  <?php /* ── Retour à la collection ──────────────────────────────────────── */ ?>
  <p class="mb-3">
    <a href="<?php echo Route::_('index.php?option=com_collector&view=collection&id=' . $this->collection->id); ?>"
       class="btn btn-sm btn-outline-secondary">
      <span class="icon-arrow-left me-1" aria-hidden="true"></span>
      <?php echo Text::_('COM_COLLECTOR_RETURN_TO_LIST'); ?>
    </a>
  </p>

  <?php if (empty($this->items)): ?>
    <div class="alert alert-success">
      <span class="icon-check-circle me-1" aria-hidden="true"></span>
      <?php echo Text::_('COM_COLLECTOR_NO_ITEMS_TO_VALIDATE'); ?>
    </div>
  <?php else: ?>
    <p class="text-muted small mb-2">
      <?php echo Text::plural('COM_COLLECTOR_ITEMS_TO_VALIDATE_COUNT', count($this->items)); ?>
    </p>

    <div class="list-group">
    <?php foreach ($this->items as $item):

      // Construire le titre à partir des champs de titre de la collection
      $title = '';
      foreach ($this->fields as $field) {
        $col = $field->_field->tablecolumn;
        if ($title !== '') $title .= ' ';
        $title .= $field->displayInTitle($item->$col ?? '');
      }
      if ($title === '') {
        $title = $item->fulltitle ?: '#' . $item->id;
      }

      $versionsUrl = Route::_(
			'index.php?option=com_collector&view=itemversions'
			. '&collection=' . (int) $item->collection
			. '&item=' . (int) $item->id
		);
    ?>
      <a href="<?php echo $versionsUrl; ?>"
         class="list-group-item list-group-item-action d-flex justify-content-between align-items-center">

        <span>
          <?php echo $this->escape($title); ?>
        </span>

        <?php /* Badge orange : nombre de versions à valider */ ?>
        <span class="badge bg-warning text-dark"
              title="<?php echo Text::plural('COM_COLLECTOR_ITEM_HISTORY_N_TO_VALIDATE', (int) $item->to_validate); ?>"
              data-bs-toggle="tooltip">
          <span class="icon-clock me-1" aria-hidden="true"></span><?php echo (int) $item->to_validate; ?>
        </span>

      </a>
    <?php endforeach; ?>
    </div>

    <?php echo $this->pagination->getListFooter(); ?>

  <?php endif; ?>

</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    // Activation des tooltips Bootstrap
    document.querySelectorAll('[data-bs-toggle="tooltip"]').forEach(function (el) {
        new bootstrap.Tooltip(el);
    });
});
</script>