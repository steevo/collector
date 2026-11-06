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

defined('_JEXEC') or die('Restricted access');

use Steevo\Component\Collector\Administrator\Helper\HomeButton;
use Joomla\CMS\Factory;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Language\Associations;
use Joomla\CMS\Language\Multilanguage;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Layout\LayoutHelper;
use Joomla\CMS\Router\Route;
use Joomla\CMS\Session\Session;

// HTMLHelper::_('bootstrap.tooltip');
HTMLHelper::_('behavior.multiselect');
// HTMLHelper::_('formbehavior.chosen', 'select');

$user      = Factory::getApplication()->getIdentity();
$userId    = $user->get('id');

$listOrder	= $this->escape($this->state->get('list.ordering'));
$listDirn	= $this->escape($this->state->get('list.direction'));
$saveOrder 	= ($listOrder == 'c.lft' && strtolower($listDirn) == 'asc');
if ($saveOrder)
{
	$saveOrderingUrl = 'index.php?option=com_collector&task=listitems.saveOrderAjax&tmpl=component';
	HTMLHelper::_('draggablelist.draggable');
	// HTMLHelper::_('sortablelist.sortable', 'itemList', 'adminForm', strtolower($listDirn), $saveOrderingUrl, false, true);
}

$sortFields = $this->getSortFields();
?>
<script type="text/javascript">
	/*Joomla.orderTable = function()
	{
		table = document.getElementById("sortTable");
		direction = document.getElementById("directionTable");
		order = table.options[table.selectedIndex].value;
		if (order != '<?php echo $listOrder; ?>')
		{
			dirn = 'asc';
		}
		else
		{
			dirn = direction.options[direction.selectedIndex].value;
		}
		Joomla.tableOrdering(order, dirn, '');
	}*/
</script>
<?php //Set up the filter bar. ?>
<form action="<?php echo Route::_('index.php?option=com_collector&view=listitems&defined='.$this->defined); ?>" method="post" name="adminForm" id="adminForm" >
	<div class="row">
        <div class="col-md-12">
            <div id="j-main-container" class="j-main-container">
                <?php echo LayoutHelper::render('joomla.searchtools.default', ['view' => $this]); ?>
				<?php if (empty($this->items)) :
                    ?>
                  <div class="alert alert-info">
                        <span class="icon-info-circle" aria-hidden="true"></span><span class="visually-hidden"><?php echo Text::_('INFO'); ?></span>
                        <?php echo Text::_('JGLOBAL_NO_MATCHING_RESULTS'); ?>
                  </div>
                    <?php
                else :
                    ?>
				<table class="table table-striped" id="itemList">
					<caption class="visually-hidden">
                        <?php echo Text::_('COM_COLLECTOR_COLLECTIONS_TABLE_CAPTION'); ?>,
                        <span id="orderedBy"><?php echo Text::_('JGLOBAL_SORTED_BY'); ?> </span>,
                        <span id="filteredBy"><?php echo Text::_('JGLOBAL_FILTERED_BY'); ?></span>
                    </caption>
					<thead>
						<tr>
							<td class="w-1 text-center">
								<?php echo HTMLHelper::_('grid.checkall'); ?>
							</td>
							<th scope="col" class="w-1 text-center d-none d-md-table-cell">
								<?php echo HTMLHelper::_('searchtools.sort', '', 'c.lft', $listDirn, $listOrder, null, 'asc', 'JGRID_HEADING_ORDERING', 'icon-menu-2'); ?>
							</th>
							<th scope="col" class="title">
								<?php echo HTMLHelper::_('searchtools.sort',  'JGLOBAL_TITLE', 'c.content', $listDirn, $listOrder ); ?>
							</th>
							<th scope="col" class="w-3 d-none d-lg-table-cell">
								<?php echo HTMLHelper::_('searchtools.sort', 'JGRID_HEADING_ID', 'c.id', $listDirn, $listOrder); ?>
							</th>
						</tr>
					</thead>
					<tbody <?php if ($saveOrder) :
                        ?> class="js-draggable" data-url="<?php echo $saveOrderingUrl; ?>" data-direction="<?php echo strtolower($listDirn); ?>" data-nested="true"<?php
                          endif; ?>>
					<?php foreach ($this->items as $i => $item) :
						$orderkey   = array_search($item->id, $this->ordering[$item->parent_id]);
						$canEdit    = $user->authorise('core.edit',       'com_collector');
						$canChange  = $user->authorise('core.edit.state', 'com_collector');
						// Get the parents of item for sorting
						if ($item->level > 1)
						{
							$parentsStr = "";
							$_currentParentId = $item->parent_id;
							$parentsStr = " ".$_currentParentId;
							for ($j = 0; $j < $item->level; $j++)
							{
								foreach ($this->ordering as $k => $v)
								{
									$v = implode("-", $v);
									$v = "-" . $v . "-";
									if (strpos($v, "-" . $_currentParentId . "-") !== false)
									{
										$parentsStr .= " " . $k;
										$_currentParentId = $k;
										break;
									}
								}
							}
						}
						else
						{
							$parentsStr = "";
						}
						?>
						<tr class="row<?php echo $i % 2; ?>" data-draggable-group="<?php echo $item->parent_id;?>" data-item-id="<?php echo $item->id?>" data-parents="<?php echo $parentsStr?>" data-level="<?php echo $item->level?>">
							<td class="center hidden-phone">
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
									<span class="icon-ellipsis-v"></span>
								</span>
								<?php if ($canChange && $saveOrder) : ?>
									<input type="text" class="hidden" name="order[]" size="5" value="<?php echo $orderkey + 1; ?>">
								<?php endif; ?>
							</td>
							<th scope="row">
								<?php $prefix = LayoutHelper::render('joomla.html.treeprefix', ['level' => $item->level]); ?>
                                <?php echo $prefix; ?>
								<?php if ($canEdit) : ?>
									<a href="<?php echo Route::_('index.php?option=com_collector&task=listitem.edit&defined='.(int) $item->defined.'&id='.(int) $item->id);?>" title="<?php echo Text::_('JACTION_EDIT'); ?> <?php echo $this->escape($item->content); ?>">
										<?php echo $this->escape($item->content); ?></a>
								<?php else : ?>
									<?php echo $this->escape($item->content); ?>
								<?php endif; ?>
							</th>
							<td class="d-none d-md-table-cell">
								<span title="<?php echo sprintf('%d-%d', $item->lft, $item->rgt);?>">
									<?php echo (int) $item->id; ?></span>
							</td>
						</tr>
						<?php endforeach; ?>
					</tbody>
				</table>
				
				<?php // load the pagination. ?>
                <?php echo $this->pagination->getListFooter(); ?>
					
				<?php endif; ?>
		
		
				<input type="hidden" name="task" value="" />
				<input type="hidden" name="boxchecked" value="0" />
				<?php echo HTMLHelper::_( 'form.token' ); ?>
			</div>
		</div>
	</div>
</form>
