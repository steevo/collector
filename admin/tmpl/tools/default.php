<?php
defined('_JEXEC') or die;

use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\Factory;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Router\Route;
use Joomla\CMS\Session\Session;

HTMLHelper::_('bootstrap.modal');

$params              = ComponentHelper::getParams('com_collector');
$saveHistoryDisabled = !$params->get('save_history', 1);
$token               = Session::getFormToken();
?>

<?php /* Outil de migration ponctuelle */ ?>
<?php if (!empty($this->collectionsToMigrate)): ?>
<div class="alert alert-warning d-flex justify-content-between align-items-center mb-3">
    <div>
        <strong><?php echo Text::_('COM_COLLECTOR_TOOLS_MIGRATE_FOLDERS_TITLE'); ?></strong>
        <p class="small mb-0">
            <?php echo Text::sprintf('COM_COLLECTOR_TOOLS_MIGRATE_FOLDERS_DESC', $this->collectionsToMigrate); ?>
        </p>
    </div>
    <a href="<?php echo Route::_('index.php?option=com_collector&task=tools.migratecollectionfolders'
        . '&' . $token . '=1'); ?>"
       class="btn btn-warning btn-sm"
       onclick="return confirm('<?php echo Text::_('COM_COLLECTOR_TOOLS_MIGRATE_FOLDERS_CONFIRM'); ?>')">
        <span class="icon-refresh me-1"></span>
        <?php echo Text::_('COM_COLLECTOR_TOOLS_MIGRATE_FOLDERS_BUTTON'); ?>
    </a>
</div>
<?php endif; ?>

<?php if (!empty($this->migrateReport)): ?>
<div class="card mb-3">
    <div class="card-header"><?php echo Text::_('COM_COLLECTOR_TOOLS_MIGRATE_FOLDERS_REPORT'); ?></div>
    <table class="table table-sm mb-0">
        <thead>
            <tr>
                <th><?php echo Text::_('COM_COLLECTOR_FILES_CONTEXT_COLLECTIONS'); ?></th>
                <th><?php echo Text::_('JSTATUS'); ?></th>
                <th><?php echo Text::_('JGLOBAL_DESCRIPTION'); ?></th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($this->migrateReport as $row): ?>
            <tr>
                <td><?php echo $this->escape($row->collection); ?></td>
                <td>
                    <?php
                    $badgeClass = match($row->status) {
                        'success' => 'bg-success',
                        'error'   => 'bg-danger',
                        default   => 'bg-secondary',
                    };
                    ?>
                    <span class="badge <?php echo $badgeClass; ?>"><?php echo $row->status; ?></span>
                </td>
                <td class="small"><?php echo $this->escape($row->message); ?></td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>
<?php endif; ?>

<form action="<?php echo Route::_('index.php?option=com_collector&view=tools'); ?>"
      method="post" name="adminForm" id="adminForm">

    <?php /* ── Sélecteur de collection ─────────────────────────────────── */ ?>
    <div class="row mb-4">
        <div class="col-md-4">
            <select name="collection" class="form-select" onchange="this.form.submit()">
                <option value=""><?php echo Text::_('COM_COLLECTOR_CHOOSE_COLLECTION_LABEL'); ?></option>
                <?php foreach ($this->collections as $col): ?>
                <option value="<?php echo (int) $col->id; ?>"
                        <?php echo ($col->id == $this->collection) ? 'selected' : ''; ?>>
                    <?php echo $this->escape($col->name); ?>
                </option>
                <?php endforeach; ?>
            </select>
        </div>
    </div>

    <?php echo HTMLHelper::_('form.token'); ?>
</form>

<?php if ($this->collection): ?>

<?php
$activeTab = Factory::getApplication()->input->getString('active_tab', '#tab-integrity');
echo HTMLHelper::_('uitab.startTabSet', 'toolsTab', ['active' => $activeTab]);
?>

<?php // echo HTMLHelper::_('uitab.startTabSet', 'toolsTab', ['active' => '#tab-integrity']); ?>

<?php /* ══════════════════════════════════════════════════════════════════
   ONGLET 1 — Intégrité des données
═══════════════════════════════════════════════════════════════════════════ */ ?>
<?php echo HTMLHelper::_('uitab.addTab', 'toolsTab', '#tab-integrity', Text::_('COM_COLLECTOR_TOOLS_TAB_INTEGRITY')); ?>

<?php /* Items sans version active */ ?>
<div class="card mb-3">
    <div class="card-body d-flex justify-content-between align-items-center">
        <div>
            <h5 class="card-title mb-1"><?php echo Text::_('COM_COLLECTOR_TOOLS_MISSING_ACTIVE_TITLE'); ?></h5>
            <p class="card-text text-muted small mb-0">
                <?php echo Text::sprintf('COM_COLLECTOR_TOOLS_MISSING_ACTIVE_DESC', $this->itemsWithoutActiveVersion); ?>
            </p>
        </div>
        <a href="<?php echo Route::_('index.php?option=com_collector&task=tools.fixmissingactiveversions'
            . '&collection=' . (int) $this->collection . '&' . $token . '=1'); ?>"
           class="btn btn-primary btn-sm <?php echo $this->itemsWithoutActiveVersion === 0 ? 'disabled' : ''; ?>"
           onclick="return confirm('<?php echo Text::_('COM_COLLECTOR_TOOLS_CONFIRM'); ?>')">
            <span class="icon-wrench me-1"></span>
            <?php echo Text::_('COM_COLLECTOR_TOOLS_MISSING_ACTIVE_BUTTON'); ?>
        </a>
    </div>
</div>

<?php /* Versions orphelines */ ?>
<div class="card mb-3">
    <div class="card-body d-flex justify-content-between align-items-center">
        <div>
            <h5 class="card-title mb-1"><?php echo Text::_('COM_COLLECTOR_TOOLS_ORPHAN_VERSIONS_TITLE'); ?></h5>
            <p class="card-text text-muted small mb-0">
                <?php echo Text::sprintf('COM_COLLECTOR_TOOLS_ORPHAN_VERSIONS_DESC', $this->orphanVersions); ?>
            </p>
        </div>
        <a href="<?php echo Route::_('index.php?option=com_collector&task=tools.cleanorphanversions'
            . '&collection=' . (int) $this->collection . '&' . $token . '=1'); ?>"
           class="btn btn-outline-danger btn-sm <?php echo $this->orphanVersions === 0 ? 'disabled' : ''; ?>"
           onclick="return confirm('<?php echo Text::_('COM_COLLECTOR_TOOLS_CONFIRM'); ?>')">
            <span class="icon-trash me-1"></span>
            <?php echo Text::_('COM_COLLECTOR_TOOLS_ORPHAN_VERSIONS_BUTTON'); ?>
        </a>
    </div>
</div>

<?php /* Usersitems orphelins */ ?>
<div class="card mb-3">
    <div class="card-body d-flex justify-content-between align-items-center">
        <div>
            <h5 class="card-title mb-1"><?php echo Text::_('COM_COLLECTOR_TOOLS_ORPHAN_USERSITEMS_TITLE'); ?></h5>
            <p class="card-text text-muted small mb-0">
                <?php echo Text::sprintf('COM_COLLECTOR_TOOLS_ORPHAN_USERSITEMS_DESC', $this->orphanUsersitems); ?>
            </p>
        </div>
        <a href="<?php echo Route::_('index.php?option=com_collector&task=tools.cleanorphanusersitems'
            . '&collection=' . (int) $this->collection . '&' . $token . '=1'); ?>"
           class="btn btn-outline-danger btn-sm <?php echo $this->orphanUsersitems === 0 ? 'disabled' : ''; ?>"
           onclick="return confirm('<?php echo Text::_('COM_COLLECTOR_TOOLS_CONFIRM'); ?>')">
            <span class="icon-trash me-1"></span>
            <?php echo Text::_('COM_COLLECTOR_TOOLS_ORPHAN_USERSITEMS_BUTTON'); ?>
        </a>
    </div>
</div>

