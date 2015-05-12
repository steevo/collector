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

defined('JPATH_BASE') or die;

$data = $displayData;

if ($data['view']->getName() == 'listitems') {
// We will get the defined filter & remove it from the form filters
	$definedField = $data['view']->filterForm->getField('defined');
	?>
	<div class="js-stools-field-filter js-stools-defined">
		<?php echo $definedField->input; ?>
	</div>
	<?php
}
if ( ($data['view']->getName() == 'fields') || ($data['view']->getName() == 'items') || ($data['view']->getName() == 'userslists') ) {
// We will get the defined filter & remove it from the form filters
	$collection = $data['view']->filterForm->getField('collection');
	?>
	<div class="js-stools-field-filter js-stools-collection">
		<?php echo $collection->input; ?>
	</div>
	<?php
}
if ($data['view']->getName() == 'itemversions') {
// We will get the defined filter & remove it from the form filters
	$collection = $data['view']->filterForm->getField('collection');
	$item = $data['view']->filterForm->getField('item');
	?>
	<div class="js-stools-field-filter js-stools-collection">
		<?php echo $collection->input; ?>
		<?php echo $item->input; ?>
	</div>
	<?php
}
