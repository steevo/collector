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

defined('_JEXEC') or die('Restricted access');

JHtml::_('behavior.tooltip');
JHtml::_('behavior.multiselect');

$listOrder	= $this->escape($this->state->get('list.ordering'));
$listDirn	= $this->escape($this->state->get('list.direction'));
?>

<script language="javascript" type="text/javascript">
	<!--
	function loadImage()
	{
		var selectedIndex = document.adminForm2.ico.selectedIndex;
		if (document.adminForm2.ico.options[selectedIndex].value!='') {
			document.preview.src='<?php echo JURI::root(); ?>administrator/components/com_collector/assets/images/' + document.adminForm2.ico.options[selectedIndex].value;
			document.preview.alt=document.adminForm2.ico.options[selectedIndex].value;
		} else {
			document.preview.src='<?php echo JURI::root(); ?>administrator/components/com_collector/assets/images/page_white.png';
			document.preview.alt='';
		}
	}
	//-->
</script>
<div class="container-fluid">
<form action="<?php echo JRoute::_('index.php?option=com_collector&view=filesconfig'); ?>" method="post" name="adminForm" id="adminForm" >
	<table class="table table-striped" id="fieldList">
		<thead>
			<tr>
				<th width="80px">
					<?php echo JHTML::_('grid.sort',  'COM_COLLECTOR_EXT', 'e.ext', $listDirn, $listOrder ); ?>
				</th>
				<th>
					<?php echo JHTML::_('grid.sort',  'COM_COLLECTOR_TYPE', 'e.type', $listDirn, $listOrder ); ?>
				</th>
				<th width="60px">
					<?php echo JText::_( 'COM_COLLECTOR_ACTIONS' ); ?>
				</th>
			</tr>
		</thead>
		<tbody>
		<?php
		$i = 0;
		$k = 0;
		foreach ( $this->items as $i => $item )
		{
			if ( $item->state == 1 )
			{
				$img = 'publish_g.png';
				$alt = JText::_( 'COM_COLLECTOR_UNPUBLISH' );
			}
			else
			{
				$img = 'publish_r.png';
				$alt = JText::_( 'COM_COLLECTOR_PUBLISH' );
			}
			
			$linkPublish = JRoute::_( 'index.php?option=com_collector&amp;task=filemanager.' . ($item->state ? 'disable' : 'enable') . '&amp;id=' . $item->id );
			$publish = '<span class="editlinktip hasTip jgrid" title="' . $alt . '">';
			$publish .= '<a href="' . $linkPublish . '" ><img src="templates/hathor/images/admin/' . $img . '" width="16" height="16" border="0" alt="' . $alt . '" /></a></span>';
			
			$linkDelete = JRoute::_( 'index.php?option=com_collector&amp;task=filemanager.remove_ext&amp;id=' . $item->id );
			$delete = '<span class="editlinktip hasTip" title="' . JText::_('COM_COLLECTOR_DELETE') . '">';
			$delete .= '<a href="' . $linkDelete . '" ><img src="components/com_collector/assets/images/cross.png" alt="' . JText::_('COM_COLLECTOR_DELETE') . '" /></a></span>';
			
			$linkEdit = JRoute::_( 'index.php?option=com_collector&amp;view=filesconfig&amp;tmpl=component&amp;id=' . $item->id );
			$edit = '<span class="editlinktip hasTip" title="' . JText::_('COM_COLLECTOR_EDIT') . '">';
			$edit .= '<a href="' . $linkEdit . '" ><img src="components/com_collector/assets/images/icon-16-edit.png" alt="' . JText::_('COM_COLLECTOR_EDIT') . '" /></a></span>';
			
			?>
			<tr class="<?php echo "row$k"; ?>">
				<td align="center" >
					.<?php echo $item->ext; ?>
				</td>
				<td>
					<img src="components/com_collector/assets/images/<?php echo $item->ico; ?>">&nbsp;<?php echo JText::_('COM_COLLECTOR_'.$item->type); ?>
				</td>
				<td align="center">
					<?php
						echo $publish .'&nbsp'. $edit .'&nbsp'. $delete;
					?>
				</td>
			</tr>
			<?php
			$k = 1 - $k;
			$i++;
		}
		?>
		</tbody>
	</table>

<input type="hidden" name="task" value="" />
<input type="hidden" name="tmpl" value="component" />
<input type="hidden" name="filter_order" value="<?php echo $listOrder; ?>" />
<input type="hidden" name="filter_order_Dir" value="<?php echo $listDirn; ?>" />

</form>

<br />

<form action="index.php" method="post" name="adminForm2" id="adminForm2" >
<fieldset>
	<legend><?php echo $this->row->id == 0 ? JText::_('COM_COLLECTOR_NEW_EXT') : JText::_('COM_COLLECTOR_EDIT_EXT'); ?></legend>
	<table width="100%" >
		<tr>
			<td>
				<?php echo JText::_('COM_COLLECTOR_EXT'); ?>&nbsp;:
			</td>
			<td>
				<input type="text" name="ext" size="5" value="<?php echo '.' . $this->row->ext; ?>" />
			</td>
		</tr>
		<tr>
			<td>
				<?php echo JText::_('COM_COLLECTOR_TYPE'); ?>&nbsp;:
			</td>
			<td>
				<?php echo JHTML::_('select.genericlist', $this->types, 'type', ' class=”inputbox” size="1" ', 'value', 'text', $this->row->type); ?>
			</td>
		</tr>
		<tr>
			<td>
				<?php
				if ($this->row->ico == '') {
					$ico = 'page_white.png';
				} else {
					$ico = $this->row->ico;
				}
				?>
				<img name="preview" src="components/com_collector/assets/images/<?php echo $ico; ?>" height="16" width="16" border="0" />
			</td>
			<td>
				<?php echo JHTML::_('list.images',  'ico', $this->row->ico, ' onchange="loadImage();"', 'administrator/components/com_collector/assets/images' ); ?>
			</td>
			<td>
				<input type="submit" class="btn btn-success" value="<?php echo JText::_('COM_COLLECTOR_SAVE'); ?>" />
			</td>
		</tr>
	</table>

<input type="hidden" name="option" value="com_collector" />
<input type="hidden" name="task" value="filemanager.save_ext" />
<input type="hidden" name="id" value="<?php echo $this->row->id; ?>" />
</fieldset>
</form>
</div>