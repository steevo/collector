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

// Check to ensure this file is included in Joomla!
defined('_JEXEC') or die( 'Restricted access' );

JHtml::addIncludePath(JPATH_COMPONENT.'/helpers/html');

JHtmlBootstrap::loadCss();
JHtml::_('behavior.tooltip');
// JHtml::_('behavior.framework', true);
JHtml::_('behavior.modal');

$listOrder	= $this->escape($this->state->get('list.ordering'));
$listDirn	= $this->escape($this->state->get('list.direction'));
?>
<script language="javascript" type="text/javascript">
Joomla.tableOrdering = function( order, dir, task )
{
	var form = document.adminForm;
 
	form.filter_order.value = order;
	form.filter_order_Dir.value = dir;
	form.submit( task );
}
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
				?>
				<th id="categorylist_header_title">
					<?php
					if ($row->_field->sort == 1) {
						echo JHTML::_('grid.sort', $row->_field->field, $row->getOrderBy(), $listDirn, $listOrder );
					} else {
						echo $row->_field->field;
					}
					?>
				</th>
				<?php
			}
		}
		?>
		<?php if (!empty($this->userslists)) : ?>
		<th style="border-left-width:0;" ></th>
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
	$link = JRoute::_($linkBase.$row->slug);
	?>

	<tr class="cat-list-row<?php echo $tableentry; ?>" style="cursor:pointer;cursor:hand;">
		<?php
		for ($j=0, $f=count($this->fieldsDisplayed);$j<$f;$j++)
		{
			$field = $this->fieldsDisplayed[$j];
			if (!(( $field->isFiltered($this->params) == true ) && ($this->params->get('hide_filter'))) || (!$this->params->get('hide_filter')))
			{
				$name = $field->_field->tablecolumn;
				?>
					<td onclick="javascript:location.href='<?php echo $link; ?>';" style="vertical-align:middle;" >
						<a href="<?php echo $link; ?>" >
						<?php
						echo $field->display($row->$name,true,$this->params);
						?>
						</a>
					</td>
				<?php
			}
		}
		?>
		<?php if (!empty($this->userslists)) : ?>
		<td class="list-edit" style="border-left-width:0;vertical-align:middle;" >
			<div id="dropdown<?php echo $row->id; ?>" class="btn-group collector-dropdown">
				<?php // echo JHtml::_('collectorgrid.edit', $item->state, $item->id, 'item.', $canChange); ?>
				<?php
				// Create dropdown items
				foreach ( $this->userslists as $userslist )
				{
					JHtml::_('collectordropdown.add', $row->collection, $row->id, $userslist);

					if ( $this->usersitems[$userslist->id] != null )
					{
						if(array_key_exists($row->id, $this->usersitems[$userslist->id]))
						{
							JHtml::_('collectordropdown.remove', $row->collection, $row->id, $userslist);
						}
					}
				}

				// Render dropdown list
				echo JHtml::_('collectordropdown.render');
				?>
			</div>
		</td>
		<?php endif; ?>
	</tr>
<?php
}
?>
</tbody>
</table>