<?php /* Compteurs to_validate */ ?>
<div class="card mb-3">
    <div class="card-body d-flex justify-content-between align-items-center">
        <div>
            <h5 class="card-title mb-1"><?php echo Text::_('COM_COLLECTOR_TOOLS_TOVALIDATE_TITLE'); ?></h5>
            <p class="card-text text-muted small mb-0">
                <?php echo Text::sprintf('COM_COLLECTOR_TOOLS_TOVALIDATE_DESC', $this->toValidateMismatch); ?>
            </p>
        </div>
        <a href="<?php echo Route::_('index.php?option=com_collector&task=tools.fixtovalidatecounts'
            . '&collection=' . (int) $this->collection . '&' . $token . '=1'); ?>"
           class="btn btn-primary btn-sm <?php echo $this->toValidateMismatch === 0 ? 'disabled' : ''; ?>"
           onclick="return confirm('<?php echo Text::_('COM_COLLECTOR_TOOLS_CONFIRM'); ?>')">
            <span class="icon-calculator me-1"></span>
            <?php echo Text::_('COM_COLLECTOR_TOOLS_TOVALIDATE_BUTTON'); ?>
        </a>
    </div>
</div>

<?php /* Items sans collection valide (global) */ ?>
<?php if (!empty($this->invalidCollectionItems)): ?>
<div class="card mb-3 border-warning">
    <details>
        <summary class="card-header bg-warning-subtle d-flex justify-content-between align-items-center w-100"
                 style="cursor:pointer; list-style:none; user-select:none;">
            <span><?php echo Text::sprintf('COM_COLLECTOR_TOOLS_INVALID_COLLECTION_TITLE', count($this->invalidCollectionItems)); ?></span>
            <span class="details-arrow">▼</span>
        </summary>
        <div class="card-body p-0">
            <p class="text-muted small p-3 mb-0"><?php echo Text::_('COM_COLLECTOR_TOOLS_INVALID_COLLECTION_DESC'); ?></p>
            <table class="table table-sm mb-0">
                <thead>
                    <tr>
                        <th><?php echo Text::_('JGRID_HEADING_ID'); ?></th>
                        <th><?php echo Text::_('JGLOBAL_TITLE'); ?></th>
                        <th><?php echo Text::_('COM_COLLECTOR_TOOLS_INVALID_COLLECTION_REF'); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($this->invalidCollectionItems as $row): ?>
                    <tr>
                        <td><?php echo (int) $row->id; ?></td>
                        <td><?php echo $this->escape($row->fulltitle ?: '—'); ?></td>
                        <td><?php echo (int) $row->collection; ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </details>
</div>
<?php endif; ?>

<?php echo HTMLHelper::_('uitab.endTab'); ?>

<?php /* ══════════════════════════════════════════════════════════════════
   ONGLET 2 — Nettoyage
═══════════════════════════════════════════════════════════════════════════ */ ?>
<?php echo HTMLHelper::_('uitab.addTab', 'toolsTab', '#tab-cleanup', Text::_('COM_COLLECTOR_TOOLS_TAB_CLEANUP')); ?>

<?php /* Sessions d'import abandonnées */ ?>
<div class="card mb-3">
    <div class="card-body d-flex justify-content-between align-items-center">
        <div>
            <h5 class="card-title mb-1"><?php echo Text::_('COM_COLLECTOR_TOOLS_IMPORT_SESSIONS_TITLE'); ?></h5>
            <p class="card-text text-muted small mb-0">
                <?php echo Text::sprintf('COM_COLLECTOR_TOOLS_IMPORT_SESSIONS_DESC', $this->staleImportSessions); ?>
            </p>
        </div>
        <a href="<?php echo Route::_('index.php?option=com_collector&task=tools.cleanstaleimportsessions'
            . '&collection=' . (int) $this->collection . '&' . $token . '=1'); ?>"
           class="btn btn-outline-danger btn-sm <?php echo $this->staleImportSessions === 0 ? 'disabled' : ''; ?>"
           onclick="return confirm('<?php echo Text::_('COM_COLLECTOR_TOOLS_CONFIRM'); ?>')">
            <span class="icon-trash me-1"></span>
            <?php echo Text::_('COM_COLLECTOR_TOOLS_IMPORT_SESSIONS_BUTTON'); ?>
        </a>
    </div>
</div>

<?php /* Versions archivées résiduelles */ ?>
<?php if ($saveHistoryDisabled): ?>
<div class="card mb-3">
    <div class="card-body d-flex justify-content-between align-items-center">
        <div>
            <h5 class="card-title mb-1"><?php echo Text::_('COM_COLLECTOR_TOOLS_ARCHIVED_TITLE'); ?></h5>
            <p class="card-text text-muted small mb-0">
                <?php echo Text::sprintf('COM_COLLECTOR_TOOLS_ARCHIVED_DESC', $this->archivedToClean); ?>
            </p>
        </div>
        <a href="<?php echo Route::_('index.php?option=com_collector&task=tools.cleanarchivedversions'
            . '&collection=' . (int) $this->collection . '&' . $token . '=1'); ?>"
           class="btn btn-outline-danger btn-sm <?php echo $this->archivedToClean === 0 ? 'disabled' : ''; ?>"
           onclick="return confirm('<?php echo Text::_('COM_COLLECTOR_TOOLS_CONFIRM'); ?>')">
            <span class="icon-trash me-1"></span>
            <?php echo Text::_('COM_COLLECTOR_TOOLS_ARCHIVED_BUTTON'); ?>
        </a>
    </div>
</div>
<?php endif; ?>

<?php /* Champs définis orphelins (global) */ ?>
<?php if (!empty($this->orphanDefinedContent)): ?>
<div class="card mb-3 border-warning">
    <details>
        <summary class="card-header bg-warning-subtle d-flex justify-content-between align-items-center w-100"
                 style="cursor:pointer; list-style:none; user-select:none;">
            <span><?php echo Text::sprintf('COM_COLLECTOR_TOOLS_ORPHAN_DEFINED_TITLE', count($this->orphanDefinedContent)); ?></span>
            <span class="details-arrow">▼</span>
        </summary>
        <div class="card-body p-0">
            <p class="text-muted small p-3 mb-0"><?php echo Text::_('COM_COLLECTOR_TOOLS_ORPHAN_DEFINED_DESC'); ?></p>
            <table class="table table-sm mb-0">
                <thead>
                    <tr>
                        <th><?php echo Text::_('JGRID_HEADING_ID'); ?></th>
                        <th><?php echo Text::_('COM_COLLECTOR_TOOLS_ORPHAN_DEFINED_GROUP'); ?></th>
                        <th><?php echo Text::_('COM_COLLECTOR_TOOLS_ORPHAN_DEFINED_VALUE'); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($this->orphanDefinedContent as $row): ?>
                    <tr>
                        <td><?php echo (int) $row->id; ?></td>
                        <td><?php echo $this->escape($row->group_name); ?></td>
                        <td><?php echo $this->escape($row->content); ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </details>
</div>
<?php endif; ?>

<?php echo HTMLHelper::_('uitab.endTab'); ?>

<?php /* ══════════════════════════════════════════════════════════════════
   ONGLET 3 — Titres
═══════════════════════════════════════════════════════════════════════════ */ ?>
<?php echo HTMLHelper::_('uitab.addTab', 'toolsTab', '#tab-titles', Text::_('COM_COLLECTOR_TOOLS_TAB_TITLES')); ?>

<div class="card mb-3">
    <div class="card-body">
        <div class="d-flex justify-content-between align-items-center mb-2">
            <div>
                <h5 class="card-title mb-1"><?php echo Text::_('COM_COLLECTOR_TOOLS_FULLTITLES_TITLE'); ?></h5>
                <p class="card-text text-muted small mb-0" id="fulltitles-desc">
                    <?php echo Text::sprintf('COM_COLLECTOR_TOOLS_FULLTITLES_DESC', $this->emptyFulltitles); ?>
                </p>
            </div>
            <div class="d-flex gap-2">
                <button type="button" id="btn-regen-fulltitles-empty"
                        class="btn btn-primary btn-sm <?php echo $this->emptyFulltitles === 0 ? 'disabled' : ''; ?>"
                        onclick="regenerateFulltitles('empty')">
                    <span class="icon-refresh me-1"></span>
                    <?php echo Text::_('COM_COLLECTOR_TOOLS_FULLTITLES_BUTTON_EMPTY'); ?>
                </button>
                <button type="button" id="btn-regen-fulltitles-all"
                        class="btn btn-outline-secondary btn-sm"
                        onclick="regenerateFulltitles('all')">
                    <span class="icon-refresh me-1"></span>
                    <?php echo Text::_('COM_COLLECTOR_TOOLS_FULLTITLES_BUTTON_ALL'); ?>
                </button>
            </div>
        </div>
        <div class="progress" id="fulltitles-progress" style="display:none; height:20px;">
            <div class="progress-bar progress-bar-striped progress-bar-animated"
                 id="fulltitles-progress-bar" style="width:0%">0%</div>
        </div>
        <div id="fulltitles-result" class="small text-muted mt-1"></div>
    </div>
