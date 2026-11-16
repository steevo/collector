<?php
/**
 * Joomla! component Collector
 *
 * @package     Collector
 * @copyright   Copyright (C) 2010 - 2026 Philippe Ousset. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 *
 * Collector is a Multi Purpose Listing Tool.
 * Originaly developped to list Collections
 * it can be used for several purpose.
 */

namespace Steevo\Component\Collector\Administrator\View\Collector;

defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\Helper\ContentHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\View\HtmlView as BaseHtmlView;
use Joomla\CMS\Router\Route;
use Joomla\CMS\Toolbar\ToolbarHelper;

/**
 * Tableau de bord de Collector
 */
class HtmlView extends BaseHtmlView
{
	protected $canDo;
	protected $collections = [];
	protected $totals;
	protected $pending = [];
	protected $imports = [];
	protected $lastImport;
	protected $members;
	protected $top = [];
	protected $activity = [];
	protected $mismatches = 0;
	protected $installedVersion;
	protected $lastVersion;

	/** Titres des collections, par identifiant */
	protected $collectionNames = [];

	public function display($tpl = null)
	{
		/** @var \Steevo\Component\Collector\Administrator\Model\CollectorModel $model */
		$model       = $this->getModel();
		$this->canDo = ContentHelper::getActions('com_collector');

		$this->collections = $model->getCollectionsStats();
		$this->totals      = (object) ['items' => 0, 'published' => 0, 'to_validate' => 0, 'proposals' => 0];

		foreach ($this->collections as $c) {
			$this->totals->items       += $c->total;
			$this->totals->published   += $c->published;
			$this->totals->to_validate += $c->to_validate;
			$this->totals->proposals   += $c->proposals;
			$this->collectionNames[(int) $c->id] = $c->name;
		}

		$this->pending    = $model->getPendingItems();
		$this->imports    = $model->getImportSessions();
		$this->lastImport = $model->getLastImportDate();
		$this->members    = $model->getMembersStats();
		$this->top        = [
			0 => $model->getTopUserlistItems(0),
			1 => $model->getTopUserlistItems(1),
			2 => $model->getTopUserlistItems(2),
		];
		$this->activity         = $model->getRecentActivity();
		$this->mismatches       = $model->getToValidateMismatches();
		$this->installedVersion = $model->getInstalledVersion();
		$this->lastVersion      = $model->getLastVersion();

		$this->addToolbar();

		parent::display($tpl);
	}

	protected function addToolbar()
	{
		ToolbarHelper::title(Text::_('COM_COLLECTOR_COLLECTOR_MANAGER'), 'cpanel');

		if ($this->canDo->get('core.admin')) {
			ToolbarHelper::preferences('com_collector');
		}
	}

	/**
	 * État de la version installée par rapport à la dernière publiée : [classe du badge, icône, texte]
	 */
	public function versionStatus(): ?array
	{
		$unknown = Text::_('COM_COLLECTOR_UNKNOWN');

		if ($this->lastVersion === $unknown || $this->installedVersion === $unknown) {
			return null;
		}

		return match (version_compare($this->installedVersion, $this->lastVersion)) {
			-1      => ['bg-warning-subtle text-warning-emphasis border border-warning-subtle', 'icon-warning', Text::_('COM_COLLECTOR_VERSION_TO_UPDATE')],
			0       => ['bg-success-subtle text-success-emphasis border border-success-subtle', 'icon-check', Text::_('COM_COLLECTOR_VERSION_UP_TO_DATE')],
			default => ['bg-info-subtle text-info-emphasis border border-info-subtle', 'icon-info-circle', Text::_('COM_COLLECTOR_DASHBOARD_VERSION_TEST')],
		};
	}

	/**
	 * Lien d'édition d'un objet en administration
	 */
	public function itemEditLink(int $collection, int $item): string
	{
		return Route::_('index.php?option=com_collector&task=item.edit&collection=' . $collection . '&id=' . $item);
	}

	/**
	 * Lien vers les versions d'un objet en administration
	 */
	public function itemVersionsLink(int $collection, int $item): string
	{
		return Route::_('index.php?option=com_collector&view=itemversions&collection=' . $collection . '&item=' . $item);
	}

	/**
	 * Date relative courte (« il y a 3 h », « hier »…), date complète au-delà d'une semaine
	 */
	public function relativeDate(?string $date): string
	{
		if (!$date) {
			return '';
		}

		$then = Factory::getDate($date)->toUnix();
		$diff = max(0, time() - $then);

		if ($diff < 3600) {
			return Text::plural('COM_COLLECTOR_DASHBOARD_MINUTES_AGO', max(1, (int) floor($diff / 60)));
		}

		if ($diff < 86400) {
			return Text::plural('COM_COLLECTOR_DASHBOARD_HOURS_AGO', (int) floor($diff / 3600));
		}

		if ($diff < 7 * 86400) {
			return Text::plural('COM_COLLECTOR_DASHBOARD_DAYS_AGO', (int) floor($diff / 86400));
		}

		return \Joomla\CMS\HTML\HTMLHelper::_('date', $date, Text::_('DATE_FORMAT_LC4'));
	}
}
