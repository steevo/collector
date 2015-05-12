<?php
/**
 * Joomla! 3.0 component Collector
 *
 * @version 	$Id: addtabscript.php 146 2014-03-17 23:42:40Z steevo $
 * @author 		Philippe Ousset steevo@steevo.fr
 * * @package 	Collector
 * @copyright   Copyright (C) 2010 - 2015 Philippe Ousset. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 *
 * Collector is a Multi Purpose Listing Tool.
 * Originaly developped to list Collections
 * it can be used for several purpose.
 */

defined('_JEXEC') or die;

$selector = empty($displayData['selector']) ? '' : $displayData['selector'];
$id = empty($displayData['id']) ? '' : $displayData['id'];
$active = empty($displayData['active']) ? '' : $displayData['active'];
$title = empty($displayData['title']) ? '' : $displayData['title'];

if (substr($id,0,1) == '#') {
	echo "(function($){
				$(document).ready(function() {
					// Handler for .ready() called.
					var tab = $('<li class=\"$active\"><a href=\"$id\" data-toggle=\"tab\">$title</a></li>');
					$('#" . $selector . "Tabs').append(tab);
				});
			})(jQuery);";
} else {
	echo "(function($){
				$(document).ready(function() {
					// Handler for .ready() called.
					var tab = $('<li class=\"$active\"><a href=\"$id\">$title</a></li>');
					$('#" . $selector . "Tabs').append(tab);
				});
			})(jQuery);";
}
