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
<table class="category" width="100%" >
	<?php
	for ($i=0, $n=count($this->fields);$i<$n;$i++)
	{
		$field = $this->fields[$i];
		$name_field = $field->_field->tablecolumn;
		
		if ((!( $field->display($this->item->$name_field,false,$this->params) === false ))||($this->params->get('show_emptyfield'))) {
			echo '<tr>';
			?>
				<td>
					<label for="name">
						<?php echo JText::_($field->getFieldName()) . ':' ; ?>
					</label>
				</td>
				<td>
					<?php echo $field->display($this->item->$name_field,false,$this->params); ?>
				</td>
			<?php
			echo '</tr>';
		}
	}
	?>
</table>