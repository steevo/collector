<?php defined('_JEXEC') or die;

use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Router\Route;
use Joomla\CMS\Session\Session;

$token      = Session::getFormToken();
$collection = $this->collectionId;
?>

<div class="container-fluid">
  <div class="row">
    <div class="col-12">

      <?php if (empty($this->imports)): ?>
      <div class="alert alert-info">
        <span class="icon-info-circle" aria-hidden="true"></span><span class="visually-hidden"><?php echo Text::_('INFO'); ?></span>
        <?php echo Text::_('COM_COLLECTOR_IMPORTHISTORY_EMPTY'); ?>
      </div>

      <?php else: ?>
      <table class="table table-striped table-hover">
        <thead class="table-light">
          <tr>
            <th><?php echo Text::_('JDATE'); ?></th>
            <th><?php echo Text::_('COM_COLLECTOR_IMPORTHISTORY_FILE'); ?></th>
            <th><?php echo Text::_('COM_COLLECTOR_IMPORTHISTORY_TYPE'); ?></th>
            <th class="text-center"><?php echo Text::_('COM_COLLECTOR_IMPORT_CNT_IMPORTED'); ?></th>
            <th class="text-center"><?php echo Text::_('COM_COLLECTOR_IMPORT_CNT_MERGED'); ?></th>
            <th class="text-center"><?php echo Text::_('COM_COLLECTOR_IMPORT_CNT_SKIPPED'); ?></th>
            <th class="text-center"><?php echo Text::_('COM_COLLECTOR_IMPORT_CNT_ERRORS'); ?></th>
            <th><?php echo Text::_('COM_COLLECTOR_IMPORTHISTORY_USER'); ?></th>
            <th></th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($this->imports as $import): ?>
          <tr>
            <td class="small">
              <?php echo HTMLHelper::_('date', $import->created, Text::_('DATE_FORMAT_LC2')); ?>
            </td>
            <td class="small">
              <span class="icon-file-excel text-success me-1"></span>
              <?php echo htmlspecialchars($import->file, ENT_QUOTES); ?>
            </td>
            <td>
              <?php if ($import->dry_run): ?>
              <span class="badge bg-warning text-dark">
                <span class="icon-eye me-1"></span>
                <?php echo Text::_('COM_COLLECTOR_IMPORTHISTORY_SIMULATION'); ?>
              </span>
              <?php else: ?>
              <span class="badge bg-success">
                <span class="icon-upload me-1"></span>
                <?php echo Text::_('COM_COLLECTOR_IMPORTHISTORY_REAL'); ?>
              </span>
              <?php endif; ?>
            </td>
            <td class="text-center">
              <span class="badge bg-success"><?php echo (int) $import->imported; ?></span>
            </td>
            <td class="text-center">
              <span class="badge bg-primary"><?php echo (int) $import->merged; ?></span>
            </td>
            <td class="text-center">
              <span class="badge bg-secondary"><?php echo (int) $import->skipped; ?></span>
            </td>
            <td class="text-center">
              <?php if ($import->errors > 0): ?>
              <span class="badge bg-danger"><?php echo (int) $import->errors; ?></span>
              <?php else: ?>
              <span class="text-muted">—</span>
              <?php endif; ?>
            </td>
            <td class="small text-muted">
              <?php echo htmlspecialchars($import->created_by_name ?? '', ENT_QUOTES); ?>
            </td>
            <td class="text-end text-nowrap">
              <?php if (!$import->dry_run): ?>
              <a href="<?php echo Route::_('index.php?option=com_collector&task=importhistory.detail&import_id=' . $import->id . '&collection=' . $collection, false); ?>"
                 class="btn btn-sm btn-outline-primary me-1">
                <span class="icon-search me-1"></span>
                <?php echo Text::_('COM_COLLECTOR_IMPORTHISTORY_DETAIL_BTN'); ?>
              </a>
              <?php endif; ?>
              <a href="<?php echo Route::_('index.php?option=com_collector&task=importhistory.delete&import_id=' . $import->id . '&collection=' . $collection . '&' . $token . '=1', false); ?>"
                 class="btn btn-sm btn-outline-danger"
                 onclick="return confirm('<?php echo Text::_('COM_COLLECTOR_IMPORTHISTORY_DELETE_CONFIRM'); ?>');">
                <span class="icon-trash me-1"></span>
                <?php echo Text::_('JACTION_DELETE'); ?>
              </a>
            </td>
          </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
      <?php endif; ?>

    </div>
  </div>
</div>