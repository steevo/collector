<?php
defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Router\Route;
use Joomla\CMS\Session\Session;
use Joomla\CMS\Uri\Uri;

$app        = Factory::getApplication();
$user       = $app->getIdentity();
$collection = $this->collection;
$type       = $this->currentType;
$showMatching = $this->showMatching ?? false;
$targetUser = $this->targetUser;
$token      = Session::getFormToken();

HTMLHelper::stylesheet(Uri::base() . 'components/com_collector/assets/css/collection.css');
// Fenêtre de saisie de la note (mes listes)
HTMLHelper::_('bootstrap.modal', '.modal');

// Champs à afficher en liste
$fieldsDisplayed = array_filter($this->fields, fn($f) => $f->_field->listing);

// Adresse d'un onglet : le type fait partie de l'adresse avant le routage
// (ajouté après, il donnait « /mon-compte/mes-bulletins&type=0 » avec un lien de menu)
$tabUrl = fn ($type) => Route::_('index.php?option=com_collector&view=usercollection&collection=' . $collection->id
    . ($this->isOwnProfile ? '' : '&user=' . $targetUser) . '&type=' . $type);
?>

<script>
function initialiser()
{
	var form = jQuery('#adminForm');

	//jQuery( "[name='filter_order']" ).val('<?php // echo $defaultListOrder; ?>');
	//jQuery( "[name='filter_order_Dir']" ).val('<?php // echo $defaultListDirn; ?>');
	jQuery( "[name='filter_search_all']" ).val('');
	
	<?php
	foreach ( $this->fields as $field )
	{
		// if (( $field->_field->filter ) && ($field->isFiltered($this->params)==false)) {
		if ( $field->_field->filter ) {
			echo $field->resetSearchArea($this->params);
		}
	}
	?>
	
	form.submit();
}

function submitsearchbox()
{
	var form = jQuery('#adminForm');
	
	<?php
	foreach ( $this->fields as $field )
	{
		echo $field->submitSearchArea();
	}
	?>
	
	form.submit();
}

