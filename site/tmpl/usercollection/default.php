<?php
/**
 * Joomla! component Collector
 *
 * Listes d'un membre (collection, souhaits, échanges) et échanges possibles.
 * Les objets sont présentés comme dans la collection : tableau ou template de liste
 * (gabarits communs collector.list.*), avec la note du membre et, sur mes listes,
 * le bouton de retrait.
 *
 * @package     Collector
 * @copyright   Copyright (C) 2010 - 2026 Philippe Ousset. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

defined('_JEXEC') or die;

use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Layout\LayoutHelper;
use Joomla\CMS\Router\Route;
use Joomla\CMS\Session\Session;
use Joomla\CMS\Uri\Uri;

$collection   = $this->collection;
$type         = $this->currentType;
$targetUser   = (int) $this->targetUser;
$token        = Session::getFormToken();
$layouts      = JPATH_SITE . '/components/com_collector/layouts';
$esc          = static fn ($text) => htmlspecialchars((string) $text, ENT_QUOTES, 'UTF-8');

HTMLHelper::stylesheet(Uri::base() . 'components/com_collector/assets/css/collection.css');
// Fenêtre de saisie de la note (mes listes)
HTMLHelper::_('bootstrap.modal', '.modal');

// Adresse d'un onglet : le type fait partie de l'adresse avant le routage
// (ajouté après, il donnait « /mon-compte/mes-bulletins&type=0 » avec un lien de menu)
$tabUrl = fn ($tab, $extra = '') => Route::_('index.php?option=com_collector&view=usercollection&collection=' . $collection->id
    . ($this->isOwnProfile ? '' : '&user=' . $targetUser) . '&type=' . $tab . $extra);

$itemLink = static fn ($item) => Route::_('index.php?option=com_collector&view=item&collection=' . $collection->slug . '&id=' . $item->slug);

// Note du membre sur un objet ; sur mes listes, avec « Ajouter » ou « Modifier »
$noteHtml = function ($item, bool $editable) use ($esc): string {
    $note = (string) ($item->userlist_note ?? '');

    if (!$editable && $note === '') {
        return '';
    }

    $html = '<span class="collector-userlist-note"><span class="text-muted fst-italic" data-role="note-text">' . nl2br($esc($note)) . '</span>';

    if ($editable) {
        $html .= ' <button type="button" class="btn btn-sm btn-link p-0 ms-1 align-baseline" data-role="note-edit"'
            . ' data-item="' . (int) $item->id . '" data-note="' . $esc($note) . '" onclick="editUserlistNote(this)">'
            . Text::_($note === '' ? 'COM_COLLECTOR_USERLIST_NOTE_ADD' : 'COM_COLLECTOR_USERLIST_NOTE_EDIT') . '</button>';
    }

    return $html . '</span>';
};

// Bouton de retrait d'un objet de ma liste
$removeHtml = fn ($item): string => '<button type="button" class="btn btn-sm btn-outline-danger collector-userlist-remove"'
    . ' onclick="removeFromUserlist(' . (int) $item->id . ', ' . (int) $this->userslistId . ', this)"'
    . ' title="' . Text::_('COM_COLLECTOR_USERLIST_REMOVE') . '" aria-label="' . Text::_('COM_COLLECTOR_USERLIST_REMOVE') . '">'
    . '<span class="icon-trash" aria-hidden="true"></span></button>';

/**
 * Liste d'objets, présentée comme la collection (tableau ou template choisi).
 * $editable : mes listes (note modifiable, bouton de retrait) ; $sortable : en-têtes de tri du tableau.
 */