</div>

<?php echo HTMLHelper::_('uitab.endTab'); ?>

<?php /* ══════════════════════════════════════════════════════════════════
   ONGLET 4 — Export / Import
═══════════════════════════════════════════════════════════════════════════ */ ?>
<?php echo HTMLHelper::_('uitab.addTab', 'toolsTab', '#tab-export', Text::_('COM_COLLECTOR_TOOLS_TAB_EXPORT')); ?>

<?php /* Export */ ?>
<div class="card mb-3">
    <div class="card-header"><?php echo Text::_('COM_COLLECTOR_TOOLS_EXPORT_TITLE'); ?></div>
    <div class="card-body">
        <form action="<?php echo Route::_('index.php?option=com_collector&task=tools.export'); ?>" method="post">
            <div class="row align-items-end g-3">
                <div class="col-md-5">
                    <label class="form-label small"><?php echo Text::_('COM_COLLECTOR_TOOLS_EXPORT_MODE'); ?></label>
                    <select name="export_mode" class="form-select form-select-sm"
                            onchange="toggleExportCollection(this.value)">
                        <option value="collection"><?php echo Text::_('COM_COLLECTOR_TOOLS_EXPORT_MODE_COLLECTION'); ?></option>
                        <option value="collection_with_lists"><?php echo Text::_('COM_COLLECTOR_TOOLS_EXPORT_MODE_COLLECTION_LISTS'); ?></option>
                        <option value="global"><?php echo Text::_('COM_COLLECTOR_TOOLS_EXPORT_MODE_GLOBAL'); ?></option>
                    </select>
                </div>
                <div class="col-auto">
                    <button type="submit" class="btn btn-primary btn-sm">
                        <span class="icon-download me-1"></span>
                        <?php echo Text::_('COM_COLLECTOR_TOOLS_EXPORT_BUTTON'); ?>
                    </button>
                </div>
            </div>
            <input type="hidden" name="collection" value="<?php echo (int) $this->collection; ?>" id="export_collection_input">
            <?php echo HTMLHelper::_('form.token'); ?>
        </form>

        <?php if (!empty($this->exportFiles)): ?>
        <hr>
        <h6 class="mb-2"><?php echo Text::_('COM_COLLECTOR_TOOLS_EXPORT_FILES_TITLE'); ?></h6>
        <table class="table table-sm mb-0">
            <thead>
                <tr>
                    <th><?php echo Text::_('COM_COLLECTOR_TOOLS_EXPORT_FILE_NAME'); ?></th>
                    <th class="text-end"><?php echo Text::_('COM_COLLECTOR_TOOLS_STATS_SIZE'); ?></th>
                    <th><?php echo Text::_('JDATE'); ?></th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($this->exportFiles as $file): ?>
                <tr>
                    <td class="small"><?php echo $this->escape($file->name); ?></td>
                    <td class="text-end small">
                        <?php echo $file->size >= 1048576
                            ? number_format($file->size / 1048576, 2) . ' Mo'
                            : number_format($file->size / 1024, 2) . ' Ko'; ?>
                    </td>
                    <td class="small"><?php echo date('d/m/Y H:i:s', $file->date); ?></td>
                    <td class="text-end text-nowrap">
                        <a href="<?php echo Route::_('index.php?option=com_collector&task=tools.downloadexport'
                            . '&file=' . urlencode($file->name) . '&' . $token . '=1'); ?>"
                           class="btn btn-sm btn-outline-primary me-1">
                            <span class="icon-download"></span>
                        </a>
                        <a href="<?php echo Route::_('index.php?option=com_collector&task=tools.deleteexport'
                            . '&file=' . urlencode($file->name)
                            . '&collection=' . (int) $this->collection
                            . '&' . $token . '=1'); ?>"
                           class="btn btn-sm btn-outline-danger"
                           onclick="return confirm('<?php echo Text::_('COM_COLLECTOR_TOOLS_EXPORT_DELETE_CONFIRM'); ?>')">
                            <span class="icon-trash"></span>
                        </a>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        <?php endif; ?>
    </div>
</div>

<?php /* Import */ ?>
<div class="card mb-3">
    <div class="card-header"><?php echo Text::_('COM_COLLECTOR_TOOLS_IMPORT_TITLE'); ?></div>
    <div class="card-body">
        <h6><?php echo Text::_('COM_COLLECTOR_TOOLS_IMPORT_FROM_UPLOAD'); ?></h6>
        <form action="<?php echo Route::_('index.php?option=com_collector&task=tools.import'); ?>"
              method="post" enctype="multipart/form-data">
            <div class="row align-items-end g-3">
                <div class="col-md-4">
                    <label class="form-label small"><?php echo Text::_('COM_COLLECTOR_TOOLS_IMPORT_FILE'); ?></label>
                    <input type="file" name="sql_file" class="form-control form-control-sm" accept=".sql">
                </div>
                <div class="col-md-3">
                    <label class="form-label small"><?php echo Text::_('COM_COLLECTOR_TOOLS_IMPORT_MODE'); ?></label>
                    <select name="import_mode" class="form-select form-select-sm">
                        <option value="merge"><?php echo Text::_('COM_COLLECTOR_TOOLS_IMPORT_MODE_MERGE'); ?></option>
                        <option value="replace"><?php echo Text::_('COM_COLLECTOR_TOOLS_IMPORT_MODE_REPLACE'); ?></option>
                    </select>
                </div>
                <div class="col-auto">
                    <button type="submit" class="btn btn-warning btn-sm"
                            onclick="return confirm('<?php echo Text::_('COM_COLLECTOR_TOOLS_IMPORT_CONFIRM'); ?>')">
                        <span class="icon-upload me-1"></span>
                        <?php echo Text::_('COM_COLLECTOR_TOOLS_IMPORT_BUTTON'); ?>
                    </button>
                </div>
            </div>
            <input type="hidden" name="collection" value="<?php echo (int) $this->collection; ?>">
            <?php echo HTMLHelper::_('form.token'); ?>
        </form>

        <?php if (!empty($this->exportFiles)): ?>
        <hr>
        <h6><?php echo Text::_('COM_COLLECTOR_TOOLS_IMPORT_FROM_SERVER'); ?></h6>
        <form action="<?php echo Route::_('index.php?option=com_collector&task=tools.importfromserver'); ?>"
              method="post">
            <div class="row align-items-end g-3">
                <div class="col-md-5">
                    <label class="form-label small"><?php echo Text::_('COM_COLLECTOR_TOOLS_IMPORT_SELECT_FILE'); ?></label>
                    <select name="server_file" class="form-select form-select-sm">
                        <?php foreach ($this->exportFiles as $file): ?>
                        <option value="<?php echo $this->escape($file->name); ?>">
                            <?php echo $this->escape($file->name); ?>
                            (<?php echo $file->size >= 1048576
                                ? number_format($file->size / 1048576, 2) . ' Mo'
                                : number_format($file->size / 1024, 2) . ' Ko'; ?>)
                            — <?php echo date('d/m/Y H:i', $file->date); ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label small"><?php echo Text::_('COM_COLLECTOR_TOOLS_IMPORT_MODE'); ?></label>
                    <select name="import_mode" class="form-select form-select-sm">
                        <option value="merge"><?php echo Text::_('COM_COLLECTOR_TOOLS_IMPORT_MODE_MERGE'); ?></option>
                        <option value="replace"><?php echo Text::_('COM_COLLECTOR_TOOLS_IMPORT_MODE_REPLACE'); ?></option>
                    </select>
                </div>
                <div class="col-auto">
                    <button type="submit" class="btn btn-warning btn-sm"
                            onclick="return confirm('<?php echo Text::_('COM_COLLECTOR_TOOLS_IMPORT_CONFIRM'); ?>')">
                        <span class="icon-upload me-1"></span>
                        <?php echo Text::_('COM_COLLECTOR_TOOLS_IMPORT_BUTTON'); ?>
                    </button>
                </div>
            </div>
            <input type="hidden" name="collection" value="<?php echo (int) $this->collection; ?>">
            <?php echo HTMLHelper::_('form.token'); ?>
        </form>
        <?php endif; ?>
    </div>
