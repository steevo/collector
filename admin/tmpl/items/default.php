<?php
/**
 * Joomla! 3.0 component Collector
 *
 * @package 	Collector
 * @copyright   Copyright (C) 2010 - 2020 Philippe Ousset. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

defined('_JEXEC') or die('Restricted access');

use Joomla\CMS\Factory;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Language\Associations;
use Joomla\CMS\Language\Multilanguage;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Layout\LayoutHelper;
use Joomla\CMS\Router\Route;
use Joomla\CMS\Session\Session;
use Steevo\Component\Collector\Administrator\Helper\CollectorGrid;

HTMLHelper::_('behavior.multiselect');
HTMLHelper::_('bootstrap.tooltip');

$app		= Factory::getApplication();
$user		= Factory::getApplication()->getIdentity();
$userId		= $user->get('id');
$listOrder	= $this->escape($this->state->get('list.ordering'));
$listDirn	= $this->escape($this->state->get('list.direction'));
$archived	= $this->state->get('filter.published') == 2 ? true : false;
$trashed	= $this->state->get('filter.published') == -2 ? true : false;
$saveOrder	= $listOrder == 'i.ordering';

if ($saveOrder)
{
	$saveOrderingUrl = 'index.php?option=com_collector&task=items.saveOrderAjax&tmpl=component&' . Session::getFormToken() . '=1';
	HTMLHelper::_('draggablelist.draggable');
}

$sortFields = $this->getSortFields();
?>

<form action="<?php echo JRoute::_('index.php?option=com_collector&view=items');?>" method="post" name="adminForm" id="adminForm" >
	<div class="row">
        <div class="col-md-12">
            <div id="j-main-container" class="j-main-container">
				<?php
				// Search tools bar
				echo LayoutHelper::render('joomla.searchtools.default', ['view' => $this, 'options' => ['selectorFieldName' => 'collection']]);
				?>
				<?php if (empty($this->items)) : ?>
					<div class="alert alert-info">
                        <span class="icon-info-circle" aria-hidden="true"></span><span class="visually-hidden"><?php echo Text::_('INFO'); ?></span>
                        <?php echo Text::_('JGLOBAL_NO_MATCHING_RESULTS'); ?>
					</div>
				<?php else : ?>
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
								<?php echo HTMLHelper::_('searchtools.sort', '', 'i.ordering', $listDirn, $listOrder, null, 'asc', 'JGRID_HEADING_ORDERING', 'icon-sort'); ?>
							</th>
							<th scope="col" style="min-width:85px" class="w-1 text-center">
								<?php echo HTMLHelper::_('searchtools.sort', 'JSTATUS', 'i.state', $listDirn, $listOrder); ?>
							</th>
							<th scope="col">
								<?php echo HTMLHelper::_('searchtools.sort', $this->collection->title, 'i.alias', $listDirn, $listOrder); ?>
							</th>
							<th scope="col" class="w-10 d-none d-md-table-cell">
								<?php echo HTMLHelper::_('searchtools.sort',  'JGRID_HEADING_ACCESS', 'i.access', $listDirn, $listOrder); ?>
							</th>
							<th scope="col" class="w-10 d-none d-md-table-cell">
								<?php echo HTMLHelper::_('searchtools.sort',  'JAUTHOR', 'i.created_by', $listDirn, $listOrder); ?>
							</th>
							<th scope="col" class="w-10 d-none d-md-table-cell text-center">
								<?php echo HTMLHelper::_('searchtools.sort', 'JDATE', 'i.created', $listDirn, $listOrder); ?>
							</th>
							<th scope="col" class="w-10 d-none d-md-table-cell text-center">
								<?php echo HTMLHelper::_('searchtools.sort', 'COM_COLLECTOR_LAST_MODIFIED', 'last_modified', $listDirn, $listOrder); ?>
							</th>
							<th scope="col" class="w-5 d-none d-lg-table-cell">
								<?php echo HTMLHelper::_('searchtools.sort', 'COM_COLLECTOR_HITS', 'i.hits', $listDirn, $listOrder ); ?>
							</th>
							<th scope="col" class="w-3 d-none d-lg-table-cell">
								<?php echo HTMLHelper::_('searchtools.sort', 'JGRID_HEADING_ID', 'i.id', $listDirn, $listOrder); ?>
							</th>
						</tr>
					</thead>
					<tbody <?php if ($saveOrder) :
						?> class="js-draggable" data-url="<?php echo $saveOrderingUrl; ?>" data-direction="<?php echo strtolower($listDirn); ?>" data-nested="true"<?php
					  endif; ?>>
					<?php foreach ($this->items as $i => $item) :
						// $ordering	= ($listOrder == 'i.ordering');
						// $canCreate  = $user->authorise('core.create',		'com_collector.collection.'.$item->collection);
						$canEdit	= $user->authorise('core.edit',			'com_collector.item.'.$item->id);
						$canCheckin	= $user->authorise('core.manage',		'com_checkin') || $item->checked_out == $userId || $item->checked_out == 0;
						$canEditOwn	= $user->authorise('core.edit.own',		'com_collector.item.'.$item->id) && $item->created_by == $userId;
						$canChange	= $user->authorise('core.edit.state',	'com_collector.item.'.$item->id) && $canCheckin;
						
						$title = '';
						
						foreach($this->fields as $field)
						{
							if ( $title != '' )
							{
								$title .= ' ';
							}
							$nameField = $field->_field->tablecolumn;
							$title .= $field->displayInTitle($item->$nameField);
						}
						$rebuild = ( $title != $item->fulltitle );
						?>
						<tr class="row<?php echo $i % 2; ?>" data-draggable-group="<?php echo $item->collection; ?>">
							<td class="text-center">
								<?php echo HTMLHelper::_('grid.id', $i, $item->id); ?>
							</td>
							<td class="text-center d-none d-md-table-cell">
								<?php
								$iconClass = '';
								if (!$canChange) {
									$iconClass = ' inactive';
								} elseif (!$saveOrder) {
									$iconClass = ' inactive tip-top hasTooltip" title="' . HTMLHelper::tooltipText('JORDERINGDISABLED');
								}
								?>
								<span class="sortable-handler<?php echo $iconClass; ?>">
									<span class="icon-ellipsis-v" aria-hidden="true"></span>
								</span>
								<?php if ($canChange && $saveOrder) : ?>
									<input type="text" name="order[]" size="5" value="<?php echo $item->ordering; ?>" class="width-20 text-area-order hidden" />
								<?php endif; ?>
							</td>
							<td class="text-center">
								<?php echo HTMLHelper::_('jgrid.published', $item->state, $i, 'items.', $canChange, 'cb', $item->publish_up, $item->publish_down); ?>
							</td>
							<th scope="row" class="has-context">
								<div>
									<?php if ($item->checked_out) : ?>
										<?php echo HTMLHelper::_('jgrid.checkedout', $i, $item->editor, $item->checked_out_time, 'items.', $canCheckin); ?>
									<?php endif; ?>
									<?php if ($rebuild) : ?>
										<?php echo HTMLHelper::_('collectorgrid.rebuild', $i, $item->fulltitle, 'items.', true); ?>
									<?php endif; ?>
									<?php if ($canEdit || $canEditOwn) : ?>
										<a href="<?php echo Route::_('index.php?option=com_collector&task=item.edit&collection='.$item->collection.'&id='.$item->id);?>" title="<?php echo Text::_('JACTION_EDIT'); ?>">
											<?php echo $this->escape($title); ?></a>
									<?php else : ?>
										<span title="<?php echo Text::sprintf('JFIELD_ALIAS_LABEL', $this->escape($item->alias)); ?>"><?php echo $this->escape($title); ?></span>
									<?php endif; ?>
									<?php if (!empty($item->to_validate)) : ?>
										<a href="<?php echo Route::_('index.php?option=com_collector&view=itemversions&collection=' . $item->collection . '&item=' . $item->id); ?>"
										   class="badge bg-warning text-dark ms-1"
										   title="<?php echo Text::plural('COM_COLLECTOR_ITEM_HISTORY_N_TO_VALIDATE', (int) $item->to_validate); ?>"
										   data-bs-toggle="tooltip">
											<span class="icon-clock me-1" aria-hidden="true"></span><?php echo (int) $item->to_validate; ?>
										</a>
									<?php endif; ?>
								</div>
							</th>
							<td class="small d-none d-md-table-cell">
								<?php echo $this->escape($item->access_level); ?>
							</td>
							<td class="small d-none d-md-table-cell">
								<?php if ($item->created_by_alias) : ?>
									<?php echo $this->escape($item->author_name); ?>
									<p class="smallsub"> <?php echo Text::sprintf('JGLOBAL_LIST_ALIAS', $this->escape($item->created_by_alias)); ?></p>
								<?php else : ?>
									<?php echo $this->escape($item->author_name); ?>
								<?php endif; ?>
							</td>
							<td class="small d-none d-md-table-cell text-center">
								<?php echo HTMLHelper::_('date', $item->created, Text::_('DATE_FORMAT_LC4')); ?>
							</td>
							<td class="small d-none d-md-table-cell text-center">
								<?php echo HTMLHelper::_('date', $item->last_modified, Text::_('DATE_FORMAT_LC4')); ?>
							</td>
							<td class="small d-none d-md-table-cell text-center">
								<?php echo $item->hits; ?>
							</td>
							<td class="d-none d-lg-table-cell">
								<?php echo (int) $item->id; ?>
							</td>
						</tr>
						<?php endforeach; ?>
					</tbody>
				</table>
				<?php endif; ?>
				<?php echo $this->pagination->getListFooter(); ?>
				<?php //Load the batch processing form. ?>
				<?php //echo $this->loadTemplate('batch'); ?>
	
				<input type="hidden" name="task" value="" />
				<input type="hidden" name="boxchecked" value="0" />
				<?php echo HTMLHelper::_( 'form.token' ); ?>
			</div>
		</div>
	</div>
</form>