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

/** @var \Joomla\CMS\WebAsset\WebAssetManager $wa */
$wa = $this->document->getWebAssetManager();
$wa->useScript('table.columns')
    ->useScript('multiselect');

// HTMLHelper::addIncludePath(JPATH_COMPONENT.'/helpers/html');
// HTMLHelper::_('bootstrap.tooltip');
HTMLHelper::_('behavior.multiselect');
// HTMLHelper::_('formbehavior.chosen', 'select');

$user		= Factory::getApplication()->getIdentity();
$userId		= $user->get('id');

$listOrder	= $this->escape($this->state->get('list.ordering'));
$listDirn	= $this->escape($this->state->get('list.direction'));
$saveOrder	= $listOrder == 'f.ordering';
if ($saveOrder && !empty($this->items))
{
	$saveOrderingUrl = 'index.php?option=com_collector&task=fields.saveOrderAjax&tmpl=component&' . Session::getFormToken() . '=1';
	HTMLHelper::_('draggablelist.draggable');
}

$sortFields = $this->getSortFields();
?>


<form action="<?php echo JRoute::_('index.php?option=com_collector&view=fields');?>" method="post" name="adminForm" id="adminForm" >
	<div class="row">
        <div class="col-md-12">
            <div id="j-main-container" class="j-main-container">
                <?php echo LayoutHelper::render('joomla.searchtools.default', ['view' => $this, 'options' => ['selectorFieldName' => 'collection']]); ?>
				<?php if (empty($this->items)) :
					?>
					<div class="alert alert-info">
                        <span class="icon-info-circle" aria-hidden="true"></span><span class="visually-hidden"><?php echo Text::_('INFO'); ?></span>
                        <?php echo Text::_('JGLOBAL_NO_MATCHING_RESULTS'); ?>
					</div>
					<?php
                else :
                    ?>
				<table class="table itemList" id="fieldList">
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
                                <?php echo HTMLHelper::_('searchtools.sort', '', 'f.ordering', $listDirn, $listOrder, null, 'asc', 'JGRID_HEADING_ORDERING', 'icon-sort'); ?>
							</th>
							<th scope="col" style="min-width:85px" class="w-1 text-center">
                                <?php echo HTMLHelper::_('searchtools.sort', 'JSTATUS', 'f.state', $listDirn, $listOrder); ?>
							</th>
							<th scope="col">
								<?php echo HTMLHelper::_('searchtools.sort', 'JGLOBAL_TITLE', 'f.field', $listDirn, $listOrder); ?>
							</th>
							<th scope="col" class="w-5">
								<?php echo Text::_( 'COM_COLLECTOR_REQUIRED' ); ?>
							</th>
							<th scope="col" class="w-5">
								<?php echo Text::_( 'COM_COLLECTOR_UNIK' ); ?>
							</th>
							<th scope="col" class="w-5">
								<?php echo Text::_( 'COM_COLLECTOR_FILTER' ); ?>
							</th>
							<th scope="col" class="w-5">
								<?php echo Text::_( 'COM_COLLECTOR_DEFAULT_LISTING' ); ?>
							</th>
							<th scope="col" class="w-5">
								<?php echo Text::_( 'COM_COLLECTOR_SORT' ); ?>
							</th>
							<th scope="col" class="w-5">
								<?php echo Text::_( 'COM_COLLECTOR_EDITION' ); ?>
							</th>
							<th scope="col" class="w-10 d-none d-md-table-cell">
                                <?php echo HTMLHelper::_('searchtools.sort', 'JGRID_HEADING_ACCESS', 'f.access', $listDirn, $listOrder); ?>
							</th>
							<th scope="col" class="w-10 d-none d-md-table-cell">
                                <?php echo HTMLHelper::_('searchtools.sort', 'JAUTHOR', 'f.created_by', $listDirn, $listOrder); ?>
                            </th>
							<th scope="col" class="w-10 d-none d-md-table-cell text-center">
                                <?php echo HTMLHelper::_('searchtools.sort', 'JDATE', 'f.created', $listDirn, $listOrder); ?>
							</th>
							<th scope="col" class="w-3 d-none d-lg-table-cell">
                                <?php echo HTMLHelper::_('searchtools.sort', 'JGRID_HEADING_ID', 'f.id', $listDirn, $listOrder); ?>
                            </th>
						</tr>
					</thead>
					<tbody <?php if ($saveOrder) :
                        ?> class="js-draggable" data-url="<?php echo $saveOrderingUrl; ?>" data-direction="<?php echo strtolower($listDirn); ?>" data-nested="true"<?php
                          endif; ?>>
					<?php foreach ($this->items as $i => $item) :
						$canEdit	= $user->authorise('core.edit',			'com_collector.field.'.$item->id);
						$canCheckin	= $user->authorise('core.manage',		'com_checkin') || $item->checked_out == $userId || $item->checked_out == 0;
						$canEditOwn	= $user->authorise('core.edit.own',		'com_collector.field.'.$item->id) && $item->created_by == $userId;
						$canChange	= $user->authorise('core.edit.state',	'com_collector.field.'.$item->id) && $canCheckin;
						?>
						<tr class="row<?php echo $i % 2; ?>" data-draggable-group="<?php echo $item->collection; ?>">
							<td class="text-center">
                                <?php echo HTMLHelper::_('grid.id', $i, $item->id, false, 'cid', 'cb', $item->field); ?>
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
                                <?php echo HTMLHelper::_('jgrid.published', $item->state, $i, 'fields.', $canChange, 'cb', $item->publish_up, $item->publish_down); ?>
                            </td>
							<th scope="row" class="has-context">
								<div>
									<?php if ($item->checked_out) : ?>
										<?php echo HTMLHelper::_('jgrid.checkedout', $i, $item->editor, $item->checked_out_time, 'fields.', $canCheckin); ?>
									<?php endif; ?>
									<?php // if ($item->language == '*'):?>
										<?php // $language = Text::alt('JALL', 'language'); ?>
									<?php // else:?>
										<?php // $language = $item->language_title ? $this->escape($item->language_title) : Text::_('JUNDEFINED'); ?>
									<?php // endif;?>
									<?php if ($canEdit || $canEditOwn) : ?>
										<a href="<?php echo Route::_('index.php?option=com_collector&task=field.edit&collection='.$item->collection.'&id=' . $item->id); ?>" title="<?php echo Text::_('JACTION_EDIT'); ?>">
											<?php echo $this->escape($item->field); ?></a>
									<?php else : ?>
										<span title="<?php echo Text::sprintf('JFIELD_ALIAS_LABEL', $this->escape($item->tablecolumn)); ?>"><?php echo $this->escape($item->field); ?></span>
									<?php endif; ?>
									<div class="small">
										<?php echo Text::_('JFIELD_ALIAS_LABEL') . ": " . $this->escape($item->tablecolumn); ?>
									</div>
								</div>
							</th>
							<td class="small d-none d-md-table-cell">
								<?php echo HTMLHelper::_('jgrid.state', HTMLHelper::_('collector.requiredStates'), $item->required, $i, 'fields.', true); ?>
							</td>
							<td class="small d-none d-md-table-cell">
								<?php echo HTMLHelper::_('jgrid.state', HTMLHelper::_('collector.unikableStates'), $item->unik, $i, 'fields.', $item->unikable); ?>
							</td>
							<td class="small d-none d-md-table-cell">
								<?php echo HTMLHelper::_('jgrid.state', HTMLHelper::_('collector.filterStates'), $item->filter, $i, 'fields.', $item->filterable); ?>
							</td>
							<td class="small d-none d-md-table-cell">
								<?php echo HTMLHelper::_('jgrid.state', HTMLHelper::_('collector.listingStates'), $item->listing, $i, 'fields.', true); ?>
							</td>
							<td class="small d-none d-md-table-cell">
								<?php echo HTMLHelper::_('jgrid.state', HTMLHelper::_('collector.sortStates'), $item->sort, $i, 'fields.', $item->sortable); ?>
							</td>
							<td class="small d-none d-md-table-cell">
								<?php echo HTMLHelper::_('jgrid.state', HTMLHelper::_('collector.editionStates'), $item->edit, $i, 'fields.', true); ?>
							</td>
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