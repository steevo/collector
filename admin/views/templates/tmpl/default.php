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

// $db = JFactory::getDBO();
// $user = JFactory::getUser();
// $config = JFactory::getConfig();
// $now = JFactory::getDate();

?>
<!--
<form action="index.php" method="post" name="adminForm" >
<table class="adminform">
<tr>
	<td nowrap="nowrap"><center><big><b>
		<?php
			// echo JText::_( 'COM_COLLECTOR_COLLECTION' ) . ' : ';
			// echo $this->lists['collections'];
		?>
	</b></big></center></td>
</tr>
</table>
<table>
<tr>
	<td align="left" width="100%">
		<?php // echo JText::_( 'COM_COLLECTOR_FILTER' ); ?>:
		<input type="text" name="search" id="search" value="<?php // echo $this->lists['search'];?>" class="text_area" onchange="document.adminForm.submit();" />
		<button onclick="this.form.submit();"><?php // echo JText::_( 'COM_COLLECTOR_GO' ); ?></button>
		<button onclick="document.getElementById('search').value='';this.form.getElementById('filter_state').value='';this.form.submit();"><?php // echo JText::_( 'COM_COLLECTOR_RESET' ); ?></button>
	</td>
	<td nowrap="nowrap">
		<?php
			// echo $this->lists['type'];
		?>
	</td>
</tr>
</table>
<div class="editcell">
	<table class="adminlist">
		<thead>
			<tr>
				<th width="5">
					<?php // echo JText::_( '#' ); ?>
				</th>
				<th width="200" >
					<?php // echo JText::_( 'COM_COLLECTOR_NAME' ); ?>
				</th>
				<th width="20">
					<?php // echo JText::_( 'COM_COLLECTOR_DEFAULT' ); ?>
				</th>
				<th width="100">
					<?php // echo JText::_( 'COM_COLLECTOR_TYPE' ); ?>
				</th>
				<th width="10">
					<?php // echo JText::_( 'COM_COLLECTOR_ID' ); ?>
				</th>
			</tr>
		</thead>
		<tfoot>
			<tr>
				<td colspan="5" >
					<?php // echo $this->pagination->getListFooter(); ?>
				</td>
			</tr>
		</tfoot>
		<tbody>
		<?php
		// $k = 0;
		// for ($i=0, $n=count($this->items);$i<$n;$i++)
		// {
			// $row = $this->items[$i];
			
			// $link = JRoute::_( 'index.php?option=com_collector&amp;controller=templates&amp;task=edit&amp;cid[]=' . $row->id );
			
			// if ( $row->alias != 'default' )
			// {
				// $name = '<a href="'.$link.'">'.htmlspecialchars($row->name, ENT_QUOTES).'</a>';
			// }
			// else
			// {
				// $name = htmlspecialchars($row->name, ENT_QUOTES);
				// if  ( ( ($row->client == 1) AND ($this->default['listing'] == 0) ) OR ( ($row->client == 2) AND ($this->default['detail'] == 0) ) )
				// {
					// $row->home = 1;
				// }
			// }
			
			// if ($row->client == 1)
			// {
				// $client = JText::_( 'COM_COLLECTOR_LISTING' );
			// }
			// else
			// {
				// $client = JText::_( 'COM_COLLECTOR_DETAIL' );
			// }
			
			?>
			<tr class="<?php // echo "row$k"; ?>">
				<td align="center" >
					<?php // echo $i+1+$this->pagination->limitstart; ?>
				</td>
				<td width="5" >
					<input type="radio" id="cb<?php // echo $i;?>" name="cid[]" value="<?php // echo $row->id; ?>" onclick="isChecked(this.checked);" />
					<?php // echo $name; ?>
				</td>
				<td align="center">
					<?php // if ( $row->home == 1 ) : ?>
					<img src="templates/khepri/images/menu/icon-16-default.png" alt="<?php // echo JText::_( 'COM_COLLECTOR_DEFAULT' ); ?>" />
					<?php // else : ?>
					&nbsp;
					<?php // endif; ?>
				</td>
				<td align="center" >
					<?php // echo $client; ?>
				</td>
				<td align="center">
					<?php // echo $row->id; ?>
				</td>
			</tr>
			<?php
			// $k = 1 - $k;
		// }
		?>
		</tbody>
	</table>
</div>

<input type="hidden" name="option" value="com_collector" />
<input type="hidden" name="view" value="templates" />
<input type="hidden" name="task" value="" />
<input type="hidden" name="boxchecked" value="0" />
<input type="hidden" name="controller" value="templates" />
<?php // echo JHTML::_( 'form.token' ); ?>
</form> -->
