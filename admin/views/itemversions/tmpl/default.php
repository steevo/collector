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
?>

<form action="<?php echo JRoute::_('index.php?option=com_collector&view=itemversions');?>" method="post" name="adminForm" id="adminForm" >
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
		echo JLayoutHelper::render('joomla.searchtools.default', array('view' => $this), null, array('debug' => false));
		?>
		<?php if (empty($this->items)) : ?>
			<div class="alert alert-no-items">
				<?php echo JText::_('JGLOBAL_NO_MATCHING_RESULTS'); ?>
			</div>
		<?php else : ?>	
			<table class="table table-striped" id="itemList">
				<thead>
					<tr>
						<th width="5">
							<?php echo JText::_( '#' ); ?>
						</th>
						<th width="1%" class="hidden-phone">
							<?php echo JHtml::_('grid.checkall'); ?>
						</th>
						<th width="20">
							<?php echo JText::_( 'JSTATUS' ); ?>
						</th>
						<th width="10%" class="nowrap hidden-phone">
							<?php echo JText::_( 'JAUTHOR' ); ?>
						</th>
						<th width="10%" class="nowrap hidden-phone">
							<?php echo JHtml::_('searchtools.sort', 'JDATE', 'h.modified', $listDirn, $listOrder); ?>
						</th>
						<th>
							<?php echo JText::_( 'COM_COLLECTOR_MODIFICATION' ); ?>
						</th>
						<th width="1%" class="nowrap hidden-phone">
							<?php echo JHtml::_('searchtools.sort', 'JGRID_HEADING_ID', 'h.id', $listDirn, $listOrder); ?>
						</th>
					</tr>
				</thead>
				<tfoot>
					<tr>
						<td colspan="7">
							<?php echo $this->pagination->getListFooter(); ?>
						</td>
					</tr>
				</tfoot>
				<tbody>
				<?php foreach ($this->items as $i => $item) :
					?>
					<tr class="row<?php echo $i % 2; ?>">
						<td>
							<?php echo $i+1+$this->pagination->limitstart; ?>
						</td>
						<td>
							<?php if ( $item->state == 2 ) echo JHtml::_('grid.id', $i, $item->id); ?>
						</td>
						<td>
							<div class="btn-group">
								<?php echo JHtml::_('jgrid.published', $item->state, $i, 'itemversions.', false, 'cb'); ?>
								<?php
								// Create dropdown items
								// $action = $trashed ? 'untrash' : 'trash';
								// JHtml::_('actionsdropdown.' . $action, 'cb' . $i, 'fields');

								// Render dropdown list
								// echo JHtml::_('actionsdropdown.render', $this->escape($item->field));
								?>
							</div>
						</td>
						<td class="small hidden-phone">
							<?php echo $this->escape($item->author_name); ?>
						</td>
						<td class="nowrap small hidden-phone">
							<?php echo JHtml::_('date',$item->modified, JText::_('DATE_FORMAT_LC4')); ?>
						</td>
						<td>
							<?php echo $item->modification; ?>
						</td>
						<td class="center">
							<?php echo $item->id; ?>
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
