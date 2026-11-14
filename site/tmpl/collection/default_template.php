<?php
/**
 * Joomla! component Collector
 *
 * Vue liste mise en page par le template choisi pour la collection (gabarit commun collector.list.template).
 *
 * @package     Collector
 * @copyright   Copyright (C) 2010 - 2026 Philippe Ousset. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

\defined('_JEXEC') or die;

use Joomla\CMS\Layout\LayoutHelper;
use Joomla\CMS\Router\Route;

$linkBase = 'index.php?option=com_collector&view=item&collection=' . $this->collection->slug . '&id=';

echo LayoutHelper::render('collector.list.template', [
	'template' => $this->listTemplate,
	'fields'   => $this->fields,
	'items'    => $this->items,
	'params'   => $this->params,
	'link'     => static fn ($item) => Route::_($linkBase . $item->slug),
], JPATH_SITE . '/components/com_collector/layouts');