$renderList = function (array $items, bool $editable, bool $sortable) use ($layouts, $itemLink, $noteHtml, $removeHtml): string {
    if ($this->listTemplate) {
        $tags = ['note' => fn ($item) => $noteHtml($item, $editable)];

        if ($editable) {
            $tags['actions'] = $removeHtml;
        }

        return LayoutHelper::render('collector.list.template', [
            'template' => $this->listTemplate,
            'fields'   => $this->fields,
            'items'    => $items,
            'params'   => $this->params,
            'link'     => $itemLink,
            'tags'     => $tags,
        ], $layouts);
    }

    $columns = [[
        'label' => Text::_('COM_COLLECTOR_USERLIST_NOTE'),
        'class' => '',
        'cell'  => fn ($item) => $noteHtml($item, $editable),
    ]];

    if ($editable) {
        $columns[] = ['label' => '', 'class' => 'text-end', 'cell' => $removeHtml];
    }

    return LayoutHelper::render('collector.list.table', [
        'fields'    => $this->fields,
        'items'     => $items,
        'params'    => $this->params,
        'link'      => $itemLink,
        'sortable'  => $sortable,
        'listOrder' => $sortable ? $this->escape($this->state->get('list.ordering')) : '',
        'listDirn'  => $sortable ? $this->escape($this->state->get('list.direction')) : '',
        'columns'   => $columns,
    ], $layouts);
};

$viewSwitch = function ($tab) use ($tabUrl, $layouts): string {
    if (!$this->listViews) {
        return '';
    }

    return LayoutHelper::render('collector.list.switch', [
        'views'  => $this->listViews,
        'active' => $this->listView,
        'url'    => fn ($id) => $tabUrl($tab, '&vue=' . (int) $id),
    ], $layouts);
};

$showPagination = in_array((int) $this->params->def('show_pagination', 2), [1, 2], true);
?>

<script>
function initialiser()
{
	var form = document.adminForm;

	jQuery( "[name='filter_search_all']" ).val('');
	form.filter_order.value = '<?php echo $this->escape($this->state->get('list.default.ordering')); ?>';
	form.filter_order_Dir.value = '<?php echo $this->escape($this->state->get('list.default.direction')); ?>';

	<?php
	foreach ( $this->fields as $field )
	{
		if ( $field->_field->filter ) {
			echo $field->resetSearchArea($this->params);
		}
	}
	?>

	form.submit();
}

function submitsearchbox()
{
	var form = document.adminForm;

	<?php
	foreach ( $this->fields as $field )
	{
		echo $field->submitSearchArea();
	}
	?>

	form.limitstart.value = 0;
	form.submit();
}

async function removeFromUserlist(itemId, userslistId, btn) {
    if (!confirm('<?php echo Text::_('COM_COLLECTOR_USERLIST_REMOVE_CONFIRM', true); ?>')) return;

    btn.disabled = true;

    const fd = new FormData();
    fd.append('item',      itemId);
    fd.append('userslist', userslistId);
    fd.append('<?php echo $token; ?>', '1');

    try {
        const resp   = await fetch('<?php echo Uri::base(true); ?>/index.php?option=com_collector&task=useritem.remove', {
            method  : 'POST',
            body    : fd,
            headers : {'X-Requested-With': 'XMLHttpRequest'}
        });
        const result = await resp.json();

        if (result.success) {
            window.location.reload();
        } else {
            btn.disabled = false;
            alert(result.message || '<?php echo Text::_('JERROR', true); ?>');
        }
    } catch(e) {
        btn.disabled = false;
        console.error(e);
    }
}

// Note d'un objet de ma liste : « Ajouter » sans note, « Modifier » avec
let noteButton = null;

function editUserlistNote(btn) {
    noteButton = btn;
    document.getElementById('userlist-note-input').value = btn.dataset.note || '';
    document.getElementById('userlist-note-error').classList.add('d-none');
    const modal = document.getElementById('modal-userlist-note');
    modal.addEventListener('shown.bs.modal', () => document.getElementById('userlist-note-input').focus(), { once: true });
    bootstrap.Modal.getOrCreateInstance(modal).show();
}

