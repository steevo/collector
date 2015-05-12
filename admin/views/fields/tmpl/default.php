<?php
/**
 * Joomla! 3.0 component Collector
 *
 * @package 	Collector
 * @copyright   Copyright (C) 2010 - 2015 Philippe Ousset. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 *
 * Collector is a Multi Purpose Listing Tool.
 * Originaly developped to list Collections
 * it can be used for several purpose.
 */

// no direct access

defined('_JEXEC') or die('Restricted access');

JHtml::addIncludePath(JPATH_COMPONENT.'/helpers/html');
JHtml::_('bootstrap.tooltip');
JHtml::_('behavior.multiselect');
JHtml::_('formbehavior.chosen', 'select');

$user		= JFactory::getUser();
$userId		= $user->get('id');

$listOrder	= $this->escape($this->state->get('list.ordering'));
$listDirn	= $this->escape($this->state->get('list.direction'));
$trashed	= $this->state->get('filter.published') == -2 ? true : false;
$saveOrder	= $listOrder == 'f.ordering';
if ($saveOrder)
{
	$saveOrderingUrl = 'index.php?option=com_collector&task=fields.saveOrderAjax&tmpl=component';
	JHtml::_('sortablelist.sortable', 'fieldList', 'adminForm', strtolower($listDirn), $saveOrderingUrl);
}

$sortFields = $this->getSortFields();
?>
<script type="text/javascript">
	Joomla.orderTable = function()
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
	}
</script>

<form action="<?php echo JRoute::_('index.php?option=com_collector&view=fields');?>" method="post" name="adminForm" id="adminForm" >
<?php if (!empty( $this->sidebar)) : ?>
	<div id="j-sidebar-container" class="span2">
		<?php echo $this->sidebar; ?>
	</div>
	<div id="j-main-container" class="span10">
<?php else : ?>
	<div id="j-main-container">