</div>

<?php echo HTMLHelper::_('uitab.endTab'); ?>

<?php /* ══════════════════════════════════════════════════════════════════
   ONGLET 5 — Statistiques
═══════════════════════════════════════════════════════════════════════════ */ ?>
<?php echo HTMLHelper::_('uitab.addTab', 'toolsTab', '#tab-stats', Text::_('COM_COLLECTOR_TOOLS_TAB_STATS')); ?>

<div class="row">
    <div class="col-md-4 mb-3">
        <div class="card h-100">
            <div class="card-header"><?php echo Text::_('COM_COLLECTOR_TOOLS_STATS_ITEMS_TITLE'); ?></div>
            <table class="table table-sm mb-0">
                <thead>
                    <tr>
                        <th><?php echo Text::_('JSTATUS'); ?></th>
                        <th class="text-end"><?php echo Text::_('COM_COLLECTOR_TOOLS_STATS_COUNT'); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    $stateLabels = [
                        1  => Text::_('JPUBLISHED'),
                        0  => Text::_('JUNPUBLISHED'),
                        2  => Text::_('JARCHIVED'),
                        -2 => Text::_('JTRASHED'),
                    ];
                    $grandTotal = 0;
                    foreach ($stateLabels as $state => $label):
                        $cnt = (int) ($this->itemCountByState[$state]['cnt'] ?? 0);
                        if (!$cnt) continue;
                        $grandTotal += $cnt;
                    ?>
                    <tr>
                        <td><?php echo $label; ?></td>
                        <td class="text-end"><span class="badge bg-secondary"><?php echo $cnt; ?></span></td>
                    </tr>
                    <?php endforeach; ?>
                    <tr class="table-light fw-bold">
                        <td><?php echo Text::_('JALL'); ?></td>
                        <td class="text-end"><span class="badge bg-primary"><?php echo $grandTotal; ?></span></td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>

    <div class="col-md-8 mb-3">
        <div class="card h-100">
            <div class="card-header"><?php echo Text::_('COM_COLLECTOR_TOOLS_STATS_TABLES_TITLE'); ?></div>
            <table class="table table-sm mb-0">
                <thead>
                    <tr>
                        <th><?php echo Text::_('COM_COLLECTOR_TOOLS_STATS_TABLE'); ?></th>
                        <th class="text-end"><?php echo Text::_('COM_COLLECTOR_TOOLS_STATS_ROWS'); ?></th>
                        <th class="text-end"><?php echo Text::_('COM_COLLECTOR_TOOLS_STATS_SIZE'); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($this->tableStats as $stat): ?>
                    <tr <?php echo !$stat->exists ? 'class="text-muted"' : ''; ?>>
                        <td>
                            <?php echo $stat->label; ?>
                            <small class="text-muted d-block"><?php echo $stat->table; ?></small>
                        </td>
                        <td class="text-end">
                            <?php echo $stat->exists ? number_format($stat->rows) : '—'; ?>
                        </td>
                        <td class="text-end">
                            <?php if ($stat->exists): ?>
                                <?php echo $stat->size_kb >= 1024
                                    ? number_format($stat->size_kb / 1024, 2) . ' Mo'
                                    : number_format($stat->size_kb, 2) . ' Ko'; ?>
                            <?php else: ?>—<?php endif; ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php echo HTMLHelper::_('uitab.endTab'); ?>

<?php /* ══════════════════════════════════════════════════════════════════
   ONGLET 6 — Gestionnaire de fichiers
═══════════════════════════════════════════════════════════════════════════ */ ?>
<?php echo HTMLHelper::_('uitab.addTab', 'toolsTab', '#tab-files', Text::_('COM_COLLECTOR_TOOLS_TAB_FILES')); ?>

<div class="row mb-3">
    <div class="col-md-4">
        <select id="files-context" class="form-select form-select-sm" onchange="switchFilesContext(this.value)">
            <option value=""><?php echo Text::_('COM_COLLECTOR_FILES_CHOOSE_CONTEXT'); ?></option>
            <optgroup label="<?php echo Text::_('COM_COLLECTOR_FILES_CONTEXT_COLLECTIONS'); ?>">
                <?php foreach ($this->collections as $col): ?>
                <option value="collection:<?php echo $col->id; ?>"
                        <?php echo ($this->filesContext === 'collection' && $this->filesContextId == $col->id) ? 'selected' : ''; ?>>
                    <?php echo $this->escape($col->name); ?>
                </option>
                <?php endforeach; ?>
            </optgroup>
            <optgroup label="<?php echo Text::_('COM_COLLECTOR_FILES_CONTEXT_DEFINE'); ?>">
                <?php foreach ($this->defines as $def): ?>
                <option value="define:<?php echo $def->id; ?>"
                        <?php echo ($this->filesContext === 'define' && $this->filesContextId == $def->id) ? 'selected' : ''; ?>>
                    <?php echo $this->escape($def->name); ?>
                </option>
                <?php endforeach; ?>
            </optgroup>
        </select>
    </div>

    <?php if ($this->filesContextId): ?>
    <div class="col-md-8 d-flex gap-2 align-items-center">
        <button type="button" class="btn btn-sm btn-outline-secondary" onclick="showMkdirModal()">
            <span class="icon-folder-plus me-1"></span><?php echo Text::_('COM_COLLECTOR_FILES_NEW_FOLDER'); ?>
        </button>
        <span class="action-selection d-none">
            <button type="button" class="btn btn-sm btn-outline-primary" onclick="showMoveModal()">
                <span class="icon-forward me-1"></span><?php echo Text::_('COM_COLLECTOR_FILES_MOVE'); ?>
            </button>
            <button type="button" class="btn btn-sm btn-outline-warning" onclick="showRenameModal()" id="btn-rename">
                <span class="icon-pencil me-1"></span><?php echo Text::_('COM_COLLECTOR_FILES_RENAME'); ?>
            </button>
            <button type="button" class="btn btn-sm btn-outline-danger" onclick="deleteSelected()">
                <span class="icon-trash me-1"></span><?php echo Text::_('COM_COLLECTOR_FILES_DELETE'); ?>
            </button>
        </span>
        <span class="text-muted small ms-auto" id="selection-info"></span>
    </div>
    <?php endif; ?>
</div>

<?php if (!$this->filesContextId): ?>
<div class="alert alert-info"><?php echo Text::_('COM_COLLECTOR_FILES_CHOOSE_CONTEXT_HELP'); ?></div>
<?php else: ?>

<div class="row" style="min-height:500px;">
    <div class="col-md-3 border-end pe-3">
        <div id="folder-tree" class="small"><?php echo Text::_('COM_COLLECTOR_FILES_LOADING'); ?></div>
    </div>
    <div class="col-md-9">
        <nav aria-label="breadcrumb" class="mb-2">
            <ol class="breadcrumb small" id="breadcrumb">
                <li class="breadcrumb-item">
                    <a href="#" onclick="navigateTo(''); return false;"><span class="icon-home"></span></a>
                </li>
            </ol>
        </nav>
        <div id="dropzone" class="border rounded p-2 position-relative" style="min-height:400px;"
             ondragover="onDragOver(event)" ondragleave="onDragLeave(event)" ondrop="onDrop(event)">
            <div id="drop-overlay"
                 class="d-none position-absolute top-0 start-0 w-100 h-100 d-flex align-items-center justify-content-center bg-primary bg-opacity-10 border border-primary rounded"
                 style="z-index:10;">
                <span class="icon-upload fs-1 text-primary"></span>
                <span class="ms-2 text-primary fw-bold"><?php echo Text::_('COM_COLLECTOR_FILES_DROP_HERE'); ?></span>
            </div>
            <div id="files-grid" class="row g-2"><?php echo Text::_('COM_COLLECTOR_FILES_LOADING'); ?></div>
        </div>
        <div id="upload-progress" class="mt-2 d-none">
            <div class="progress" style="height:20px;">
                <div class="progress-bar progress-bar-striped progress-bar-animated" id="upload-bar" style="width:0%">0%</div>
            </div>
            <div class="small text-muted mt-1" id="upload-status"></div>
        </div>
    </div>
