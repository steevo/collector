<?php
/**
 * @package    Collector
 * @copyright  Copyright (C) 2010 - 2026 Philippe Ousset. All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace Steevo\Component\Collector\Site\Controller;

defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\Controller\BaseController;
use Joomla\CMS\Router\Route;
use Joomla\CMS\Session\Session;
use Joomla\Database\DatabaseInterface;
use Steevo\Component\Collector\Administrator\Table\CollectorItemsTable;

/**
 * « Mes propositions » : retrait par son auteur d'une proposition encore en attente
 */
class MyproposalsController extends BaseController
{
	public function withdraw(): void
	{
		$redirect = Route::_('index.php?option=com_collector&view=myproposals', false);

		if (!Session::checkToken('get') && !Session::checkToken()) {
			$this->setRedirect($redirect, Text::_('JINVALID_TOKEN'), 'error');

			return;
		}

		$user       = $this->app->getIdentity();
		$collection = $this->input->getInt('collection');
		$versionId  = $this->input->getInt('version_id');

		if ($user->guest || !$collection || !$versionId) {
			$this->setRedirect($redirect, Text::_('JERROR_ALERTNOAUTHOR'), 'error');

			return;
		}

		$exists = (int) Factory::getContainer()->get(DatabaseInterface::class)->setQuery(
			'SELECT COUNT(*) FROM #__collector WHERE id = ' . $collection
		)->loadResult();

		$table             = new CollectorItemsTable(Factory::getContainer()->get(DatabaseInterface::class));
		$table->collection = $collection;

		// Seulement sa propre proposition encore en attente (vérifié par la table)
		if ($exists && $table->withdrawVersion($versionId, (int) $user->id)) {
			$this->setRedirect($redirect, Text::_('COM_COLLECTOR_MYPROPOSALS_WITHDRAWN'));
		} else {
			$this->setRedirect($redirect, $table->getError() ?: Text::_('JERROR_ALERTNOAUTHOR'), 'error');
		}
	}
}