async function saveUserlistNote() {
    if (!noteButton) return;

    const note  = document.getElementById('userlist-note-input').value.trim();
    const error = document.getElementById('userlist-note-error');
    const fd    = new FormData();
    fd.append('item',      noteButton.dataset.item);
    fd.append('userslist', '<?php echo (int) $this->userslistId; ?>');
    fd.append('note',      note);
    fd.append('<?php echo $token; ?>', '1');

    try {
        const resp   = await fetch('<?php echo Uri::base(true); ?>/index.php?option=com_collector&task=useritem.updatenote', {
            method  : 'POST',
            body    : fd,
            headers : {'X-Requested-With': 'XMLHttpRequest'}
        });
        const result = await resp.json();

        if (!result.success) {
            throw new Error(result.message || '<?php echo Text::_('JERROR', true); ?>');
        }

        // Texte de la note et libellé du bouton mis à jour sans recharger la page
        const text = noteButton.parentElement.querySelector('[data-role="note-text"]');
        text.textContent = note;
        text.innerHTML = text.innerHTML.replace(/\n/g, '<br>');
        noteButton.dataset.note = note;
        noteButton.textContent = note === ''
            ? '<?php echo Text::_('COM_COLLECTOR_USERLIST_NOTE_ADD', true); ?>'
            : '<?php echo Text::_('COM_COLLECTOR_USERLIST_NOTE_EDIT', true); ?>';

        bootstrap.Modal.getInstance(document.getElementById('modal-userlist-note'))?.hide();
        noteButton.focus();
    } catch (e) {
        error.textContent = e.message;
        error.classList.remove('d-none');
    }
}

async function setUserlistAccess(userlistId, access, clickedBtn) {
    // Désactiver tous les boutons pendant la requête
    document.querySelectorAll('[onclick^="setUserlistAccess"]').forEach(b => b.disabled = true);
    document.getElementById('access-ok')?.classList.add('d-none');
    document.getElementById('access-err')?.classList.add('d-none');

    const fd = new FormData();
    fd.append('userlist', userlistId);
    fd.append('access',   access);
    fd.append('<?php echo $token; ?>', '1');

    try {
        const resp   = await fetch('<?php echo Uri::base(true); ?>/index.php?option=com_collector&task=useritem.setaccess', {
            method  : 'POST',
            body    : fd,
            headers : {'X-Requested-With': 'XMLHttpRequest'}
        });
        const result = await resp.json();

        if (result.success) {
            // Mettre à jour l'apparence des boutons
            document.querySelectorAll('[onclick^="setUserlistAccess"]').forEach(b => {
                b.disabled  = false;
                const btnAccess = parseInt(b.getAttribute('onclick').match(/,\s*(\d)/)[1]);
                if (btnAccess === access) {
                    b.classList.remove('btn-outline-secondary', 'btn-outline-warning', 'btn-outline-success');
                    b.classList.add(btnAccess === 0 ? 'btn-secondary' : btnAccess === 1 ? 'btn-warning' : 'btn-success');
                    b.disabled = true;
                } else {
                    b.classList.remove('btn-secondary', 'btn-warning', 'btn-success');
                    b.classList.add(btnAccess === 0 ? 'btn-outline-secondary' : btnAccess === 1 ? 'btn-outline-warning' : 'btn-outline-success');
                }
            });
            document.getElementById('access-ok')?.classList.remove('d-none');
            setTimeout(() => document.getElementById('access-ok')?.classList.add('d-none'), 2000);
        } else {
            document.querySelectorAll('[onclick^="setUserlistAccess"]').forEach(b => b.disabled = false);
            document.getElementById('access-err')?.classList.remove('d-none');
        }
    } catch(e) {
        document.querySelectorAll('[onclick^="setUserlistAccess"]').forEach(b => b.disabled = false);
        document.getElementById('access-err')?.classList.remove('d-none');
    }
}
</script>

