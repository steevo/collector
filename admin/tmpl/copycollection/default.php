<?php
/**
 * Joomla! 6 component Collector
 *
 * @package 	Collector
 * @copyright   Copyright (C) 2010 - 2026 Philippe Ousset. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 *
 * Collector is a Multi Purpose Listing Tool.
 * Originaly developped to list Collections
 * it can be used for several purpose.
 */

// Check to ensure this file is included in Joomla!
defined('_JEXEC') or die( 'Restricted access' );

use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Router\Route;
use Joomla\CMS\Session\Session;
use Joomla\CMS\Uri\Uri;

$copyConfig = [
	'url'        => Route::_('index.php?option=com_collector&task=collection.copy&format=raw', false),
	'token'      => Session::getFormToken(),
	'collection' => (int) $this->collection,
	'mode'       => (int) $this->copy_mode,
	'access'     => (int) $this->assetgroup_id,
	'txtFields'  => Text::_('COM_COLLECTOR_COLLECTION_COPY_FIELDS_COPIED_OUT_OF'),
	'txtItems'   => Text::_('COM_COLLECTOR_COLLECTION_COPY_ITEMS_COPIED_OUT_OF'),
	'txtEnd'     => Text::_('COM_COLLECTOR_COLLECTION_COPY_END'),
	'txtSelect'  => Text::_('COM_COLLECTOR_COLLECTION_COPY_SELECT_A_COLLECTION'),
];
?>
<div id="j-main-container" class="p-3">
	<div class="card" style="max-width:520px; margin:auto;">
		<div class="card-body text-center">
			<p id="update_fields" class="mb-2"></p>
			<p id="update_items" class="mb-3"></p>
			<h2 id="state" class="h4">
				<img src="<?php echo Uri::root(true); ?>/administrator/components/com_collector/assets/images/loading.gif" alt="" /><br />
				<?php echo Text::_('COM_COLLECTOR_COLLECTION_COPY_IN_PROGRESS'); ?>
			</h2>
		</div>
	</div>
</div>

<form action="<?php echo Route::_('index.php?option=com_collector'); ?>" method="post" name="adminForm" id="adminForm">
	<input type="hidden" name="task" value="" />
	<?php echo HTMLHelper::_('form.token'); ?>
</form>

<script>
(function () {
	'use strict';

	const cfg = <?php echo json_encode($copyConfig, JSON_HEX_TAG | JSON_HEX_AMP | JSON_UNESCAPED_UNICODE); ?>;
	const stateEl  = document.getElementById('state');
	const fieldsEl = document.getElementById('update_fields');
	const itemsEl  = document.getElementById('update_items');
	let updatedFields = 0;
	let updatedItems  = 0;

	// Bouton « Retour » désactivé pendant la copie
	function backButton() {
		return document.querySelector('#toolbar-arrow-left-2 button');
	}
	function setBackEnabled(enabled) {
		const btn = backButton();
		if (btn) { btn.disabled = !enabled; }
	}

	function setState(text, isError) {
		stateEl.textContent = text;
		stateEl.classList.toggle('text-danger', !!isError);
	}

	async function step(newCol) {
		const fd = new FormData();
		fd.append('id', cfg.collection);
		fd.append('mode', cfg.mode);
		fd.append('assetgroup_id', cfg.access);
		fd.append('new_col', newCol);
		fd.append(cfg.token, '1');

		let json;
		try {
			const resp = await fetch(cfg.url, { method: 'POST', body: fd, credentials: 'same-origin' });
			json = await resp.json();
		} catch (e) {
			setState('Erreur : ' + e.message, true);
			setBackEnabled(true);
			return;
		}

		if (!json.success || !json.data) {
			setState(json.message || 'Erreur', true);
			setBackEnabled(true);
			return;
		}

		const d = json.data;
		updatedFields += parseInt(d.updated_fields, 10);
		updatedItems  += parseInt(d.updated_items, 10);
		fieldsEl.textContent = updatedFields + cfg.txtFields + (updatedFields + parseInt(d.remaining_fields, 10));
		if (cfg.mode !== 1) {
			itemsEl.textContent = updatedItems + cfg.txtItems + (updatedItems + parseInt(d.remaining_items, 10));
		}

		if (parseInt(d.remaining_fields, 10) !== 0 || parseInt(d.remaining_items, 10) !== 0) {
			return step(parseInt(d.new_col, 10));
		}

		setState(cfg.txtEnd, false);
		setBackEnabled(true);
	}

	document.addEventListener('DOMContentLoaded', function () {
		if (!cfg.collection || !cfg.mode) {
			setState(cfg.txtSelect, true);
			return;
		}
		setBackEnabled(false);
		step(0);
	});
})();
</script>