async function removeFromUserlist(itemId, userslistId, btn) {
    if (!confirm('<?php echo Text::_('COM_COLLECTOR_USERLIST_REMOVE_CONFIRM'); ?>')) return;

    btn.disabled = true;

    const fd = new FormData();
    fd.append('item',      itemId);
    fd.append('userslist', userslistId);
    fd.append('<?php echo Session::getFormToken(); ?>', '1');

    try {
        const resp   = await fetch('<?php echo \Joomla\CMS\Uri\Uri::base(true); ?>/index.php?option=com_collector&task=useritem.remove', {
            method  : 'POST',
            body    : fd,
            headers : {'X-Requested-With': 'XMLHttpRequest'}
        });
        const result = await resp.json();

        if (result.success) {
            window.location.reload();
        } else {
            btn.disabled = false;
            alert(result.message || '<?php echo Text::_('JERROR'); ?>');
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
    fd.append('userslist', '<?php echo (int) ($this->userslistId ?? 0); ?>');
    fd.append('note',      note);
    fd.append('<?php echo Session::getFormToken(); ?>', '1');

    try {
        const resp   = await fetch('<?php echo \Joomla\CMS\Uri\Uri::base(true); ?>/index.php?option=com_collector&task=useritem.updatenote', {
            method  : 'POST',
            body    : fd,
            headers : {'X-Requested-With': 'XMLHttpRequest'}
        });
        const result = await resp.json();

        if (!result.success) {
            throw new Error(result.message || '<?php echo Text::_('JERROR'); ?>');
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
    fd.append('<?php echo Session::getFormToken(); ?>', '1');

    try {
        const resp   = await fetch('<?php echo \Joomla\CMS\Uri\Uri::base(true); ?>/index.php?option=com_collector&task=useritem.setaccess', {
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
<?php /* ── En-tête ──────────────────────────────────────────────────────── */ ?>
<div class="collector-usercollection mb-3">

  <?php if (!$this->isOwnProfile && $this->targetUserName): ?>
  <h2 class="mb-3">
    <?php echo Text::sprintf('COM_COLLECTOR_USERCOLLECTION_TITLE_OTHER',
        htmlspecialchars($this->targetUserName, ENT_QUOTES),
        htmlspecialchars($collection->name, ENT_QUOTES)); ?>
  </h2>
  <?php else: ?>
  <h2 class="mb-3">
    <?php echo Text::sprintf('COM_COLLECTOR_USERCOLLECTION_TITLE_MINE',
        htmlspecialchars($collection->name, ENT_QUOTES)); ?>
  </h2>
  <?php endif; ?>

  <?php /* ── Onglets des types de listes ────────────────────────────────── */ ?>
  <ul class="nav nav-tabs">
    <?php foreach ($this->userslists as $userslist): ?>
    <li class="nav-item">
      <a class="nav-link <?php echo (!$showMatching && $userslist->type === $type) ? 'active' : ''; ?>"
         href="<?php echo $tabUrl((int) $userslist->type); ?>">
        <?php
        $icon = match((int) $userslist->type) {
            0 => 'icon-heart me-1',
            1 => 'icon-loop me-1',
            2 => 'icon-star me-1',
            default => 'icon-list me-1',
        };
        ?>
        <span class="<?php echo $icon; ?>"></span>
        <?php echo htmlspecialchars($userslist->name, ENT_QUOTES); ?>
      </a>
    </li>
    <?php endforeach; ?>

    <?php /* Onglet mise en relation — uniquement pour type 0 et 1 */ ?>
	<?php $totalMatches = array_sum(array_map(fn($m) => $m['want'] + $m['have'], $this->matchingUsers)); ?>
    <?php if (!empty($this->matchingUsers) && $this->isOwnProfile): ?>
	<li class="nav-item">
		<a class="nav-link <?php echo $showMatching ? 'active' : ''; ?>" href="<?php echo $tabUrl('matching'); ?>">
			<span class="icon-users me-1"></span>
			<?php echo Text::_('COM_COLLECTOR_USERCOLLECTION_MATCHING_TAB'); ?>
			<span class="badge bg-primary ms-1"><?php echo $totalMatches; ?></span>
		</a>
	</li>
	<?php endif; ?>
  </ul>

  <div class="tab-content">
    <?php if ($showMatching): ?>
    <?php /* ── Onglet mise en relation ─────────────────────────────────── */ ?>
    <div class="tab-pane active" id="matching">
		<table class="table table-sm table-hover">
			<thead class="table-light">
				<tr>
					<th><?php echo Text::_('COM_COLLECTOR_USERCOLLECTION_MATCHING_USER'); ?></th>
					<th class="text-center">
						<span class="icon-heart me-1"></span>
						<?php echo Text::_('COM_COLLECTOR_USERLIST_TYPE_0'); ?>
					</th>
					<th class="text-center">
						<span class="icon-loop me-1"></span>
						<?php echo Text::_('COM_COLLECTOR_USERLIST_TYPE_1'); ?>
					</th>
					<th></th>
				</tr>
			</thead>
			<tbody>
				<?php foreach ($this->matchingUsers as $match): ?>
				<?php if ($match['want'] > 0 || $match['have'] > 0): ?>
				<tr>
					<td><?php echo htmlspecialchars($match['user_name'], ENT_QUOTES); ?></td>
					<td class="text-center">
						<?php if ($match['want'] > 0): ?>
						<span class="badge bg-success"><?php echo $match['want']; ?></span>
						<?php else: ?>
						<span class="text-muted">—</span>
						<?php endif; ?>
					</td>
					<td class="text-center">
						<?php if ($match['have'] > 0): ?>
						<span class="badge bg-warning text-dark"><?php echo $match['have']; ?></span>
						<?php else: ?>
						<span class="text-muted">—</span>
						<?php endif; ?>
					</td>
					<td>
						<a href="<?php echo Route::_('index.php?option=com_collector&view=usercollection&collection='
							. $collection->id . '&user=' . (int) $match['user_id'] . '&type=0'); ?>"
						   class="btn btn-sm btn-outline-primary">
							<?php echo Text::_('COM_COLLECTOR_USERCOLLECTION_SEE_PROFILE'); ?>
						</a>
					</td>
				</tr>
				<?php endif; ?>
				<?php endforeach; ?>
			</tbody>
		</table>
    </div>
	<?php else: ?>
    <?php /* ── Onglet liste courante ───────────────────────────────────── */ ?>
    <div class="tab-pane active" id="list-tab">

      <?php /* Filtres */ ?>
      <form action="<?php echo Route::_('index.php?option=com_collector&view=usercollection&collection='
          . $collection->id . '&type=' . $type
          . ($this->isOwnProfile ? '' : '&user=' . $targetUser)); ?>"
            method="post" id="adminForm">
			
		<fieldset class="filters mb-0">
		  <?php if ($this->isOwnProfile && !$showMatching && $this->currentUserlist): ?>
			<div class="d-flex align-items-center gap-2 mb-3">
				<span class="small text-muted"><?php echo Text::_('COM_COLLECTOR_USERLIST_VISIBILITY'); ?></span>
				<?php
				$access     = (int) $this->currentUserlist->access;
				$userlistId = (int) $this->currentUserlist->id;
				// Type 2 (collection) → seulement privé/public
				// Type 0 et 1 → les 3 niveaux
				if ($type === 2) {
					$levels = [
						0 => ['label' => Text::_('COM_COLLECTOR_USERLIST_ACCESS_PRIVATE'), 'icon' => 'icon-lock',  'class' => 'btn-outline-secondary'],
						2 => ['label' => Text::_('COM_COLLECTOR_USERLIST_ACCESS_PUBLIC'),  'icon' => 'icon-globe', 'class' => 'btn-outline-success'],
					];
					// Si access vaut 1 (match only), le traiter comme privé pour la collection
					if ($access === 1) $access = 0;
				} else {
					$levels = [
						0 => ['label' => Text::_('COM_COLLECTOR_USERLIST_ACCESS_PRIVATE'),    'icon' => 'icon-lock',  'class' => 'btn-outline-secondary'],
						1 => ['label' => Text::_('COM_COLLECTOR_USERLIST_ACCESS_MATCH_ONLY'), 'icon' => 'icon-users', 'class' => 'btn-outline-warning'],
						2 => ['label' => Text::_('COM_COLLECTOR_USERLIST_ACCESS_PUBLIC'),      'icon' => 'icon-globe', 'class' => 'btn-outline-success'],
					];
				}
				foreach ($levels as $level => $info):
					$isActive = ($access === $level);
				?>
				<button type="button"
						class="btn btn-sm <?php echo $isActive ? str_replace('outline-', '', $info['class']) : $info['class']; ?>"
						onclick="setUserlistAccess(<?php echo $userlistId; ?>, <?php echo $level; ?>, this)"
						<?php echo $isActive ? 'disabled' : ''; ?>>
					<span class="<?php echo $info['icon']; ?> me-1"></span>
					<?php echo $info['label']; ?>
				</button>
				<?php endforeach; ?>

				<span id="access-ok"  class="text-success d-none"><span class="icon-check"></span></span>
				<span id="access-err" class="text-danger d-none"><span class="icon-cancel"></span></span>
			</div>
		  <?php endif; ?>
		  <div class="search-area">
			<?php /* Recherche globale */ ?>
			<div class="search-element">
				<input type="text" name="filter_search_all" value="<?php echo $this->escape($this->state->get('filter.filter_search_all')); ?>" class="inputbox" placeholder="<?php echo Text::_('JSEARCH_FILTER'); ?>" style="margin-bottom:0px" title="<?php echo Text::_('COM_COLLECTOR_FILTER_SEARCH_DESC'); ?>" />
			</div>
			
			<?php /* Filtres par champ */ ?>
			<?php $hasFilters = false; ?>
			<?php foreach ($this->fields as $field): ?>
			  <?php if ($field->_field->filter): ?>
				<?php $hasFilters = true; ?>
				<div class="search-element mb-2">
				  <?php echo $field->displayFilter($this->params, $this->state->get('filter.filterfield_' . $field->_field->tablecolumn)); ?>
				</div>
			  <?php endif; ?>
			<?php endforeach; ?>

			<input type="hidden" name="<?php echo $token; ?>" value="1">
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
		</fieldset>
      </form>

      <?php /* Liste des items */ ?>
      <?php if (empty($this->items)): ?>
      <div class="alert alert-info">
        <?php echo Text::_('COM_COLLECTOR_USERCOLLECTION_EMPTY'); ?>
      </div>

      <?php else: ?>
      <div class="table-responsive">
        <table class="table table-sm table-hover">
          <thead class="table-light">
            <tr>
              <?php foreach ($fieldsDisplayed as $field): ?>
              <th class="small">
                <?php echo htmlspecialchars($field->_field->field, ENT_QUOTES); ?>
              </th>
              <?php endforeach; ?>
              <th class="small"><?php echo Text::_('COM_COLLECTOR_USERLIST_NOTE'); ?></th>
			  <?php if ($this->isOwnProfile): ?>
			  <th style="width:40px"></th>
			  <?php endif; ?>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($this->items as $item): ?>
            <tr>
              <?php foreach ($fieldsDisplayed as $field): ?>
              <td class="small">
                <a href="<?php echo Route::_('index.php?option=com_collector&view=item&collection='
                    . $collection->id . '&id=' . $item->id); ?>">
                  <?php echo $field->display($item->{$field->_field->tablecolumn} ?? '', true, $this->params, $item->id); ?>
                </a>
              </td>
              <?php endforeach; ?>
              <?php $note = (string) ($item->userlist_note ?? ''); ?>
              <td class="small">
                <?php // Note du propriétaire de la liste (état, prix…) : lisible par ceux qui voient la liste ?>
                <span class="text-muted fst-italic" data-role="note-text"><?php echo nl2br(htmlspecialchars($note, ENT_QUOTES)); ?></span>
                <?php if ($this->isOwnProfile): ?>
                  <button type="button" class="btn btn-sm btn-link p-0 ms-1 align-baseline"
                          data-role="note-edit" data-item="<?php echo (int) $item->id; ?>"
                          data-note="<?php echo htmlspecialchars($note, ENT_QUOTES); ?>"
                          onclick="editUserlistNote(this)">
                    <?php echo Text::_($note === '' ? 'COM_COLLECTOR_USERLIST_NOTE_ADD' : 'COM_COLLECTOR_USERLIST_NOTE_EDIT'); ?>
                  </button>
                <?php endif; ?>
              </td>
				<?php if ($this->isOwnProfile): ?>
				<td class="text-end">
					<button type="button"
						  class="btn btn-sm btn-outline-danger"
						  onclick="removeFromUserlist(<?php echo $item->id; ?>, <?php echo $this->userslistId ?? 0; ?>, this)"
						  title="<?php echo Text::_('COM_COLLECTOR_USERLIST_REMOVE'); ?>">
					<span class="icon-trash"></span>
				</button>
			  </td>
			  <?php endif; ?>
            </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
      <?php echo $this->pagination->getListFooter(); ?>
      <?php endif; ?>

    </div>
	
    <?php endif; ?>


  </div><!-- tab-content -->

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

</div><!-- collector-usercollection -->