</div>

<?php /* Modales */ ?>
<div class="modal fade" id="modal-mkdir" tabindex="-1">
    <div class="modal-dialog modal-sm modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h6 class="modal-title"><?php echo Text::_('COM_COLLECTOR_FILES_NEW_FOLDER'); ?></h6>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <input type="text" id="mkdir-name" class="form-control form-control-sm"
                       placeholder="<?php echo Text::_('COM_COLLECTOR_FILES_FOLDER_NAME'); ?>">
            </div>
            <div class="modal-footer p-2">
                <button type="button" class="btn btn-sm btn-outline-secondary" data-bs-dismiss="modal"><?php echo Text::_('JCANCEL'); ?></button>
                <button type="button" class="btn btn-sm btn-primary" onclick="doMkdir()"><?php echo Text::_('JCREATE'); ?></button>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="modal-rename" tabindex="-1">
    <div class="modal-dialog modal-sm modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h6 class="modal-title"><?php echo Text::_('COM_COLLECTOR_FILES_RENAME'); ?></h6>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <input type="text" id="rename-input" class="form-control form-control-sm">
                <div id="rename-refs" class="small text-muted mt-2"></div>
            </div>
            <div class="modal-footer p-2">
                <button type="button" class="btn btn-sm btn-outline-secondary" data-bs-dismiss="modal"><?php echo Text::_('JCANCEL'); ?></button>
                <button type="button" class="btn btn-sm btn-warning" onclick="doRename()"><?php echo Text::_('COM_COLLECTOR_FILES_RENAME'); ?></button>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="modal-move" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h6 class="modal-title"><?php echo Text::_('COM_COLLECTOR_FILES_MOVE'); ?></h6>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p class="small text-muted mb-2"><?php echo Text::_('COM_COLLECTOR_FILES_MOVE_TARGET'); ?></p>
                <div id="move-tree" class="border rounded p-2 small" style="max-height:300px; overflow-y:auto;"></div>
                <div id="move-refs" class="small text-muted mt-2"></div>
            </div>
            <div class="modal-footer p-2">
                <button type="button" class="btn btn-sm btn-outline-secondary" data-bs-dismiss="modal"><?php echo Text::_('JCANCEL'); ?></button>
                <button type="button" class="btn btn-sm btn-primary" onclick="doMove()"><?php echo Text::_('COM_COLLECTOR_FILES_MOVE'); ?></button>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="modal-refs" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h6 class="modal-title"><?php echo Text::_('COM_COLLECTOR_FILES_REFERENCES'); ?></h6>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" id="refs-content"></div>
            <div class="modal-footer p-2">
                <button type="button" class="btn btn-sm btn-outline-secondary" data-bs-dismiss="modal"><?php echo Text::_('JCLOSE'); ?></button>
            </div>
        </div>
    </div>
</div>

<?php endif; ?>

<?php echo HTMLHelper::_('uitab.endTab'); ?>

<?php /* ══════════════════════════════════════════════════════════════════
   ONGLET — Vérification des liens de fichiers
═══════════════════════════════════════════════════════════════════════════ */ ?>
<?php echo HTMLHelper::_('uitab.addTab', 'toolsTab', '#tab-filecheck', Text::_('COM_COLLECTOR_FILECHECK_TAB')); ?>

<p class="text-muted small"><?php echo Text::_('COM_COLLECTOR_FILECHECK_INTRO'); ?></p>

<?php $runUrl = Route::_('index.php?option=com_collector&view=tools&collection=' . (int) $this->collection . '&active_tab=%23tab-filecheck'); ?>

<?php if ($this->fileReport === null): ?>
<a href="<?php echo $runUrl; ?>" class="btn btn-primary">
    <span class="icon-search me-1"></span><?php echo Text::_('COM_COLLECTOR_FILECHECK_RUN'); ?>
</a>
<?php else: ?>
<?php $report = $this->fileReport; ?>

<div class="d-flex flex-wrap gap-2 align-items-center mb-3">
    <span class="badge bg-secondary"><?php echo Text::sprintf('COM_COLLECTOR_FILECHECK_STAT_CHECKED', $report->stats->checked); ?></span>
    <span class="badge <?php echo $report->stats->broken ? 'bg-danger' : 'bg-success'; ?>"><?php echo Text::sprintf('COM_COLLECTOR_FILECHECK_STAT_BROKEN', $report->stats->broken); ?></span>
    <span class="badge bg-info text-dark"><?php echo Text::sprintf('COM_COLLECTOR_FILECHECK_STAT_UNUSED', $report->stats->unused); ?></span>
    <a href="<?php echo $runUrl; ?>" class="btn btn-sm btn-outline-secondary ms-auto">
        <span class="icon-refresh me-1"></span><?php echo Text::_('COM_COLLECTOR_FILECHECK_RERUN'); ?>
    </a>
    <?php if ($report->stats->fixable): ?>
    <form action="<?php echo Route::_('index.php?option=com_collector&task=tools.autofixfilelinks'); ?>" method="post" class="d-inline"
          onsubmit="return confirm(<?php echo $this->escape(json_encode(Text::sprintf('COM_COLLECTOR_FILECHECK_AUTOFIX_CONFIRM', $report->stats->fixable))); ?>)">
        <button type="submit" class="btn btn-sm btn-success">
            <span class="icon-wrench me-1"></span><?php echo Text::sprintf('COM_COLLECTOR_FILECHECK_AUTOFIX', $report->stats->fixable); ?>
        </button>
        <?php echo HTMLHelper::_('form.token'); ?>
    </form>
    <?php endif; ?>
</div>

