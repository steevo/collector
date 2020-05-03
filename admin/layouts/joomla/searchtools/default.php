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

defined('JPATH_BASE') or die;

$data = $displayData;

if ($data['view']->getName() == 'listitems') {
	// Receive overridable options
	$data['options'] = !empty($data['options']) ? $data['options'] : array();

	$doc = JFactory::getDocument();

	$doc->addStyleDeclaration("
		/* Fixed filter field in search bar */
		.js-stools .js-stools-defined {
			float: left;
			margin-right: 10px;
		}
		html[dir=rtl] .js-stools .js-stools-defined {
			float: right;
			margin-left: 10px
			margin-right: 0;
		}
		.js-stools .js-stools-container-bar .js-stools-field-filter .chzn-container {
			padding: 3px 0;
		}
	");

	// defined filter doesn't have to activate the filter bar
	unset($data['view']->activeFilters['defined']);
}
if ($data['view']->getName() == 'fields') {
	// Receive overridable options
	$data['options'] = !empty($data['options']) ? $data['options'] : array();

	$doc = JFactory::getDocument();

	$doc->addStyleDeclaration("
		/* Fixed filter field in search bar */
		.js-stools .js-stools-collection {
			float: left;
			margin-right: 10px;
		}
		html[dir=rtl] .js-stools .js-stools-collection {
			float: right;
			margin-left: 10px
			margin-right: 0;
		}
		.js-stools .js-stools-container-bar .js-stools-field-filter .chzn-container {
			padding: 3px 0;
		}
	");

	// defined filter doesn't have to activate the filter bar
	unset($data['view']->activeFilters['collection']);
}
if ($data['view']->getName() == 'templates') {
	// Receive overridable options
	$data['options'] = !empty($data['options']) ? $data['options'] : array();

	$doc = JFactory::getDocument();

	$doc->addStyleDeclaration("
		/* Fixed filter field in search bar */
		.js-stools .js-stools-collection {
			float: left;
			margin-right: 10px;
		}
		html[dir=rtl] .js-stools .js-stools-collection {
			float: right;
			margin-left: 10px
			margin-right: 0;
		}
		.js-stools .js-stools-container-bar .js-stools-field-filter .chzn-container {
			padding: 3px 0;
		}
	");

	// defined filter doesn't have to activate the filter bar
	unset($data['view']->activeFilters['collection']);
}
if ($data['view']->getName() == 'items') {
	// Receive overridable options
	$data['options'] = !empty($data['options']) ? $data['options'] : array();

	$doc = JFactory::getDocument();

	$doc->addStyleDeclaration("
		/* Fixed filter field in search bar */
		.js-stools .js-stools-collection {
			float: left;
			margin-right: 10px;
		}
		html[dir=rtl] .js-stools .js-stools-collection {
			float: right;
			margin-left: 10px
			margin-right: 0;
		}
		.js-stools .js-stools-container-bar .js-stools-field-filter .chzn-container {
			padding: 3px 0;
		}
	");

	// defined filter doesn't have to activate the filter bar
	unset($data['view']->activeFilters['collection']);
}
if ($data['view']->getName() == 'userslists') {
	// Receive overridable options
	$data['options'] = !empty($data['options']) ? $data['options'] : array();

	$doc = JFactory::getDocument();

	$doc->addStyleDeclaration("
		/* Fixed filter field in search bar */
		.js-stools .js-stools-collection {
			float: left;
			margin-right: 10px;
		}
		html[dir=rtl] .js-stools .js-stools-collection {
			float: right;
			margin-left: 10px
			margin-right: 0;
		}
		.js-stools .js-stools-container-bar .js-stools-field-filter .chzn-container {
			padding: 3px 0;
		}
	");

	// defined filter doesn't have to activate the filter bar
	unset($data['view']->activeFilters['collection']);
}
if ($data['view']->getName() == 'itemversions') {
	// Receive overridable options
	$data['options'] = !empty($data['options']) ? $data['options'] : array();

	$doc = JFactory::getDocument();

	$doc->addStyleDeclaration("
		/* Fixed filter field in search bar */
		.js-stools .js-stools-collection .js-stools-item {
			float: left;
			margin-right: 10px;
		}
		html[dir=rtl] .js-stools .js-stools-collection .js-stools-item {
			float: right;
			margin-left: 10px
			margin-right: 0;
		}
		.js-stools .js-stools-container-bar .js-stools-field-filter .chzn-container {
			padding: 3px 0;
		}
	");

	// defined filter doesn't have to activate the filter bar
	unset($data['view']->activeFilters['collection']);
	unset($data['view']->activeFilters['item']);
}
?>
<div class="js-stools clearfix">
	<div class="clearfix">
		<div class="js-stools-container-bar">
			<?php echo JLayoutHelper::render('joomla.searchtools.default.category', $data); ?>
		</div>
	</div>
</div>

<?php
// Display the main joomla layout
echo JLayoutHelper::render('joomla.searchtools.default', $data, null, array('component' => 'none'));
