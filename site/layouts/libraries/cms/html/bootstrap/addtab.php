<?php
/**
 * Joomla! 3.0 component Collector
 *
 * @version 	$Id: addtab.php 146 2014-03-17 23:42:40Z steevo $
 * @author 		Philippe Ousset steevo@steevo.fr
 * * @package 	Collector
 * @copyright   Copyright (C) 2010 - 2015 Open Source Matters, Inc. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 *
 * Collector is a Multi Purpose Listing Tool.
 * Originaly developped to list Collections
 * it can be used for several purpose.
 */

defined('_JEXEC') or die;

$id = empty($displayData['id']) ? '' : $displayData['id'];
$active = empty($displayData['active']) ? '' : $displayData['active'];

if (substr($id,0,1) == '#') {
	$id = substr($id,1);
	echo '<div id="' . $id . '" class="tab-pane' . $active . '">';
}
?>
