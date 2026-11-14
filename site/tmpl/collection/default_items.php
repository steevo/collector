<?php
/**
 * Joomla! component Collector
 *
 * Vue liste en tableau (gabarit commun collector.list.table), avec les indicateurs
 * des listes des membres (souhaits, échanges).
 *
 * @package     Collector
 * @copyright   Copyright (C) 2010 - 2026 Philippe Ousset. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

\defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Layout\LayoutHelper;
use Joomla\CMS\Router\Route;

$user     = Factory::getApplication()->getIdentity();
$linkBase = 'index.php?option=com_collector&view=item&collection=' . $this->collection->slug . '&id=';
$columns  = [];

if ($user->id > 0 && !empty($this->userlistIndicators)) {
	$columns[] = [
		'label' => '',
		'class' => 'text-center text-nowrap',
		'cell'  => function ($row) {
			$ind = $this->userlistIndicators[$row->id] ?? null;
			$out = '';

			if ($ind && $ind->want_count > 0) {
				$out .= '<span class="badge bg-danger me-1" data-bs-toggle="tooltip" data-bs-placement="top" data-bs-title="'
					. Text::sprintf('COM_COLLECTOR_USERLIST_WANT_COUNT', $ind->want_count) . '">'
					. '<span class="icon-heart me-1"></span>' . (int) $ind->want_count . '</span>';
			}

			if ($ind && $ind->exchange_count > 0) {
				$out .= '<span class="badge bg-success" data-bs-toggle="tooltip" data-bs-placement="top" data-bs-title="'
					. Text::sprintf('COM_COLLECTOR_USERLIST_EXCHANGE_COUNT', $ind->exchange_count) . '">'
					. '<span class="icon-loop me-1"></span>' . (int) $ind->exchange_count . '</span>';
			}

			return $out;
		},
	];
}

echo LayoutHelper::render('collector.list.table', [
	'fields'    => $this->fields,
	'items'     => $this->items,
	'params'    => $this->params,
	'link'      => static fn ($item) => Route::_($linkBase . $item->slug),
	'sortable'  => true,
	'listOrder' => $this->escape($this->state->get('list.ordering')),
	'listDirn'  => $this->escape($this->state->get('list.direction')),
	'columns'   => $columns,
], JPATH_SITE . '/components/com_collector/layouts');
