<?php
/**
 * @package     Joomla.CMS
 * @subpackage  Layout
 *
 * @copyright   Copyright (C) 2005 - 2013 Open Source Matters, Inc. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
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
