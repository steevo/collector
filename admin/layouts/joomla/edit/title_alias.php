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

defined('JPATH_BASE') or die;

$form = $displayData->getForm();

$title = $form->getField('title') ? 'title' : ($form->getField('name') ? 'name' : ($form->getField('field') ? 'field' : ''));
$alias = $form->getField('alias') ? 'alias' : ($form->getField('tablecolumn') ? 'tablecolumn' : '');

?>
<div class="form-inline form-inline-header">
	<?php
	echo $title ? $form->getControlGroup($title) : '';
	echo $alias ? $form->getControlGroup($alias) : '';
	?>
</div>
