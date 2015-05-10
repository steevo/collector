<?php
/**
 * Joomla! 3.0 component Collector
 *
 * @package 	Collector
 * @copyright   Copyright (C) 2010 - 2015 Open Source Matters, Inc. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 *
 * Collector is a Multi Purpose Listing Tool.
 * Originaly developped to list Collections
 * it can be used for several purpose.
 */

// Check to ensure this file is included in Joomla!
defined('_JEXEC') or die( 'Restricted access' );

?>

<?php if (count($this->folders) > 0) { ?>
<ul class="manager thumbnails">

	<?php for ($i = 0, $n = count($this->folders); $i < $n; $i++) :
		$this->setFolder($i);
		echo $this->loadTemplate('folder');
	endfor; ?>
	
</ul>
<?php } else { ?>
	<div id="media-noimages">
		<div class="alert alert-info"><?php echo JText::_('COM_COLLECTOR_NO_FOLDERS_FOUND'); ?></div>
	</div>
<?php } ?>
