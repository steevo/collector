<?php
/**
 * Joomla! component Collector
 *
 * Liste des collections.
 *
 * @package     Collector
 * @copyright   Copyright (C) 2010 - 2026 Philippe Ousset. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

\defined('_JEXEC') or die;

use Joomla\CMS\Language\Text;

?>
<div class="collector-collections<?php echo $this->escape($this->params->get('pageclass_sfx', '')); ?>">
	<?php if ($this->params->get('show_page_heading', 1)) : ?>
		<h1><?php echo $this->escape($this->params->get('page_heading')); ?></h1>
	<?php endif; ?>

	<?php if (!$this->items) : ?>
		<p><?php echo Text::_('COM_COLLECTOR_NO_COLLECTION'); ?></p>
	<?php else : ?>
		<?php foreach ($this->items as $item) : ?>
			<?php $this->item = $item; ?>
			<?php echo $this->loadTemplate('default'); ?>
		<?php endforeach; ?>
	<?php endif; ?>
</div>
