<?php
defined('_JEXEC') or die;

use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Router\Route;
use Joomla\CMS\Session\Session;

$token = Session::getFormToken();
?>

<div class="row mb-3">

    <?php /* ── Sélecteur contexte ──────────────────────────────────────── */ ?>
    <div class="col-md-3">
        <select id="files-context" class="form-select form-select-sm" onchange="switchContext(this.value)">
            <optgroup label="<?php echo Text::_('COM_COLLECTOR_FILES_CONTEXT_COLLECTIONS'); ?>">
                <?php foreach ($this->collections as $col): ?>
                <option value="collection:<?php echo $col->id; ?>"
                        <?php echo ($this->context === 'collection' && $this->contextId == $col->id) ? 'selected' : ''; ?>>
                    <?php echo $this->escape($col->name); ?>
                </option>
                <?php endforeach; ?>
            </optgroup>
            <optgroup label="<?php echo Text::_('COM_COLLECTOR_FILES_CONTEXT_DEFINE'); ?>">
                <?php foreach ($this->defines as $def): ?>
                <option value="define:<?php echo $def->id; ?>"
                        <?php echo ($this->context === 'define' && $this->contextId == $def->id) ? 'selected' : ''; ?>>
                    <?php echo $this->escape($def->name); ?>
                </option>
                <?php endforeach; ?>
            </optgroup>
        </select>
    </div>

    <?php /* ── Barre d'actions ──────────────────────────────────────────── */ ?>
    <div class="col-md-9 d-flex gap-2 align-items-center">
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
</div>

<?php if (!$this->contextId): ?>
<div class="alert alert-info"><?php echo Text::_('COM_COLLECTOR_FILES_CHOOSE_CONTEXT'); ?></div>
<?php else: ?>

<div class="row" style="min-height: 500px;">

    <?php /* ── Arborescence dossiers ─────────────────────────────────── */ ?>
    <div class="col-md-3 border-end pe-3">
        <div id="folder-tree" class="small">
            <?php echo Text::_('COM_COLLECTOR_FILES_LOADING'); ?>
        </div>
    </div>

    <?php /* ── Panneau fichiers ─────────────────────────────────────── */ ?>
    <div class="col-md-9">

        <?php /* Fil d'Ariane */ ?>
        <nav aria-label="breadcrumb" class="mb-2">
            <ol class="breadcrumb small" id="breadcrumb">
                <li class="breadcrumb-item"><a href="#" onclick="navigateTo(''); return false;">
                    <span class="icon-home"></span>
                </a></li>
            </ol>
        </nav>

        <?php /* Zone de drop + grille */ ?>
        <div id="dropzone"
             class="border rounded p-2 position-relative"
             style="min-height: 400px;"
             ondragover="onDragOver(event)"
             ondragleave="onDragLeave(event)"
             ondrop="onDrop(event)">

            <div id="drop-overlay" class="d-none position-absolute top-0 start-0 w-100 h-100 d-flex align-items-center justify-content-center bg-primary bg-opacity-10 border border-primary rounded" style="z-index:10;">
                <span class="icon-upload fs-1 text-primary"></span>
                <span class="ms-2 text-primary fw-bold"><?php echo Text::_('COM_COLLECTOR_FILES_DROP_HERE'); ?></span>
            </div>

            <div id="files-grid" class="row g-2">
                <?php echo Text::_('COM_COLLECTOR_FILES_LOADING'); ?>
            </div>

        </div>

        <?php /* Barre de progression upload */ ?>
        <div id="upload-progress" class="mt-2 d-none">
            <div class="progress" style="height:20px;">
                <div class="progress-bar progress-bar-striped progress-bar-animated"
                     id="upload-bar" style="width:0%">0%</div>
            </div>
            <div class="small text-muted mt-1" id="upload-status"></div>
        </div>

    </div>
</div>

<?php /* ── Modales ──────────────────────────────────────────────────── */ ?>

<?php /* Modale nouveau dossier */ ?>
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

<?php /* Modale renommer */ ?>
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

<?php /* Modale déplacer */ ?>
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

<?php /* Modale références */ ?>
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

<script>
const FILE_TOKEN   = '<?php echo $token; ?>';
const FILE_CONTEXT = '<?php echo $this->escape($this->context); ?>';
const FILE_ID      = <?php echo (int) $this->contextId; ?>;
const BASE_URL     = 'index.php?option=com_collector&';

// ── State ─────────────────────────────────────────────────────────────────
let currentSubpath  = '';
let selectedFiles   = new Set();
let moveTargetPath  = '';

// ── Init ──────────────────────────────────────────────────────────────────
<?php if ($this->contextId): ?>
document.addEventListener('DOMContentLoaded', () => {
    loadTree();
    loadFiles('');
});
<?php endif; ?>

// ── Navigation ────────────────────────────────────────────────────────────
function switchContext(value) {
    const [ctx, id] = value.split(':');
    window.location.href = `index.php?option=com_collector&view=files&context=${ctx}&id=${id}`;
}

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

        await fetch(BASE_URL + 'task=files.upload', {
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

// Drag & Drop interne (move par glisser-déposer)
let draggedPath = null;

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
        const resp = await fetch(BASE_URL + 'task=' + task, {
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

    const id   = 'toast-' + Date.now();
    const html = `
    <div id="${id}" class="toast align-items-center text-white bg-${type} border-0" role="alert">
        <div class="d-flex">
            <div class="toast-body">${message}</div>
            <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"></button>
        </div>
    </div>`;

    container.insertAdjacentHTML('beforeend', html);
    const toastEl = document.getElementById(id);
    new bootstrap.Toast(toastEl, { delay: 3000 }).show();
    toastEl.addEventListener('hidden.bs.toast', () => toastEl.remove());
}
</script>