<div class="collector-usercollection category-list mb-3">

  <?php /* ── En-tête ── */ ?>
  <h2 class="mb-3">
    <?php if (!$this->isOwnProfile && $this->targetUserName): ?>
      <?php echo Text::sprintf('COM_COLLECTOR_USERCOLLECTION_TITLE_OTHER', $esc($this->targetUserName), $esc($collection->name)); ?>
    <?php else: ?>
      <?php echo Text::sprintf('COM_COLLECTOR_USERCOLLECTION_TITLE_MINE', $esc($collection->name)); ?>
    <?php endif; ?>
  </h2>

  <?php /* ── Onglets : listes, puis échanges possibles ── */ ?>
  <ul class="nav nav-tabs mb-3">
    <?php foreach ($this->userslists as $userslist): ?>
    <li class="nav-item">
      <a class="nav-link <?php echo (int) $userslist->type === $type ? 'active' : ''; ?>" href="<?php echo $tabUrl((int) $userslist->type); ?>">
        <?php
        $icon = match((int) $userslist->type) {
            0 => 'icon-heart',
            1 => 'icon-loop',
            2 => 'icon-star',
            default => 'icon-list',
        };
        ?>
        <span class="<?php echo $icon; ?> me-1" aria-hidden="true"></span>
        <?php echo $esc($userslist->name); ?>
      </a>
    </li>
    <?php endforeach; ?>

    <?php if ($this->isOwnProfile && !empty($this->matchingUsers)): ?>
      <?php $totalMatches = array_sum(array_map(fn ($m) => $m['want'] + $m['have'], $this->matchingUsers)); ?>
    <li class="nav-item">
      <a class="nav-link <?php echo $type === 'matching' ? 'active' : ''; ?>" href="<?php echo $tabUrl('matching'); ?>">
        <span class="icon-users me-1" aria-hidden="true"></span>
        <?php echo Text::_('COM_COLLECTOR_USERCOLLECTION_MATCHING_TAB'); ?>
        <span class="badge bg-primary ms-1"><?php echo $totalMatches; ?></span>
      </a>
    </li>
    <?php elseif (!$this->isOwnProfile && $this->matchesCount > 0): ?>
    <li class="nav-item">
      <a class="nav-link <?php echo $type === 'matches' ? 'active' : ''; ?>" href="<?php echo $tabUrl('matches'); ?>">
        <span class="icon-users me-1" aria-hidden="true"></span>
        <?php echo Text::_('COM_COLLECTOR_USERCOLLECTION_MATCHING_TAB'); ?>
        <span class="badge bg-primary ms-1"><?php echo (int) $this->matchesCount; ?></span>
      </a>
    </li>
    <?php endif; ?>
  </ul>

  <?php if ($type === 'matching'): ?>
  <?php /* ── Mes échanges possibles : un membre par ligne ── */ ?>
  <table class="table table-sm table-hover">
    <thead class="table-light">
      <tr>
        <th><?php echo Text::_('COM_COLLECTOR_USERCOLLECTION_MATCHING_USER'); ?></th>
        <th class="text-center"><span class="icon-heart me-1" aria-hidden="true"></span><?php echo Text::_('COM_COLLECTOR_USERCOLLECTION_MATCHES_WANT_SHORT'); ?></th>
        <th class="text-center"><span class="icon-loop me-1" aria-hidden="true"></span><?php echo Text::_('COM_COLLECTOR_USERCOLLECTION_MATCHES_HAVE_SHORT'); ?></th>
        <th></th>
      </tr>
    </thead>
    <tbody>
      <?php foreach ($this->matchingUsers as $match): ?>
      <tr>
        <td><?php echo $esc($match['user_name']); ?></td>
        <td class="text-center">
          <?php echo $match['want'] > 0 ? '<span class="badge bg-success">' . $match['want'] . '</span>' : '<span class="text-muted">—</span>'; ?>
        </td>
        <td class="text-center">
          <?php echo $match['have'] > 0 ? '<span class="badge bg-warning text-dark">' . $match['have'] . '</span>' : '<span class="text-muted">—</span>'; ?>
        </td>
        <td class="text-end">
          <a href="<?php echo Route::_('index.php?option=com_collector&view=usercollection&collection=' . $collection->id
              . '&user=' . (int) $match['user_id'] . '&type=matches'); ?>" class="btn btn-sm btn-outline-primary">
            <?php echo Text::_('COM_COLLECTOR_USERCOLLECTION_SEE_MATCHES'); ?>
          </a>
        </td>
      </tr>
      <?php endforeach; ?>
    </tbody>
  </table>

  <?php elseif ($type === 'matches'): ?>
  <?php /* ── Échanges possibles avec ce membre : objets en correspondance, présentés comme la collection ── */ ?>
  <div class="collector-matches">
    <?php if ($this->contacts): ?>
    <div class="d-flex flex-wrap align-items-center gap-2 mb-3">
      <span><?php echo Text::sprintf('COM_COLLECTOR_USERCOLLECTION_CONTACT', $esc($this->targetUserName)); ?></span>
      <?php foreach ($this->contacts as $contact): ?>
      <a class="btn btn-sm btn-primary" href="<?php echo $esc($contact['url']); ?>">
        <span class="<?php echo $esc($contact['icon']); ?> me-1" aria-hidden="true"></span><?php echo $esc($contact['label']); ?>
      </a>
      <?php endforeach; ?>
    </div>
    <?php endif; ?>

    <?php echo $viewSwitch('matches'); ?>

    <?php if (!$this->matchesWant && !$this->matchesHave): ?>
    <div class="alert alert-info"><?php echo Text::sprintf('COM_COLLECTOR_USERCOLLECTION_MATCHES_NONE', $esc($this->targetUserName)); ?></div>
    <?php endif; ?>

    <?php foreach ([
        ['items' => $this->matchesWant, 'title' => 'COM_COLLECTOR_USERCOLLECTION_MATCHES_WANT', 'icon' => 'icon-heart'],
        ['items' => $this->matchesHave, 'title' => 'COM_COLLECTOR_USERCOLLECTION_MATCHES_HAVE', 'icon' => 'icon-loop'],
    ] as $section): ?>
      <?php if ($section['items']): ?>
    <section class="mb-4">
      <h3 class="h5">
        <span class="<?php echo $section['icon']; ?> me-1" aria-hidden="true"></span>
        <?php echo Text::sprintf($section['title'], $esc($this->targetUserName)); ?>
        <span class="badge bg-secondary ms-1"><?php echo count($section['items']); ?></span>
      </h3>
      <?php echo $renderList($section['items'], false, false); ?>
    </section>
      <?php endif; ?>
    <?php endforeach; ?>
  </div>

  <?php else: ?>
  <?php /* ── Liste courante ── */ ?>
  <form action="<?php echo $tabUrl($type); ?>" method="post" name="adminForm" id="adminForm">
    <fieldset class="filters mb-0">
      <?php if ($this->isOwnProfile && $this->currentUserlist): ?>
      <div class="d-flex flex-wrap align-items-center gap-2 mb-3">
        <span class="small text-muted"><?php echo Text::_('COM_COLLECTOR_USERLIST_VISIBILITY'); ?></span>
        <?php
        $access     = (int) $this->currentUserlist->access;
        $userlistId = (int) $this->currentUserlist->id;
        // Collection (type 2) : privée ou publique ; souhaits et échanges : les 3 niveaux
        $levels = [
            0 => ['label' => Text::_('COM_COLLECTOR_USERLIST_ACCESS_PRIVATE'),    'icon' => 'icon-lock',  'class' => 'btn-outline-secondary'],
            1 => ['label' => Text::_('COM_COLLECTOR_USERLIST_ACCESS_MATCH_ONLY'), 'icon' => 'icon-users', 'class' => 'btn-outline-warning'],
            2 => ['label' => Text::_('COM_COLLECTOR_USERLIST_ACCESS_PUBLIC'),     'icon' => 'icon-globe', 'class' => 'btn-outline-success'],
        ];
        if ($type === 2) {
            unset($levels[1]);
            // l'ancienne valeur « matchs uniquement » vaut privée pour une collection
            if ($access === 1) {
                $access = 0;
            }
        }
        foreach ($levels as $level => $info):
            $isActive = ($access === $level);
        ?>
        <button type="button"
                class="btn btn-sm <?php echo $isActive ? str_replace('outline-', '', $info['class']) : $info['class']; ?>"
                onclick="setUserlistAccess(<?php echo $userlistId; ?>, <?php echo $level; ?>, this)"
                <?php echo $isActive ? 'disabled' : ''; ?>>
          <span class="<?php echo $info['icon']; ?> me-1" aria-hidden="true"></span>
          <?php echo $info['label']; ?>
        </button>
        <?php endforeach; ?>
        <span id="access-ok"  class="text-success d-none"><span class="icon-check"></span></span>
        <span id="access-err" class="text-danger d-none"><span class="icon-cancel"></span></span>
      </div>
      <?php endif; ?>

      <div class="search-area">
        <div class="search-element">
          <input type="text" name="filter_search_all" value="<?php echo $this->escape($this->state->get('filter.filter_search_all')); ?>" class="inputbox" placeholder="<?php echo Text::_('JSEARCH_FILTER'); ?>" style="margin-bottom:0px" title="<?php echo Text::_('COM_COLLECTOR_FILTER_SEARCH_DESC'); ?>" />
        </div>
        <?php foreach ($this->fields as $field): ?>
          <?php if ($field->_field->filter): ?>
        <div class="search-element">
          <?php echo $field->displayFilter($this->params, $this->state->get('filter.filterfield_' . $field->_field->tablecolumn)); ?>
        </div>
          <?php endif; ?>
        <?php endforeach; ?>
      </div>
      <div class="search-area">
        <div class="btn-group">
          <button type="button" class="btn btn-primary" onclick="submitsearchbox()">
            <span class="icon-search"></span>&#160;<?php echo Text::_('COM_COLLECTOR_SEARCH') ?>
          </button>
        </div>
        <div class="btn-group">
          <button type="button" class="btn" onclick="initialiser();">
            <span class="icon-cancel"></span>&#160;<?php echo Text::_('COM_COLLECTOR_INIT') ?>
          </button>
        </div>
      </div>

      <?php if ($this->items && $this->params->get('show_pagination_limit')): ?>
      <div class="btn-group float-end mb-0">
        <label for="limit" class="visually-hidden"><?php echo Text::_('JGLOBAL_DISPLAY_NUM'); ?></label>
        <?php echo $this->pagination->getLimitBox(); ?>
      </div>
      <?php endif; ?>

      <input type="hidden" name="filter_order" value="<?php echo $this->escape($this->state->get('list.ordering')); ?>" />
      <input type="hidden" name="filter_order_Dir" value="<?php echo $this->escape($this->state->get('list.direction')); ?>" />
      <input type="hidden" name="limitstart" value="" />
      <input type="hidden" name="<?php echo $token; ?>" value="1" />
    </fieldset>

    <?php if (empty($this->items)): ?>
    <div class="alert alert-info"><?php echo Text::_('COM_COLLECTOR_USERCOLLECTION_EMPTY'); ?></div>
    <?php else: ?>

    <?php echo $viewSwitch($type); ?>

    <?php if ($showPagination): ?>
    <div>
      <?php if ($this->params->def('show_pagination_results', 1)): ?>
      <p class="counter float-end pt-3 pe-2"><?php echo $this->pagination->getResultsCounter(); ?></p>
      <?php endif; ?>
      <div><?php echo $this->pagination->getPagesLinks(); ?></div>
    </div>
    <?php endif; ?>

    <?php echo $renderList($this->items, $this->isOwnProfile, true); ?>

    <?php if ($showPagination && $this->pagination->pagesTotal > 1): ?>
    <div>
      <?php if ($this->params->def('show_pagination_results', 1)): ?>
      <p class="counter float-end pt-3 pe-2"><?php echo $this->pagination->getResultsCounter(); ?></p>
      <?php endif; ?>
      <div><?php echo $this->pagination->getPagesLinks(); ?></div>
    </div>
    <?php endif; ?>

    <?php endif; ?>
  </form>
  <?php endif; ?>

  <?php if ($this->isOwnProfile): ?>
  <?php /* Saisie de la note d'un objet de ma liste */ ?>
  <div class="modal fade" id="modal-userlist-note" tabindex="-1" aria-labelledby="modal-userlist-note-title" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-sm">
      <div class="modal-content">
        <div class="modal-header">
          <h2 class="modal-title fs-6" id="modal-userlist-note-title"><?php echo Text::_('COM_COLLECTOR_USERLIST_NOTE'); ?></h2>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="<?php echo Text::_('JCLOSE'); ?>"></button>
        </div>
        <div class="modal-body">
          <textarea id="userlist-note-input" class="form-control form-control-sm" rows="3"
                    aria-labelledby="modal-userlist-note-title"
                    placeholder="<?php echo Text::_('COM_COLLECTOR_USERLIST_NOTE_PLACEHOLDER'); ?>"></textarea>
          <p class="small text-danger mt-2 mb-0 d-none" id="userlist-note-error"></p>
        </div>
        <div class="modal-footer p-2">
          <button type="button" class="btn btn-sm btn-outline-secondary" data-bs-dismiss="modal"><?php echo Text::_('JCANCEL'); ?></button>
          <button type="button" class="btn btn-sm btn-primary" onclick="saveUserlistNote()"><?php echo Text::_('JSAVE'); ?></button>
        </div>
      </div>
    </div>
  </div>
  <?php endif; ?>
</div>
