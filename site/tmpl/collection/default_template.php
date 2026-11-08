<?php
/**
 * Joomla! component Collector
 *
 * Vue liste mise en page par le template choisi pour la collection.
 *
 * @package     Collector
 * @copyright   Copyright (C) 2010 - 2026 Philippe Ousset. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

\defined('_JEXEC') or die;

use Joomla\CMS\Router\Route;
use Steevo\Component\Collector\Administrator\Helper\CollectorTemplateHelper;

$linkBase = 'index.php?option=com_collector&view=item&collection=' . $this->collection->slug . '&id=';
?>
<div class="collector-template collector-template-list collector-template-<?php echo (int) $this->listTemplate->id; ?>">
	<?php echo CollectorTemplateHelper::renderList(
		$this->listTemplate,
		$this->fields,
		$this->items,
		$this->params,
		static fn ($item) => Route::_($linkBase . $item->slug)
	); ?>
</div>
