<?php
defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Router\Route;

$typeLabels = [
    0 => Text::_('COM_COLLECTOR_USERLIST_TYPE_0'),
    1 => Text::_('COM_COLLECTOR_USERLIST_TYPE_1'),
    2 => Text::_('COM_COLLECTOR_USERLIST_TYPE_2'),
];

$accessLabels = [
    0 => Text::_('COM_COLLECTOR_USERLIST_ACCESS_PRIVATE'),
    1 => Text::_('COM_COLLECTOR_USERLIST_ACCESS_MATCH_ONLY'),
    2 => Text::_('COM_COLLECTOR_USERLIST_ACCESS_PUBLIC'),
];
?>

<div class="row">

  <?php /* ── Cartes indicateurs globaux ──────────────────────────────────── */ ?>
  <div class="col-12 mb-4">
    <div class="row">
      <div class="col-md-3">
        <div class="card text-center">
          <div class="card-body">
            <div class="display-6"><?php echo (int) $this->stats->active_users; ?></div>
            <div class="text-muted small"><?php echo Text::_('COM_COLLECTOR_USERLISTSTATS_ACTIVE_USERS'); ?></div>
          </div>
        </div>
      </div>
      <?php foreach ([2, 0, 1] as $type): ?>
      <div class="col-md-3">
        <div class="card text-center">
          <div class="card-body">
            <div class="display-6"><?php echo (int) $this->stats->items_by_type[$type]; ?></div>
            <div class="text-muted small"><?php echo $typeLabels[$type]; ?></div>
          </div>
        </div>
      </div>
      <?php endforeach; ?>
    </div>
  </div>

  <?php /* ── Répartition d'accès par type ────────────────────────────────── */ ?>
  <div class="col-12 mb-4">
    <div class="card">
      <div class="card-header"><?php echo Text::_('COM_COLLECTOR_USERLISTSTATS_ACCESS_REPARTITION'); ?></div>
      <div class="card-body">
        <table class="table table-sm mb-0">
          <thead>
            <tr>
              <th><?php echo Text::_('COM_COLLECTOR_USERLISTSTATS_LIST_TYPE'); ?></th>
              <?php foreach ($accessLabels as $label): ?>
              <th class="text-center"><?php echo $label; ?></th>
              <?php endforeach; ?>
            </tr>
          </thead>
          <tbody>
            <?php foreach ([2, 0, 1] as $type): ?>
            <tr>
              <td><?php echo $typeLabels[$type]; ?></td>
              <?php foreach ([0, 1, 2] as $access): ?>
              <?php
                $count = 0;
                foreach ($this->stats->access_by_type as $row) {
                    if ((int) $row->type === $type && (int) $row->access === $access) {
                        $count = (int) $row->cnt;
                        break;
                    }
                }
              ?>
              <td class="text-center"><?php echo $count; ?></td>
              <?php endforeach; ?>
            </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>

  <?php /* ── Top utilisateurs par type ───────────────────────────────────── */ ?>
  <?php foreach ([2, 0, 1] as $type): ?>
  <?php if (!empty($this->topUsersByType[$type])): ?>
  <div class="col-md-4 mb-4">
    <div class="card h-100">
      <div class="card-header">
        <?php echo Text::sprintf('COM_COLLECTOR_USERLISTSTATS_TOP_USERS', $typeLabels[$type]); ?>
      </div>
      <ul class="list-group list-group-flush">
        <?php foreach ($this->topUsersByType[$type] as $row): ?>
        <li class="list-group-item d-flex justify-content-between align-items-center">
          <?php echo $this->escape($row->name); ?>
          <span class="badge bg-primary"><?php echo (int) $row->cnt; ?></span>
        </li>
        <?php endforeach; ?>
      </ul>
    </div>
  </div>
  <?php endif; ?>
  <?php endforeach; ?>

  <?php /* ── Top objets par type ──────────────────────────────────────────── */ ?>
  <?php foreach ([0, 1] as $type): ?>
  <?php if (!empty($this->topItemsByType[$type])): ?>
  <div class="col-md-6 mb-4">
    <div class="card h-100">
      <div class="card-header">
        <?php echo Text::sprintf('COM_COLLECTOR_USERLISTSTATS_TOP_ITEMS', $typeLabels[$type]); ?>
      </div>
      <ul class="list-group list-group-flush">
        <?php foreach ($this->topItemsByType[$type] as $row): ?>
        <li class="list-group-item d-flex justify-content-between align-items-center">
          <a href="<?php echo Route::_('index.php?option=com_collector&task=item.edit&collection='
              . (int) $this->collection->id . '&id=' . (int) $row->id); ?>">
            <?php echo $this->escape($row->fulltitle); ?>
          </a>
          <span class="badge bg-secondary"><?php echo (int) $row->cnt; ?></span>
        </li>
        <?php endforeach; ?>
      </ul>
    </div>
  </div>
  <?php endif; ?>
  <?php endforeach; ?>

  <?php /* ── Objets avec matchs potentiels ───────────────────────────────── */ ?>
  <?php if (!empty($this->matchingItems)): ?>
  <div class="col-12 mb-4">
    <div class="card">
      <div class="card-header"><?php echo Text::_('COM_COLLECTOR_USERLISTSTATS_MATCHING_ITEMS'); ?></div>
      <div class="card-body p-0">
        <table class="table table-sm mb-0">
          <thead>
            <tr>
              <th><?php echo Text::_('JGLOBAL_TITLE'); ?></th>
              <th class="text-center"><?php echo $typeLabels[0]; ?></th>
              <th class="text-center"><?php echo $typeLabels[1]; ?></th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($this->matchingItems as $row): ?>
            <tr>
              <td>
                <a href="<?php echo Route::_('index.php?option=com_collector&task=item.edit&collection='
                    . (int) $this->collection->id . '&id=' . (int) $row->id); ?>">
                  <?php echo $this->escape($row->fulltitle); ?>
                </a>
              </td>
              <td class="text-center"><?php echo (int) $row->want_count; ?></td>
              <td class="text-center"><?php echo (int) $row->have_count; ?></td>
            </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>
  <?php endif; ?>

  <?php /* ── Activité par mois ────────────────────────────────────────────── */ ?>
  <?php if (!empty($this->activity)): ?>
  <div class="col-12 mb-4">
    <div class="card">
      <div class="card-header"><?php echo Text::_('COM_COLLECTOR_USERLISTSTATS_ACTIVITY'); ?></div>
      <div class="card-body p-0">
        <table class="table table-sm mb-0">
          <thead>
            <tr>
              <th><?php echo Text::_('COM_COLLECTOR_USERLISTSTATS_MONTH'); ?></th>
              <th class="text-center"><?php echo Text::_('COM_COLLECTOR_USERLISTSTATS_ADDITIONS'); ?></th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($this->activity as $row): ?>
            <tr>
              <td><?php echo $this->escape($row->month); ?></td>
              <td class="text-center"><?php echo (int) $row->cnt; ?></td>
            </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>
  <?php endif; ?>

</div>