<?php if (!$report->broken): ?>
<div class="alert alert-success"><?php echo Text::_('COM_COLLECTOR_FILECHECK_NO_BROKEN'); ?></div>
<?php else: ?>
<table class="table table-sm align-middle">
    <thead>
        <tr>
            <th><?php echo Text::_('COM_COLLECTOR_FILECHECK_COL_PATH'); ?></th>
            <th><?php echo Text::_('COM_COLLECTOR_FILECHECK_COL_USED_BY'); ?></th>
            <th style="width:40%"><?php echo Text::_('COM_COLLECTOR_FILECHECK_COL_FIX'); ?></th>
        </tr>
    </thead>
    <tbody>
    <?php foreach ($report->broken as $i => $link): ?>
        <tr>
            <td class="small text-break"><code><?php echo $this->escape($link->path); ?></code></td>
            <td class="small">
                <?php foreach ($link->items as $use): ?>
                <div>
                    <a href="<?php echo Route::_('index.php?option=com_collector&task=item.edit&collection=' . $use->collection . '&id=' . $use->item); ?>" target="_blank">
                        <?php echo $this->escape($use->title !== '' ? $use->title : '#' . $use->item); ?>
                    </a>
                    <span class="text-muted">— <?php echo $this->escape($use->field); ?></span>
                    <?php if (!$use->current): ?>
                    <span class="badge bg-light text-dark border"><?php echo Text::_('COM_COLLECTOR_FILECHECK_OLD_VERSIONS_ONLY'); ?></span>
                    <?php endif; ?>
                </div>
                <?php endforeach; ?>
                <?php foreach ($link->defined as $use): ?>
                <div>
                    <a href="<?php echo Route::_('index.php?option=com_collector&task=listitem.edit&defined=' . $use->defined . '&id=' . $use->id); ?>" target="_blank">
                        <?php echo $this->escape($use->content); ?>
                    </a>
                    <span class="text-muted">— <?php echo Text::sprintf('COM_COLLECTOR_FILECHECK_IN_LIST', $this->escape($use->list)); ?></span>
                </div>
                <?php endforeach; ?>
                <?php foreach ($link->collections as $use): ?>
                <div>
                    <a href="<?php echo Route::_('index.php?option=com_collector&task=collection.edit&id=' . $use->id); ?>" target="_blank">
                        <?php echo $this->escape($use->name); ?>
                    </a>
                    <span class="text-muted">— <?php echo Text::_('COM_COLLECTOR_FILECHECK_COLLECTION_IMAGE'); ?></span>
                </div>
                <?php endforeach; ?>
            </td>
            <td>
                <?php if (!$link->candidates): ?>
                <?php
                // Aucun fichier de même nom : champ média Joomla (sélection ou envoi d'un fichier dans la modale)
                // La modale s'ouvre dans le dossier d'origine du fichier s'il existe encore
                $mediaDir = 'collector';
                $oldDir   = dirname($link->path);

                if (str_starts_with($oldDir, 'images/') && is_dir(JPATH_ROOT . '/' . $oldDir)) {
                    $mediaDir = substr($oldDir, strlen('images/'));
                }

                $mediaForm = \Joomla\CMS\Form\Form::getInstance(
                    'com_collector.filecheck.' . $i,
                    '<form><field name="new_path" type="media" types="images,documents" preview="tooltip" directory="'
                        . htmlspecialchars($mediaDir, ENT_QUOTES) . '" /></form>',
                    ['control' => 'fix' . $i]
                );
                ?>
                <div class="small text-muted mb-1"><?php echo Text::_('COM_COLLECTOR_FILECHECK_NO_CANDIDATE'); ?></div>
                <form action="<?php echo Route::_('index.php?option=com_collector&task=tools.fixfilelink'); ?>" method="post" class="d-flex gap-1 align-items-start">
                    <div class="flex-grow-1"><?php echo $mediaForm->getInput('new_path'); ?></div>
                    <input type="hidden" name="old_path" value="<?php echo $this->escape($link->path); ?>">
                    <input type="hidden" name="row" value="<?php echo (int) $i; ?>">
                    <button type="submit" class="btn btn-sm btn-primary text-nowrap"><?php echo Text::_('COM_COLLECTOR_FILECHECK_FIX'); ?></button>
                    <?php echo HTMLHelper::_('form.token'); ?>
                </form>
                <?php else: ?>
                <form action="<?php echo Route::_('index.php?option=com_collector&task=tools.fixfilelink'); ?>" method="post" class="d-flex gap-1">
                    <select name="new_path" class="form-select form-select-sm" aria-label="<?php echo Text::_('COM_COLLECTOR_FILECHECK_COL_FIX'); ?>">
                        <?php foreach ($link->candidates as $candidate): ?>
                        <option value="<?php echo $this->escape($candidate); ?>"><?php echo $this->escape($candidate); ?></option>
                        <?php endforeach; ?>
                    </select>
                    <input type="hidden" name="old_path" value="<?php echo $this->escape($link->path); ?>">
                    <button type="submit" class="btn btn-sm btn-primary text-nowrap"><?php echo Text::_('COM_COLLECTOR_FILECHECK_FIX'); ?></button>
                    <?php echo HTMLHelper::_('form.token'); ?>
                </form>
                <?php if (count($link->candidates) > 1): ?>
                <div class="small text-warning mt-1"><?php echo Text::sprintf('COM_COLLECTOR_FILECHECK_SEVERAL_CANDIDATES', count($link->candidates)); ?></div>
                <?php endif; ?>
                <?php endif; ?>
            </td>
        </tr>
    <?php endforeach; ?>
    </tbody>
</table>
<?php endif; ?>

<?php if ($report->unused): ?>
<details class="mt-3">
    <summary class="fw-bold"><?php echo Text::sprintf('COM_COLLECTOR_FILECHECK_UNUSED_TITLE', count($report->unused)); ?> <span class="icon-chevron-down details-arrow small"></span></summary>
    <p class="small text-muted mt-2"><?php echo Text::_('COM_COLLECTOR_FILECHECK_UNUSED_DESC'); ?></p>
    <ul class="small" style="columns: 2;">
        <?php foreach ($report->unused as $path): ?>
        <li class="text-break"><a href="<?php echo $this->escape(\Joomla\CMS\Uri\Uri::root() . $path); ?>" target="_blank"><?php echo $this->escape($path); ?></a></li>
        <?php endforeach; ?>
    </ul>
</details>
<?php endif; ?>

<?php endif; ?>

<?php echo HTMLHelper::_('uitab.endTab'); ?>

<?php echo HTMLHelper::_('uitab.endTabSet'); ?>

<?php else: ?>
<div class="alert alert-info">
    <?php echo Text::_('COM_COLLECTOR_TOOLS_CHOOSE_COLLECTION'); ?>
</div>
<?php endif; ?>

<style>
details > summary .details-arrow { transition: transform 0.2s; }
details[open] > summary .details-arrow { transform: rotate(180deg); }
details > summary::-webkit-details-marker { display: none; }
</style>

<script>
const FULLTITLES_TOTAL_EMPTY = <?php echo (int) ($this->emptyFulltitles ?? 0); ?>;
const TOTAL_ITEMS            = <?php echo (int) ($this->totalItems ?? 0); ?>;
const COLLECTION_ID          = <?php echo (int) $this->collection; ?>;
const TOKEN                  = '<?php echo $token; ?>';

document.addEventListener('DOMContentLoaded', () => {
    <?php if (!empty($this->filesContextId)): ?>
    
    const tabShown = () => {
        if (!document.getElementById('files-grid').dataset.loaded) {
            loadTree();
            loadFiles('');
            document.getElementById('files-grid').dataset.loaded = '1';
        }
    };

    // Si l'onglet fichiers est actif au chargement
    if ('<?php echo $activeTab; ?>' === '#tab-files') {
        tabShown();
    }

    // Ou au premier clic sur l'onglet
    document.querySelector('[data-bs-target="#tab-files"]')
        ?.addEventListener('shown.bs.tab', tabShown);

    <?php endif; ?>
});

async function regenerateFulltitles(mode) {
    const btn      = document.getElementById('btn-regen-fulltitles-' + mode);
    const progress = document.getElementById('fulltitles-progress');
    const bar      = document.getElementById('fulltitles-progress-bar');
    const result   = document.getElementById('fulltitles-result');

    btn.disabled = true;
    progress.style.display = 'block';
    bar.classList.add('progress-bar-animated');

    const total = (mode === 'empty') ? FULLTITLES_TOTAL_EMPTY : TOTAL_ITEMS;

    let afterId    = 0;
    let totalFixed = 0;
    let totalDone  = 0;
    let done       = false;

    while (!done) {
        const fd = new FormData();
        fd.append('collection', COLLECTION_ID);
        fd.append('mode',       mode);
        fd.append('after_id',   afterId);
        fd.append(TOKEN,        '1');

        const resp = await fetch('index.php?option=com_collector&task=tools.regeneratefulltitlesajax', {
            method: 'POST', body: fd,
            headers: {'X-Requested-With': 'XMLHttpRequest'}
        });
        const data = await resp.json();

        if (!data.success) {
            result.textContent = data.message || 'Erreur';
            break;
        }

        totalFixed += data.data.fixed;
        totalDone  += data.data.processed;
        afterId     = data.data.last_id;
        done        = data.data.done;

        const pct = total > 0 ? Math.min(100, Math.round((totalDone / total) * 100)) : 100;
        bar.style.width    = pct + '%';
        bar.textContent    = pct + '%';
        result.textContent = totalFixed + ' / ' + total + ' traités';

        if (data.data.processed === 0) break;
    }

    bar.classList.remove('progress-bar-animated');
    result.textContent += ' — Terminé.';
    btn.classList.add('disabled');
}

function toggleExportCollection(mode) {
    document.getElementById('export_collection_input').value =
        (mode === 'global') ? 0 : COLLECTION_ID;
}

// ── Gestionnaire de fichiers ───────────────────────────────────────────────
const FILE_TOKEN   = '<?php echo $token; ?>';
const FILE_CONTEXT = '<?php echo $this->escape($this->filesContext ?? ''); ?>';
const FILE_ID      = <?php echo (int) ($this->filesContextId ?? 0); ?>;
const FILES_BASE   = 'index.php?option=com_collector&';

let currentSubpath = '';
let selectedFiles  = new Set();
let moveTargetPath = '';
let draggedPath    = null;