<?php endif;?>
		<?php
		// Search tools bar
		echo JLayoutHelper::render('joomla.searchtools.default', array('view' => $this));
		?>
		<?php if (empty($this->items)) : ?>
			<div class="alert alert-no-items">
				<?php echo JText::_('JGLOBAL_NO_MATCHING_RESULTS'); ?>
			</div>
		<?php else : ?>
			<table class="table table-striped" id="fieldList">
				<thead>
					<tr>
						<th width="1%" class="nowrap center hidden-phone">
							<?php echo JHtml::_('searchtools.sort', '', 'f.ordering', $listDirn, $listOrder, null, 'asc', 'JGRID_HEADING_ORDERING', 'icon-menu-2'); ?>
						</th>
						<th width="1%" class="hidden-phone">
							<?php echo JHtml::_('grid.checkall'); ?>
						</th>
						<th width="1%" style="min-width:55px" class="nowrap center">
							<?php echo JHtml::_('searchtools.sort', 'JSTATUS', 'f.state', $listDirn, $listOrder); ?>
						</th>
						<th>
							<?php echo JHtml::_('searchtools.sort', 'JGLOBAL_TITLE', 'f.field', $listDirn, $listOrder); ?>
						</th>
						<th width="5%">
							<?php echo JText::_( 'COM_COLLECTOR_REQUIRED' ); ?>
						</th>
						<th width="5%">
							<?php echo JText::_( 'COM_COLLECTOR_UNIK' ); ?>
						</th>
						<th width="5%">
							<?php echo JText::_( 'COM_COLLECTOR_FILTER' ); ?>
						</th>
						<th width="5%">
							<?php echo JText::_( 'COM_COLLECTOR_DEFAULT_LISTING' ); ?>
						</th>
						<th width="5%">
							<?php echo JText::_( 'COM_COLLECTOR_SORT' ); ?>
						</th>
						<th width="5%">
							<?php echo JText::_( 'COM_COLLECTOR_EDITION' ); ?>
						</th>
						<th width="10%" class="nowrap hidden-phone">
							<?php echo JHtml::_('searchtools.sort',  'JGRID_HEADING_ACCESS', 'f.access', $listDirn, $listOrder); ?>
						</th>
						<th width="10%" class="nowrap hidden-phone">
							<?php echo JHtml::_('searchtools.sort',  'JAUTHOR', 'f.created_by', $listDirn, $listOrder); ?>
						</th>
						<th width="10%" class="nowrap hidden-phone">
							<?php echo JHtml::_('searchtools.sort', 'JDATE', 'f.created', $listDirn, $listOrder); ?>
						</th>
						<th width="1%" class="nowrap hidden-phone">
							<?php echo JHtml::_('searchtools.sort', 'JGRID_HEADING_ID', 'f.id', $listDirn, $listOrder); ?>
						</th>
					</tr>
				</thead>
				<tbody>
				<?php foreach ($this->items as $i => $item) :
					$ordering	= ($listOrder == 'f.ordering');
					$canEdit	= $user->authorise('core.edit',			'com_collector.field.'.$item->id);
					$canCheckin	= $user->authorise('core.manage',		'com_checkin') || $item->checked_out == $userId || $item->checked_out == 0;
					$canEditOwn	= $user->authorise('core.edit.own',		'com_collector.field.'.$item->id) && $item->created_by == $userId;
					$canChange	= $user->authorise('core.edit.state',	'com_collector.field.'.$item->id) && $canCheckin;
					
					// required
					if ( $item->required == '1' )
					{
						$requiredtext = JText::_( 'COM_COLLECTOR_CLICK_TO_DISABLE' );
						$requiredimg = 'components/com_collector/assets/images/tick.png';
						$requiredalt = JText::_( 'COM_COLLECTOR_ENABLED' );
					}
					else
					{
						$requiredtext = JText::_( 'COM_COLLECTOR_CLICK_TO_ENABLE' );
						$requiredimg = 'components/com_collector/assets/images/cross.png';
						$requiredalt = JText::_( 'COM_COLLECTOR_DISABLED' );
					}

					// filter
					if ( $item->filterable )
					{
						if ( $item->filter == '1' )
						{
							$filtertext = JText::_( 'COM_COLLECTOR_CLICK_TO_DISABLE' );
							$filterimg = 'components/com_collector/assets/images/tick.png';
							$filteralt = JText::_( 'COM_COLLECTOR_ENABLED' );
						}
						else
						{
							$filtertext = JText::_( 'COM_COLLECTOR_CLICK_TO_ENABLE' );
							$filterimg = 'components/com_collector/assets/images/cross.png';
							$filteralt = JText::_( 'COM_COLLECTOR_DISABLED' );
						}
					}
					else
					{
						$filtertext = JText::_( 'COM_COLLECTOR_IMPOSSIBLE' );
						$filterimg = 'templates/hathor/images/admin/disabled.png';
						$filteralt = JText::_( 'COM_COLLECTOR_IMPOSSIBLE' );
					}

					// unik
					if ( $item->unikable )
					{
						if ( $item->unik == '1' )
						{
							$uniktext = JText::_( 'COM_COLLECTOR_CLICK_TO_DISABLE' );
							$unikimg = 'components/com_collector/assets/images/key.png';
							$unikalt = JText::_( 'COM_COLLECTOR_ENABLED' );
						}
						else
						{
							$uniktext = JText::_( 'COM_COLLECTOR_CLICK_TO_ENABLE' );
							$unikimg = 'components/com_collector/assets/images/key_delete.png';
							$unikalt = JText::_( 'COM_COLLECTOR_DISABLED' );
						}
					}
					else
					{
						$uniktext = JText::_( 'COM_COLLECTOR_IMPOSSIBLE' );
						$unikimg = 'templates/hathor/images/admin/disabled.png';
						$unikalt = JText::_( 'COM_COLLECTOR_IMPOSSIBLE' );
					}

					// edition
					if ( $item->edit == '1' )
					{
						$editiontext = JText::_( 'COM_COLLECTOR_CLICK_TO_DISABLE' );
						$editionimg = 'components/com_collector/assets/images/icon-16-edit.png';
						$editionalt = JText::_( 'COM_COLLECTOR_ENABLED' );
					}
					else
					{
						$editiontext = JText::_( 'COM_COLLECTOR_CLICK_TO_ENABLE' );
						$editionimg = 'templates/hathor/images/admin/checked_out.png';
						$editionalt = JText::_( 'COM_COLLECTOR_DISABLED' );
					}

					// default listing
					if ( $item->listing == '1' )
					{
						$listingtext = JText::_( 'COM_COLLECTOR_CLICK_TO_HIDE' );
						$listingimg = 'components/com_collector/assets/images/table.png';
						$listingalt = JText::_( 'COM_COLLECTOR_HIDE' );
					}
					else
					{
						$listingtext = JText::_( 'COM_COLLECTOR_CLICK_TO_DISPLAY' );
						$listingimg = 'components/com_collector/assets/images/table_delete.png';
						$listingalt = JText::_( 'COM_COLLECTOR_DISPLAY' );
					}

					// sort
					if (( $item->sortable ) && ( $item->listing == 1 ))
					{
						if ( $item->sort == '1' )
						{
							$sorttext = JText::_( 'COM_COLLECTOR_CLICK_TO_DISABLE' );
							$sortimg = 'components/com_collector/assets/images/tick.png';
							$sortalt = JText::_( 'COM_COLLECTOR_ENABLED' );
						}
						else
						{
							$sorttext = JText::_( 'COM_COLLECTOR_CLICK_TO_ENABLE' );
							$sortimg = 'components/com_collector/assets/images/cross.png';
							$sortalt = JText::_( 'COM_COLLECTOR_DISABLED' );
						}
					}
					else
					{
						$sorttext = JText::_( 'COM_COLLECTOR_IMPOSSIBLE' );
						$sortimg = 'templates/hathor/images/admin/disabled.png';
						$sortalt = JText::_( 'COM_COLLECTOR_IMPOSSIBLE' );
					}
					
					?>
					<tr class="row<?php echo $i % 2; ?>" sortable-group-id="<?php echo $item->collection; ?>">
						<td class="order nowrap center hidden-phone">
						<?php if ($canChange) :
							$disableClassName = '';
							$disabledLabel	  = '';

							if (!$saveOrder) :
								$disabledLabel    = JText::_('JORDERINGDISABLED');
								$disableClassName = 'inactive tip-top';
							endif; ?>
							<span class="sortable-handler hasTooltip <?php echo $disableClassName; ?>" title="<?php echo $disabledLabel; ?>">
								<i class="icon-menu"></i>
							</span>
							<input type="text" style="display:none" name="order[]" size="5" value="<?php echo $item->ordering; ?>" class="width-20 text-area-order " />
						<?php else : ?>
							<span class="sortable-handler inactive" >
								<i class="icon-menu"></i>
							</span>
						<?php endif; ?>
						</td>
						<td class="center hidden-phone">
							<?php echo JHtml::_('grid.id', $i, $item->id); ?>
						</td>
						<td class="center">
							<div class="btn-group">
								<?php echo JHtml::_('jgrid.published', $item->state, $i, 'fields.', $canChange, 'cb', $item->publish_up, $item->publish_down); ?>
								<?php
								$change = (($item->intitle)&&($item->state!=-2)) ? $canChange : false;
								echo JHtml::_('collectorfields.home', $item->home, $i, $change);
								?>
								<?php
								// Create dropdown items
								$action = $trashed ? 'untrash' : 'trash';
								JHtml::_('actionsdropdown.' . $action, 'cb' . $i, 'fields');

								// Render dropdown list
								echo JHtml::_('actionsdropdown.render', $this->escape($item->field));
								?>
							</div>
						</td>
						<td class="nowrap has-context">
							<div class="pull-left">
								<?php if ($item->checked_out) : ?>
									<?php echo JHtml::_('jgrid.checkedout', $i, $item->editor, $item->checked_out_time, 'fields.', $canCheckin); ?>
								<?php endif; ?>
								<?php if ($canEdit || $canEditOwn) : ?>
									<a href="<?php echo JRoute::_('index.php?option=com_collector&task=field.edit&collection='.$item->collection.'&id='.$item->id);?>">
										<?php echo $this->escape($item->field); ?></a>
								<?php else : ?>
									<?php echo $this->escape($item->field); ?>
								<?php endif; ?>
							</div>
						</td>
						<td class="center hidden-phone">
							<span class="editlinktip hasTip" title="<?php echo JText::_('COM_COLLECTOR_FIELD_REQUIRED'); ?>::<?php echo $requiredtext; ?>">
								<?php
								if ( $item->state == -1 )
								{
								?>
									<img src="<?php echo $requiredimg;?>" width="16" height="16" border="0" alt="<?php echo $requiredalt;?>" />
								<?php
								}
								else
								{
								?>
								<a href="javascript:void(0);" onclick="return listItemTask('cb<?php echo $i;?>','fields.<?php echo $item->required ? 'norequired' : 'required' ?>')">
									<img src="<?php echo $requiredimg;?>" width="16" height="16" border="0" alt="<?php echo $requiredalt;?>" />
								</a>
								<?php
								}
								?>
							</span>
						</td>
						<td class="center hidden-phone">
							<span class="editlinktip hasTip" title="<?php echo JText::_('COM_COLLECTOR_UNIK_VALUE'); ?>::<?php echo $uniktext; ?>">
								<?php
								if ( $item->state == -1 || $item->unikable == 0 )
								{
								?>
									<img src="<?php echo $unikimg;?>" width="16" height="16" border="0" alt="<?php echo $unikalt;?>" />
								<?php
								}
								else
								{
								?>
								<a href="javascript:void(0);" onclick="return listItemTask('cb<?php echo $i;?>','fields.<?php echo $item->unik ? 'nounik' : 'unik' ?>')">
									<img src="<?php echo $unikimg;?>" width="16" height="16" border="0" alt="<?php echo $unikalt;?>" />
								</a>
								<?php
								}
								?>
							</span>
						</td>
						<td class="center hidden-phone">
							<span class="editlinktip hasTip" title="<?php echo JText::_('COM_COLLECTOR_FRONTEND_FILTER'); ?>::<?php echo $filtertext; ?>">
								<?php
								if ( $item->state == -1 || $item->filterable == 0 )
								{
								?>
									<img src="<?php echo $filterimg;?>" width="16" height="16" border="0" alt="<?php echo $filteralt;?>" />
								<?php
								}
								else
								{
								?>
								<a href="javascript:void(0);" onclick="return listItemTask('cb<?php echo $i;?>','fields.<?php echo $item->filter ? 'deny_filter' : 'allow_filter' ?>')">
									<img src="<?php echo $filterimg;?>" width="16" height="16" border="0" alt="<?php echo $filteralt;?>" />
								</a>
								<?php
								}
								?>
							</span>
						</td>
						<td class="center hidden-phone">
							<span class="editlinktip hasTip" title="<?php echo JText::_('COM_COLLECTOR_DEFAULT_LISTING'); ?>::<?php echo $listingtext; ?>">
								<?php
								if ( $item->listing == -1 )
								{
								?>
									<img src="<?php echo $listingimg; ?>" width="16" height="16" border="0" alt="<?php echo $listingalt; ?>" />
								<?php
								}
								else
								{
								?>
									<a href="javascript:void(0);" onclick="return listItemTask('cb<?php echo $i;?>','fields.<?php echo $item->listing ? 'hide' : 'nohide' ?>')">
										<img src="<?php echo $listingimg; ?>" width="16" height="16" border="0" alt="<?php echo $listingalt; ?>" />
									</a>
								<?php
								}
								?>
							</span>
						</td>
						<td class="center hidden-phone">
							<span class="editlinktip hasTip" title="<?php echo JText::_('COM_COLLECTOR_FRONTEND_SORT'); ?>::<?php echo $sorttext; ?>">
								<?php
								if (( $item->sortable == 0 ) || ($item->listing == 0 ))
								{
								?>
									<img src="<?php echo $sortimg; ?>" width="16" height="16" border="0" alt="<?php echo $sortalt; ?>" />
								<?php
								}
								else
								{
								?>
									<a href="javascript:void(0);" onclick="return listItemTask('cb<?php echo $i;?>','fields.<?php echo $item->sort ? 'nosort' : 'sort' ?>')">
										<img src="<?php echo $sortimg; ?>" width="16" height="16" border="0" alt="<?php echo $sortalt; ?>" />
									</a>
								<?php
								}
								?>
							</span>
						</td>
						<td class="center hidden-phone">
							<span class="editlinktip hasTip" title="<?php echo JText::_('COM_COLLECTOR_FRONTEND_EDITION'); ?>::<?php echo $editiontext; ?>">
								<?php
								if ( $item->state == -1 )
								{
								?>
									<img src="<?php echo $editionimg; ?>" width="16" height="16" border="0" alt="<?php echo $editionalt; ?>" />
								<?php
								}
								else
								{
								?>
									<a href="javascript:void(0);" onclick="return listItemTask('cb<?php echo $i;?>','fields.<?php echo $item->edit ? 'lock' : 'open' ?>')">
										<img src="<?php echo $editionimg; ?>" width="16" height="16" border="0" alt="<?php echo $editionalt; ?>" />
									</a>
								<?php
								}
								?>
							</span>
						</td>
						<td class="small hidden-phone">
							<?php echo $this->escape($item->access_level); ?>
						</td>
						<td class="small hidden-phone">
							<?php if ($item->created_by_alias) : ?>
								<?php echo $this->escape($item->author_name); ?>
								<p class="smallsub"> <?php echo JText::sprintf('JGLOBAL_LIST_ALIAS', $this->escape($item->created_by_alias)); ?></p>
							<?php else : ?>
								<?php echo $this->escape($item->author_name); ?>
							<?php endif; ?>
						</td>
						<td class="nowrap small hidden-phone">
							<?php echo JHtml::_('date', $item->created, JText::_('DATE_FORMAT_LC4')); ?>
						</td>
						<td class="center hidden-phone">
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
		<?php echo JHTML::_( 'form.token' ); ?>
	</div>
</form>
