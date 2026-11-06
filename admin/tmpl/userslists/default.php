<?php
/**
 * Joomla! 3.0 component Collector
 *
 * @package 	Collector
 * @copyright   Copyright (C) 2010 - 2020 Philippe Ousset. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 *
 * Collector is a Multi Purpose Listing Tool.
 * Originaly developped to list Collections
 * it can be used for several purpose.
 */

// no direct access
defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Layout\LayoutHelper;
use Joomla\CMS\Router\Route;
use Joomla\CMS\Session\Session;

HTMLHelper::_('behavior.multiselect');

$user       = Factory::getApplication()->getIdentity();
$userId     = $user->get('id');
$listOrder  = $this->escape($this->state->get('list.ordering'));
$listDirn   = $this->escape($this->state->get('list.direction'));
$trashed    = $this->state->get('filter.published') == -2;
$saveOrder  = ($listOrder === 'cu.ordering' && strtolower($listDirn) === 'asc');
$collection = $this->collection;

if ($saveOrder && !empty($this->items))
{
	$saveOrderingUrl = 'index.php?option=com_collector&task=userslists.saveOrderAjax&tmpl=component&' . Session::getFormToken() . '=1';
	HTMLHelper::_('draggablelist.draggable');
}
?>

<form action="<?php echo Route::_('index.php?option=com_collector&view=userslists'); ?>"
      method="post" name="adminForm" id="adminForm">

  <?php
	// Ajouter selectorFieldName pour la collection
	echo LayoutHelper::render('joomla.searchtools.default', [
		'view'    => $this,
		'options' => ['selectorFieldName' => 'collection']
	]);
	?>
  <?php if (empty($this->items)): ?>
  <div class="alert alert-info">
    <?php echo Text::_('JGLOBAL_NO_MATCHING_RESULTS'); ?>
  </div>

  <?php else: ?>
  <table class="table table-striped" id="userslistList">
    <thead>
	  <tr>
	    <th class="w-1 text-center" style="display:none">
		  <?php echo HTMLHelper::_('grid.checkall'); ?>
		</th>
		<th class="w-1 text-center d-none d-md-table-cell">
		  <?php echo HTMLHelper::_('searchtools.sort', '', 'cu.ordering', $listDirn, $listOrder, null, 'asc', 'JGRID_HEADING_ORDERING', 'icon-menu-2'); ?>
		</th>
		<th class="w-1 text-center">
		  <?php echo Text::_('JSTATUS'); ?>
		</th>
		<th>
		  <?php echo Text::_('JGLOBAL_TITLE'); ?>
		</th>
		<th class="w-15 text-center">
		  <?php echo Text::_('JGRID_HEADING_ACCESS'); ?>
		</th>
		<th class="w-5 text-center">
		  <?php echo Text::_('JGRID_HEADING_ID'); ?>
		</th>
	  </tr>
	</thead>
    <tbody <?php if ($saveOrder) :
        ?> class="js-draggable" data-url="<?php echo $saveOrderingUrl; ?>" data-direction="<?php echo strtolower($listDirn); ?>" data-nested="true"<?php
                          endif; ?>>
      <?php 
	  $accessLevels = HTMLHelper::_('access.assetgroups');
	  foreach ($this->items as $i => $item):
        $canEdit    = $user->authorise('core.edit',       'com_collector');
        $canChange  = $user->authorise('core.edit.state', 'com_collector');
      ?>
      <tr class="row<?php echo $i % 2; ?>" data-draggable-group="<?php echo (int) $item->collection; ?>">
	    <td class="text-center" style="display:none">
		  <?php echo HTMLHelper::_('grid.id', $i, $item->id); ?>
		</td>
		<td class="text-center d-none d-md-table-cell">
			<?php
			$iconClass = '';
			if (!$canChange) {
				$iconClass = ' inactive';
			} elseif (!$saveOrder) {
				$iconClass = ' inactive" title="' . Text::_('JORDERINGDISABLED');
			}
			?>
			<span class="sortable-handler<?php echo $iconClass ?>">
				<span class="icon-ellipsis-v" aria-hidden="true"></span>
			</span>
			<?php if ($canChange && $saveOrder) : ?>
				<input type="text" name="order[]" size="5" value="<?php echo $item->ordering; ?>" class="width-20 text-area-order hidden">
			<?php endif; ?>
		</td>
        <td class="text-center">
          <?php echo HTMLHelper::_('jgrid.published', $item->state, $i, 'userslists.', $canChange, 'cb'); ?>
		  
        </td>
        <td>
		  <?php echo $this->escape($item->name); ?>
		  <small class="text-muted ms-1">(<?php echo Text::_('COM_COLLECTOR_USERLIST_TYPE_' . $item->type); ?>)</small>
	    </td>
		<td class="text-center">
			<div class="d-flex align-items-center justify-content-center gap-1">
				<select class="form-select form-select-sm"
						id="access_<?php echo $item->id; ?>"
						onchange="updateUserslistAccess(<?php echo $item->id; ?>, this)">
					<?php echo HTMLHelper::_('select.options',
						HTMLHelper::_('access.assetgroups'),
						'value', 'text', $item->access); ?>
				</select>
				<span id="access_ok_<?php echo $item->id; ?>"
					  class="text-success d-none"
					  title="<?php echo Text::_('JSAVED'); ?>">
					<span class="icon-check"></span>
				</span>
				<span id="access_err_<?php echo $item->id; ?>"
					  class="text-danger d-none"
					  title="<?php echo Text::_('JERROR'); ?>">
					<span class="icon-cancel"></span>
				</span>
			</div>
		</td>
        <td class="text-center d-none d-md-table-cell">
          <?php echo (int) $item->id; ?>
        </td>
      </tr>
      <?php endforeach; ?>
    </tbody>
  </table>
  <?php endif; ?>

  <?php echo $this->pagination->getListFooter(); ?>

  <input type="hidden" name="task" value="">
  <input type="hidden" name="boxchecked" value="0">
  <?php echo HTMLHelper::_('form.token'); ?>
</form>

<script>
document.querySelectorAll('[name="order[]"]').forEach(el => console.log(el, el.value));

async function updateUserslistAccess(id, select) {
    const okEl  = document.getElementById('access_ok_'  + id);
    const errEl = document.getElementById('access_err_' + id);
    
    // Masquer les indicateurs précédents
    okEl?.classList.add('d-none');
    errEl?.classList.add('d-none');

    const fd = new FormData();
    fd.append('id',     id);
    fd.append('access', select.value);
    fd.append('<?php echo Session::getFormToken(); ?>', '1');

    try {
        const resp   = await fetch('index.php?option=com_collector&task=userslists.setaccess', {
            method: 'POST', body: fd
        });
        const result = await resp.json();

        if (result.success) {
            okEl?.classList.remove('d-none');
            setTimeout(() => okEl?.classList.add('d-none'), 2000);
        } else {
            errEl?.classList.remove('d-none');
        }
    } catch(e) {
        errEl?.classList.remove('d-none');
    }
}
</script>