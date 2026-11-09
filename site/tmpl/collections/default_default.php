<?php
/**
 * Joomla! component Collector
 *
 * Une collection dans la liste des collections.
 *
 * @package     Collector
 * @copyright   Copyright (C) 2010 - 2026 Philippe Ousset. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

\defined('_JEXEC') or die;

use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Router\Route;

$link = Route::_('index.php?option=com_collector&view=collection&id=' . (int) $this->item->id);
?>
<div class="collector-collections-item mb-4">
	<h2><a href="<?php echo $link; ?>"><?php echo $this->escape($this->item->name); ?></a></h2>
	<?php if (trim((string) $this->item->description) !== '') : ?>
		<div class="collector-collections-description">
			<?php echo HTMLHelper::_('content.prepare', $this->item->description, '', 'com_collector.collections'); ?>
		</div>
	<?php endif; ?>
</div>
