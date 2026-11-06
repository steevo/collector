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
<div class="row title-alias form-vertical mb-3">
    <div class="col-12 col-md-6">
        <?php echo $title ? $form->renderField($title) : ''; ?>
    </div>
    <div class="col-12 col-md-6">
        <?php echo $alias ? $form->renderField($alias) : ''; ?>
    </div>
</div>
