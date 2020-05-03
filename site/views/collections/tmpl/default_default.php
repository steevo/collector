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

$link = 'index.php?option=com_collector&view=collection&id='.$this->item->id;
$app = JFactory::getApplication();
$Itemid = $app->input->get('Itemid', 0, 'get');

?>
<div class="items-row">
	<h2>
		<a href="<?php echo JRoute::_($link.'&Itemid='.$Itemid); ?>"><?php echo $this->escape($this->item->name); ?></a>
	</h2>
	<?php echo $this->item->description; ?>
</div>