<?php if (!empty($this->filesContextId)): ?>
document.addEventListener('DOMContentLoaded', () => {
    // Initialiser seulement si l'onglet fichiers est actif ou au premier clic
    document.querySelector('a[data-bs-target="#tab-files"]')
        ?.addEventListener('shown.bs.tab', () => {
            if (!document.getElementById('files-grid').dataset.loaded) {
                loadTree();
                loadFiles('');
                document.getElementById('files-grid').dataset.loaded = '1';
            }
        });
});
<?php endif; ?>

function switchFilesContext(value) {
    if (!value) return;
    const [ctx, id] = value.split(':');
    window.location.href = `index.php?option=com_collector&view=tools&collection=<?php echo (int) $this->collection; ?>&context=${ctx}&context_id=${id}&active_tab=%23tab-files`;
}

// ... [reprendre toutes les fonctions JS du template files/default.php]
// navigateTo, loadTree, renderTree, loadFiles, toggleSelect,
// updateSelectionBar, showMkdirModal, doMkdir, showRenameModal, doRename,
// showMoveModal, renderMoveTree, selectMoveTarget, doMove, deleteSelected,
// showRefs, onDragOver, onDragLeave, onDrop, onFileDragStart,
// buildFormData, apiCall, showToast

async function navigateTo(subpath) {
    currentSubpath = subpath;
    selectedFiles.clear();
    updateSelectionBar();
    await loadFiles(subpath);
    updateBreadcrumb(subpath);
}

function updateBreadcrumb(subpath) {
    const bc   = document.getElementById('breadcrumb');
    const parts = subpath ? subpath.split('/') : [];
    let html = `<li class="breadcrumb-item"><a href="#" onclick="navigateTo(''); return false;"><span class="icon-home"></span></a></li>`;
    let accumulated = '';
    parts.forEach((part, i) => {
        accumulated = accumulated ? accumulated + '/' + part : part;
        const path = accumulated;
        if (i === parts.length - 1) {
            html += `<li class="breadcrumb-item active">${part}</li>`;
        } else {
            html += `<li class="breadcrumb-item"><a href="#" onclick="navigateTo('${path}'); return false;">${part}</a></li>`;
        }
    });
    bc.innerHTML = html;
}

// ── Chargement arborescence ───────────────────────────────────────────────
async function loadTree(subpath = '', container = null) {
    const fd = buildFormData({ subpath });
    const data = await apiCall('files.browse', fd);
    if (!data) return;

    const el = container || document.getElementById('folder-tree');
    el.innerHTML = renderTree(data.dirs, subpath);
}

function renderTree(dirs, parentPath) {
    if (!dirs.length) return '<span class="text-muted"><?php echo Text::_('COM_COLLECTOR_FILES_NO_SUBFOLDERS'); ?></span>';
    return dirs.map(dir => `
        <div class="mb-1">
            <a href="#" class="text-decoration-none d-flex align-items-center gap-1"
               onclick="navigateTo('${dir.path}'); return false;">
                <span class="icon-folder text-warning"></span>
                <span>${dir.name}</span>
                <small class="text-muted ms-auto">${dir.count}</small>
            </a>
        </div>
    `).join('');
}

// ── Chargement fichiers ───────────────────────────────────────────────────
async function loadFiles(subpath) {
    const fd   = buildFormData({ subpath });
    const data = await apiCall('files.browse', fd);
    if (!data) return;

    const grid = document.getElementById('files-grid');
    let html   = '';

    // Dossiers
    data.dirs.forEach(dir => {
        html += `
        <div class="col-6 col-md-3 col-lg-2">
            <div class="card h-100 text-center border-0 bg-light file-item" data-path="${dir.path}" data-type="dir"
                 ondblclick="navigateTo('${dir.path}')" onclick="toggleSelect(this, event)">
                <div class="card-body p-2">
                    <span class="icon-folder text-warning" style="font-size:2.5rem;"></span>
                    <div class="small mt-1 text-truncate">${dir.name}</div>
                    <div class="text-muted" style="font-size:0.7rem;">${dir.count} fichier(s)</div>
                </div>
            </div>
        </div>`;
    });

    // Fichiers
    data.files.forEach(file => {
        const size = file.size >= 1048576
            ? (file.size / 1048576).toFixed(2) + ' Mo'
            : (file.size / 1024).toFixed(1) + ' Ko';

        const preview = file.is_image
            ? `<img src="${file.url}" class="img-fluid" style="max-height:80px; object-fit:cover;" loading="lazy" onerror="this.style.display='none'">`
            : `<span class="icon-file" style="font-size:2.5rem;"></span>`;

        html += `
        <div class="col-6 col-md-3 col-lg-2">
            <div class="card h-100 text-center border file-item"
                 data-path="${file.path}" data-rel-path="${file.rel_path}" data-type="file"
                 onclick="toggleSelect(this, event)"
                 draggable="true"
                 ondragstart="onFileDragStart(event, this)">
                <div class="card-body p-2">
                    ${preview}
                    <div class="small mt-1 text-truncate" title="${file.name}">${file.name}</div>
                    <div class="text-muted" style="font-size:0.7rem;">${size}</div>
                </div>
                <div class="card-footer p-1 bg-transparent border-0">
                    <button class="btn btn-link btn-sm p-0 text-muted" title="<?php echo Text::_('COM_COLLECTOR_FILES_REFERENCES'); ?>"
                            onclick="showRefs(event, '${file.rel_path}')">
                        <span class="icon-link"></span>
                    </button>
                </div>
            </div>
        </div>`;
    });

    if (!html) {
        html = `<div class="col-12 text-muted small text-center py-5"><?php echo Text::_('COM_COLLECTOR_FILES_EMPTY'); ?></div>`;
    }

    grid.innerHTML = html;
}

// ── Sélection ─────────────────────────────────────────────────────────────
function toggleSelect(el, event) {
    if (event.target.closest('button')) return;
    const path = el.dataset.path;

    if (el.classList.contains('selected')) {
        el.classList.remove('selected', 'border-primary', 'bg-primary', 'bg-opacity-10');
        selectedFiles.delete(path);
    } else {
        el.classList.add('selected', 'border-primary', 'bg-primary', 'bg-opacity-10');
        selectedFiles.add(path);
    }
    updateSelectionBar();
}

function updateSelectionBar() {
    const bar    = document.querySelector('.action-selection');
    const info   = document.getElementById('selection-info');
    const rename = document.getElementById('btn-rename');
    const count  = selectedFiles.size;

    if (count > 0) {
        bar.classList.remove('d-none');
        info.textContent = count + ' élément(s) sélectionné(s)';
        rename.disabled  = count > 1;
    } else {
        bar.classList.add('d-none');
        info.textContent = '';
    }
}

// ── Actions ───────────────────────────────────────────────────────────────

// Nouveau dossier
function showMkdirModal() {
    document.getElementById('mkdir-name').value = '';
    new bootstrap.Modal(document.getElementById('modal-mkdir')).show();
}

async function doMkdir() {
    const name = document.getElementById('mkdir-name').value.trim();
    if (!name) return;

    const fd = buildFormData({ subpath: currentSubpath, name });
    const data = await apiCall('files.mkdir', fd);

    if (data) {
        bootstrap.Modal.getInstance(document.getElementById('modal-mkdir'))?.hide();
        await loadFiles(currentSubpath);
        await loadTree();
        showToast('<?php echo Text::_('COM_COLLECTOR_FILES_DIR_CREATED'); ?>', 'success');
    }
}

// Renommer
async function showRenameModal() {
    const paths = [...selectedFiles];
    if (paths.length !== 1) return;

    const name = paths[0].split('/').pop();
    document.getElementById('rename-input').value = name;

    // Chercher références si c'est un fichier
    const el      = document.querySelector(`.file-item[data-path="${paths[0]}"]`);
    const relPath = el?.dataset.relPath;
    const refsEl  = document.getElementById('rename-refs');

    if (relPath) {
        const fd   = buildFormData({ rel_path: relPath });
        const data = await apiCall('files.references', fd);
        refsEl.textContent = data
            ? `${data.count} référence(s) en base seront mises à jour.`
            : '';
    } else {
        refsEl.textContent = '';
    }

    new bootstrap.Modal(document.getElementById('modal-rename')).show();
}

