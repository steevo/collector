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

?>
<?php if (count($this->items)>1) { ?>
	<table class="table table-striped" id="articleList">
		<tbody>
			<?php foreach ($this->items as $i => $item) : ?>
			<tr class="row<?php echo $i % 2; ?>" >
				<td class="center hidden-phone" rowspan="2" >
					<?php echo JHtml::_('grid.id', $i, $item->id); ?>
				</td>
				<td>
					<?php echo $this->escape($item->name); ?>
				</td>
			</tr>
			<tr>
				<td>
					<?php echo $this->escape($item->comment); ?>
				</td>
			</tr>
			<?php endforeach; ?>
		</tbody>
	</table>
<?php } else { ?>


<?php } ?>