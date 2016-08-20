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

if ( ($this->params->get('comments') == 1) && (file_exists(JPATH_SITE.DS.'components'.DS.'com_jcomments'.DS.'jcomments.php')) )
{
	require_once(JPATH_SITE.DS.'components'.DS.'com_jcomments'.DS.'jcomments.php');
	echo JComments::showComments($this->item->id, 'com_collector', $this->escape($this->item->fulltitle));
}
?>