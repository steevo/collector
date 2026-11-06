<?php
/**
 * Joomla! 3.0 component Collector
 *
 * @package 	Collector
 * @copyright   Copyright (C) 2010 - 2020 Philippe Ousset. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 *
 * Collector is a Multi Purpose Listing Tool.
 * Originaly developped to list Collections
 * it can be used for several purpose.
 */

// Check to ensure this file is included in Joomla!
defined('_JEXEC') or die( 'Restricted access' );

use Joomla\CMS\Language\Text;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Layout\LayoutHelper;

$published = (int) $this->state->get('filter.published');
?>

<div class="p-3">
    <div class="row">
		<p><?php echo Text::_('COM_COLLECTOR_BATCH_TIP'); ?>
	</div>
    <div class="row">
		<div class="form-group col-md-12">
            <div class="controls">
                <?php echo LayoutHelper::render('joomla.html.batch.access', []); ?>
            </div>
        </div>
	</div>
    <div class="row">
        <div class="form-group col-md-12">
            <div class="controls">
                <?php echo LayoutHelper::render('joomla.html.batch.collection', ['extension' => 'com_collector']); ?>
            </div>
        </div>
	</div>
</div>
<div class="btn-toolbar p-3">
    <joomla-toolbar-button task="collection.batch" class="ms-auto">
        <button type="button" class="btn btn-success"><?php echo Text::_('JGLOBAL_BATCH_PROCESS'); ?></button>
    </joomla-toolbar-button>
</div>