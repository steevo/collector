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

use Joomla\CMS\Factory;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Router\Route;

// Check to ensure this file is included in Joomla!
defined('_JEXEC') or die( 'Restricted access' );


// HTMLHelper::Bootstrap::loadCss();
HTMLHelper::_('bootstrap.tooltip');
// HTMLHelper::_('behavior.framework', true);
HTMLHelper::_('bootstrap.modal');

$listOrder	= $this->escape($this->state->get('list.ordering'));
$listDirn	= $this->escape($this->state->get('list.direction'));

$user          = Factory::getApplication()->getIdentity();
$showIndicators = $user->id > 0 && !empty($this->userlistIndicators);
?>
<script language="javascript" type="text/javascript">
Joomla.tableOrdering = function( order, dir, task )
{
	var form = document.adminForm;
 
	form.filter_order.value = order;
	form.filter_order_Dir.value = dir;
	form.submit( task );
}

document.addEventListener('DOMContentLoaded', function() {
    document.querySelectorAll('[data-bs-toggle="tooltip"]').forEach(el => {
        new bootstrap.Tooltip(el);
    });
});
</script>
<table class="category table table-striped table-bordered table-hover">
	<thead>
		<tr>
		<?php
		for ($i=0, $f=count($this->fieldsDisplayed);$i<$f;$i++)
		{
			$row = $this->fieldsDisplayed[$i];
			if (!(( $row->isFiltered($this->params) == true ) && ($this->params->get('hide_filter'))) || (!$this->params->get('hide_filter')))
			{
			if ( (int)$row->_field->column_width != 0 ) {
				$style = "style='min-width:".(int)$row->_field->column_width."px'";
			} else {
				$style = "";
			}
				?>
				<th id="categorylist_header_title" <?php echo $style; ?>>
					<?php
					if ($row->_field->sort == 1) {
						$sort = explode('/',$row->_field->next_sorted_field);
						$order = $row->getOrderBy();
						if ($sort[0]!='0') {
							foreach ($sort as $key => $value) {
								foreach ($this->fields as $field) {
									if ($field->_field->id == $value) {
										$next_sorted_field = $field->getOrderBy();
									}
								}
								$order .= '//' . $next_sorted_field;
							}
						}
						echo HTMLHelper::_('grid.sort', $row->_field->field, $order, $listDirn, $listOrder );
						// echo HTMLHelper::_('grid.sort', $row->_field->field, $row->getOrderBy(), $listDirn, $listOrder );
					} else {
						echo $row->_field->field;
					}
					?>
				</th>
				<?php
			}
		}
		?>
		<?php if ($showIndicators): ?>
		<th class="text-center" style="width:80px"></th>
		<?php endif; ?>
		</tr>
	</thead>
	<tbody>
<?php
//$linkBase = 'index.php?option=com_collector&view=item&id='.$this->collection->id.'&Itemid='.$this->itemid.'&item=';
$linkBase = 'index.php?option=com_collector&view=item&collection='.$this->collection->slug.'&id=';

for ($i=0, $n=count($this->items);$i<$n;$i++)
{
	$tableentry = 1 + ( $i % 2 );
	$row = $this->items[$i];
	//$link = $linkBase.$row->cid;
	$link = Route::_($linkBase.$row->slug);
	?>

	<tr class="cat-list-row<?php echo $tableentry; ?>" style="cursor:pointer;cursor:hand;">
		<?php
		for ($j=0, $f=count($this->fieldsDisplayed);$j<$f;$j++)
		{
			$field = $this->fieldsDisplayed[$j];
			if (!(( $field->isFiltered($this->params) == true ) && ($this->params->get('hide_filter'))) || (!$this->params->get('hide_filter')))
			{
				$name = $field->_field->tablecolumn;
				$onclick = $field->makeLink?'onclick="javascript:location.href=\''.$link.'\';"':''; 
				?>
					<td <?php echo $onclick ?> style="vertical-align:middle;">
						<?php
						echo $field->display($row->$name,true,$this->params,$row->id);
						?>
					</td>
				<?php
			}
		}
		?>
		<?php if ($showIndicators): ?>
		<td class="text-center text-nowrap" style="vertical-align:middle">
			<?php
			$ind = $this->userlistIndicators[$row->id] ?? null;
			if ($ind):
			?>
			<?php if ($ind->want_count > 0): ?>
			<span class="badge bg-danger me-1"
				  data-bs-toggle="tooltip"
				  data-bs-placement="top"
				  data-bs-title="<?php echo Text::sprintf('COM_COLLECTOR_USERLIST_WANT_COUNT', $ind->want_count); ?>">
				<span class="icon-heart me-1"></span><?php echo (int) $ind->want_count; ?>
			</span>
			<?php endif; ?>
			<?php if ($ind->exchange_count > 0): ?>
			<span class="badge bg-success"
				  data-bs-toggle="tooltip"
				  data-bs-placement="top"
				  data-bs-title="<?php echo Text::sprintf('COM_COLLECTOR_USERLIST_EXCHANGE_COUNT', $ind->exchange_count); ?>">
				<span class="icon-loop me-1"></span><?php echo (int) $ind->exchange_count; ?>
			</span>
			<?php endif; ?>
			<?php endif; ?>
		</td>
		<?php endif; ?>
	</tr>
<?php
}
?>
</tbody>
</table>