async function doRename() {
    const paths   = [...selectedFiles];
    const newName = document.getElementById('rename-input').value.trim();
    if (!newName || !paths.length) return;

    const fd   = buildFormData({ subpath: paths[0], new_name: newName });
    const data = await apiCall('files.rename', fd);

    if (data) {
        bootstrap.Modal.getInstance(document.getElementById('modal-rename'))?.hide();
        selectedFiles.clear();
        updateSelectionBar();
        await loadFiles(currentSubpath);
        await loadTree();
        showToast(`Renommé — ${data.references_updated} référence(s) mise(s) à jour.`, 'success');
    }
}

// Déplacer
async function showMoveModal() {
    moveTargetPath = '';

    // Construire l'arborescence de dossiers pour sélectionner la cible
    const fd   = buildFormData({ subpath: '' });
    const data = await apiCall('files.browse', fd);
    const tree = document.getElementById('move-tree');

    if (data) {
        tree.innerHTML = renderMoveTree(data.dirs, '');
    }

    // Compter les références des fichiers sélectionnés
    let totalRefs = 0;
    for (const path of selectedFiles) {
        const el      = document.querySelector(`.file-item[data-path="${path}"]`);
        const relPath = el?.dataset.relPath;
        if (relPath) {
            const fd   = buildFormData({ rel_path: relPath });
            const data = await apiCall('files.references', fd);
            if (data) totalRefs += data.count;
        }
    }
    document.getElementById('move-refs').textContent =
        totalRefs > 0 ? `${totalRefs} référence(s) en base seront mises à jour.` : '';

    new bootstrap.Modal(document.getElementById('modal-move')).show();
}

function renderMoveTree(dirs, parentPath, depth = 0) {
    let html = '';
    // Option racine
    if (depth === 0) {
        html += `<div class="move-target py-1 px-2 rounded" style="cursor:pointer" data-path=""
                      onclick="selectMoveTarget(this, '')">
                     <span class="icon-home me-1"></span><?php echo Text::_('COM_COLLECTOR_FILES_ROOT'); ?>
                 </div>`;
    }
    dirs.forEach(dir => {
        html += `<div class="move-target py-1 px-2 rounded" style="cursor:pointer; padding-left:${(depth+1)*16}px"
                      data-path="${dir.path}" onclick="selectMoveTarget(this, '${dir.path}')">
                     <span class="icon-folder text-warning me-1"></span>${dir.name}
                 </div>`;
    });
    return html;
}

function selectMoveTarget(el, path) {
    document.querySelectorAll('.move-target').forEach(t => t.classList.remove('bg-primary', 'bg-opacity-10', 'text-primary'));
    el.classList.add('bg-primary', 'bg-opacity-10', 'text-primary');
    moveTargetPath = path;
}

async function doMove() {
    const paths = [...selectedFiles];
    let updated = 0;

    for (const from of paths) {
        const filename = from.split('/').pop();
        const to       = moveTargetPath ? moveTargetPath + '/' + filename : filename;
        const fd       = buildFormData({ from, to });
        const data     = await apiCall('files.move', fd);
        if (data) updated += data.references_updated;
    }

    bootstrap.Modal.getInstance(document.getElementById('modal-move'))?.hide();
    selectedFiles.clear();
    updateSelectionBar();
    await loadFiles(currentSubpath);
    await loadTree();
    showToast(`Déplacé — ${updated} référence(s) mise(s) à jour.`, 'success');
}

// Supprimer
async function deleteSelected() {
    if (!confirm('<?php echo Text::_('COM_COLLECTOR_FILES_DELETE_CONFIRM'); ?>')) return;

    for (const path of selectedFiles) {
        const fd = buildFormData({ subpath: path });
        await apiCall('files.delete', fd);
    }

    selectedFiles.clear();
    updateSelectionBar();
    await loadFiles(currentSubpath);
    await loadTree();
    showToast('<?php echo Text::_('COM_COLLECTOR_FILES_DELETED'); ?>', 'success');
}

// Références
async function showRefs(event, relPath) {
    event.stopPropagation();
    const fd   = buildFormData({ rel_path: relPath });
    const data = await apiCall('files.references', fd);
    const el   = document.getElementById('refs-content');

    if (!data || !data.count) {
        el.innerHTML = '<p class="text-muted small"><?php echo Text::_('COM_COLLECTOR_FILES_NO_REFERENCES'); ?></p>';
    } else {
        let html = `<p class="small mb-2">${data.count} référence(s) :</p><ul class="small list-unstyled">`;
        data.items.forEach(item => {
            html += `<li><span class="icon-arrow-right me-1"></span>
                <strong>${item.fulltitle || '#'+item.item_id}</strong> — ${item.field_label}</li>`;
        });
        html += '</ul>';
        el.innerHTML = html;
    }

    new bootstrap.Modal(document.getElementById('modal-refs')).show();
}

// ── Drag & Drop fichiers (upload) ─────────────────────────────────────────
function onDragOver(event) {
    // Vérifier si c'est un fichier externe (upload) ou un fichier interne (move)
    if (event.dataTransfer.types.includes('Files')) {
        event.preventDefault();
        document.getElementById('drop-overlay').classList.remove('d-none');
    }
}

function onDragLeave(event) {
    if (!event.currentTarget.contains(event.relatedTarget)) {
        document.getElementById('drop-overlay').classList.add('d-none');
    }
}

async function onDrop(event) {
    event.preventDefault();
    document.getElementById('drop-overlay').classList.add('d-none');

    const files = event.dataTransfer.files;
    if (!files.length) return;

    const progressEl = document.getElementById('upload-progress');
    const bar        = document.getElementById('upload-bar');
    const status     = document.getElementById('upload-status');

    progressEl.classList.remove('d-none');

    for (let i = 0; i < files.length; i++) {
        const file = files[i];
        const pct  = Math.round(((i) / files.length) * 100);
        bar.style.width  = pct + '%';
        bar.textContent  = pct + '%';
        status.textContent = `Upload ${i+1}/${files.length} : ${file.name}`;

        const fd = new FormData();
        fd.append('<?php echo $token; ?>', '1');
        fd.append('context',  FILE_CONTEXT);
        fd.append('id',       FILE_ID);
        fd.append('subpath',  currentSubpath);
        fd.append('file',     file);

        await fetch(FILES_BASE + 'task=files.upload', {
            method: 'POST', body: fd,
            headers: {'X-Requested-With': 'XMLHttpRequest'}
        });
    }

    bar.style.width = '100%';
    bar.textContent = '100%';
    status.textContent = `${files.length} fichier(s) uploadé(s).`;

    setTimeout(() => progressEl.classList.add('d-none'), 2000);
    await loadFiles(currentSubpath);
}

function onFileDragStart(event, el) {
    draggedPath = el.dataset.path;
    event.dataTransfer.effectAllowed = 'move';
    event.dataTransfer.setData('text/plain', draggedPath);
}

// ── Helpers ───────────────────────────────────────────────────────────────
function buildFormData(extra = {}) {
    const fd = new FormData();
    fd.append(FILE_TOKEN, '1');
    fd.append('context', FILE_CONTEXT);
    fd.append('id',      FILE_ID);
    Object.entries(extra).forEach(([k, v]) => fd.append(k, v));
    return fd;
}

async function apiCall(task, fd) {
    try {
        const resp = await fetch(FILES_BASE + 'task=' + task, {
            method: 'POST', body: fd,
            headers: {'X-Requested-With': 'XMLHttpRequest'}
        });
        const json = await resp.json();
        if (!json.success) {
            showToast(json.message || 'Erreur', 'danger');
            return null;
        }
        return json.data;
    } catch(e) {
        console.error(e);
        showToast('Erreur réseau', 'danger');
        return null;
    }
}

function showToast(message, type = 'success') {
    const container = document.getElementById('toast-container') || (() => {
        const el = document.createElement('div');
        el.id = 'toast-container';
        el.className = 'position-fixed bottom-0 end-0 p-3';
        el.style.zIndex = 9999;
        document.body.appendChild(el);
        return el;
    })();

    const id = 'toast-' + Date.now();
    const html = `
    <div id="${id}" class="alert alert-${type} alert-dismissible" role="alert" style="min-width:300px">
        ${message}
        <button type="button" class="btn-close" onclick="document.getElementById('${id}').remove()"></button>
    </div>`;

    container.insertAdjacentHTML('beforeend', html);
    setTimeout(() => document.getElementById(id)?.remove(), 3000);
}
</script>