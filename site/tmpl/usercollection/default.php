<?php
defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Router\Route;
use Joomla\CMS\Session\Session;

$app        = Factory::getApplication();
$user       = $app->getIdentity();
$collection = $this->collection;
$type       = $this->currentType;
$showMatching = $this->showMatching ?? false;
$targetUser = $this->targetUser;
$token      = Session::getFormToken();

HTMLHelper::stylesheet(Juri::base() . 'components/com_collector/assets/css/collection.css');

// Champs à afficher en liste
$fieldsDisplayed = array_filter($this->fields, fn($f) => $f->_field->listing);

// URL de base pour les onglets
$baseTabUrl = Route::_('index.php?option=com_collector&view=usercollection&collection=' . $collection->id
    . ($this->isOwnProfile ? '' : '&user=' . $targetUser));
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
        const resp   = await fetch('index.php?option=com_collector&task=useritem.remove', {
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
        const resp   = await fetch('index.php?option=com_collector&task=useritem.setaccess', {
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
         href="<?php echo $baseTabUrl . '&type=' . (int) $userslist->type; ?>">
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
		<a class="nav-link <?php echo $showMatching ? 'active' : ''; ?>" href="<?php echo $baseTabUrl . '&type=matching'; ?>">
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
              <th class="small"><?php echo Text::_('COM_COLLECTOR_USERLIST_NOTE_TITLE'); ?></th>
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
              <td class="small text-muted fst-italic">
                <?php echo htmlspecialchars($item->userlist_note ?? '', ENT_QUOTES); ?>
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

</div><!-- collector-usercollection -->