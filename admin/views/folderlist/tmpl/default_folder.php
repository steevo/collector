<?php
/**
 * Joomla! 3.0 component Collector
 *
 * @version 	$Id: default_folder.php 146 2014-03-17 23:42:40Z steevo $
 * @author 		Philippe Ousset steevo@steevo.fr
 * * @package 	Collector
 * @copyright   Copyright (C) 2010 - 2015 Open Source Matters, Inc. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 *
 * Collector is a Multi Purpose Listing Tool.
 * Originaly developped to list Collections
 * it can be used for several purpose.
 */

// Check to ensure this file is included in Joomla!
defined('_JEXEC') or die( 'Restricted access' );

$input = JFactory::getApplication()->input;
?>
<li class="imgOutline thumbnail height-80 width-80 center">
	<a href="index.php?option=com_collector&amp;view=folderlist&amp;tmpl=component&amp;folder=<?php echo $this->_tmp_folder->path_relative; ?>" target="fileframe">
		<div class="height-50">
			<i class="icon-folder-2"></i>
		</div>
		<div class="small">
			<?php echo JHtml::_('string.truncate', $this->_tmp_folder->name, 10, false); ?>
		</div>
	</a>
</li>
