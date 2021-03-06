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

// Check to ensure this file is included in Joomla!
defined('_JEXEC') or die( 'Restricted access' );

JHtml::addIncludePath(JPATH_COMPONENT.'/helpers/html');
JHtml::_('bootstrap.tooltip');
JHtml::_('behavior.multiselect');
JHtml::_('formbehavior.chosen', 'select');

?>
<form action="<?php echo JRoute::_('index.php?option=com_collector&view=templates');?>" method="post" name="adminForm" id="adminForm" >
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
			<table class="table table-striped" id="templateList">
				<thead>
					<tr>
						<th width="1%" class="nowrap center hidden-phone">
						</th>
						<th width="1%" class="hidden-phone">
							<?php echo JHtml::_('grid.checkall'); ?>
						</th>
						<th width="1%" style="min-width:55px" class="nowrap center">
							<?php echo JText::_( 'COM_COLLECTOR_DEFAULT' ); ?>
						</th>
						<th>
							<?php echo JText::_( 'JGLOBAL_TITLE' ); ?>
						</th>
						<th>
							<?php echo JText::_( 'COM_COLLECTOR_TYPE' ); ?>
						</th>
						<th width="1%" class="nowrap hidden-phone">
							<?php echo JText::_( 'JGRID_HEADING_ID' ); ?>
						</th>
					</tr>
				</thead>
				<tbody>
				<?php foreach ($this->items as $i => $item) :
					$canEdit	= $user->authorise('core.edit',			'com_collector.template.'.$item->id);
					
					?>
					<tr class="row<?php echo $i % 2; ?>" sortable-group-id="<?php echo $item->collection; ?>">
						<td class="order nowrap center hidden-phone">
							<span class="sortable-handler inactive" >
								<i class="icon-menu"></i>
							</span>
						</td>
						<td class="center hidden-phone">
							<?php echo JHtml::_('grid.id', $i, $item->id); ?>
						</td>
						<td class="center">
							<div class="btn-group">
								<?php
								echo JHtml::_('collectortemplates.home', $item->home, $i, true);
								?>
								<?php
								// Create dropdown items
								JHtml::_('actionsdropdown.' . 'dalete', 'cb' . $i, 'templates');

								// Render dropdown list
								echo JHtml::_('actionsdropdown.render', $this->escape($item->name));
								?>
							</div>
						</td>
						<td class="nowrap has-context">
							<div class="pull-left">
								<?php if ($canEdit) : ?>
									<a href="<?php echo JRoute::_('index.php?option=com_collector&task=template.edit&collection='.$item->collection.'&id='.$item->id);?>">
										<?php echo $this->escape($item->name); ?></a>
								<?php else : ?>
									<?php echo $this->escape($item->name); ?>
								<?php endif; ?>
							</div>
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

		<input type="hidden" name="task" value="" />
		<input type="hidden" name="boxchecked" value="0" />
		<?php echo JHTML::_( 'form.token' ); ?>
	</div>
</form>
