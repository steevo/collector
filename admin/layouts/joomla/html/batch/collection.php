<?php

/**
 * @package     Joomla.Site
 * @subpackage  Layout
 *
 * @copyright   (C) 2015 Open Source Matters, Inc. <https://www.joomla.org>
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Language\Text;

extract($displayData);

/**
 * Layout variables
 * -----------------
 * @var   string  $extension  The extension name
 */

// Create the copy/move options.
$options = [
    HTMLHelper::_('select.option', '0', Text::_('COM_COLLECTOR_BATCH_NO_COPY')),
    HTMLHelper::_('select.option', '1', Text::_('COM_COLLECTOR_BATCH_COPY_ONLY_FIELDS')),
    HTMLHelper::_('select.option', '2', Text::_('COM_COLLECTOR_BATCH_COPY_FIELDS_AND_ITEMS_WITHOUT_HISTORY')),
    HTMLHelper::_('select.option', '3', Text::_('COM_COLLECTOR_BATCH_COPY_FIELDS_AND_ITEMS_WITH_HISTORY'))
];

/** @var Joomla\CMS\WebAsset\WebAssetManager $wa */
$wa = Factory::getApplication()->getDocument()->getWebAssetManager();
$wa->useScript('joomla.batch-copymove');

?>
<label id="batch-choose-action-lbl" for="batch-copy-mode-id">
    <?php echo Text::_('COM_COLLECTOR_BATCH_COPY_LABEL'); ?>
</label>
<div id="batch-copy-mode" class="control-group">
    <select name="batch[copy_mode]" class="form-select" id="batch-copy-mode-id">
         <?php echo HTMLHelper::_('select.options', $options, 'value', 'text', '0'); ?>
    </select>
</div>
