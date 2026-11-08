<?php
/**
 * Joomla! component Collector
 *
 * Fiche détaillée mise en page par le template choisi pour la collection.
 *
 * @package     Collector
 * @copyright   Copyright (C) 2010 - 2026 Philippe Ousset. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

\defined('_JEXEC') or die;

use Joomla\CMS\Uri\Uri;
use Steevo\Component\Collector\Administrator\Helper\CollectorTemplateHelper;
?>
<div class="collector-template collector-template-item collector-template-<?php echo (int) $this->itemTemplate->id; ?>">
	<?php echo CollectorTemplateHelper::renderItem(
		$this->itemTemplate,
		$this->fields,
		$this->item,
		$this->params,
		Uri::getInstance()->toString()
	); ?>
</div>
<?php
if (($this->params->get('comments') != 0) && ($this->params->get('comments_display_mode') == 0)) {
	echo $this->loadTemplate('comments');
}
