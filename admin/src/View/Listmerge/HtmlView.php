<?php
/**
 * Joomla! component Collector
 *
 * @package     Collector
 * @copyright   Copyright (C) 2010 - 2026 Philippe Ousset. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace Steevo\Component\Collector\Administrator\View\Listmerge;

defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\View\HtmlView as BaseHtmlView;
use Joomla\CMS\Router\Route;
use Joomla\CMS\Toolbar\ToolbarHelper;

/**
 * Écran de fusion de deux valeurs d'une liste prédéfinie.
 *
 * @package  Collector
 */
class HtmlView extends BaseHtmlView
{
	/** @var int */
	protected $defined;

	/** @var int */
	protected $source;

	/** @var int */
	protected $target;

	/** @var object */
	protected $summary;

	/** @var object[] */
	protected $values;

	/** @var array */
	protected $usage;

	/** @var bool */
	protected $canMerge;

	public function display($tpl = null)
	{
		$app   = Factory::getApplication();
		$input = $app->getInput();
		$user  = $this->getCurrentUser();

		$this->defined = $input->getInt('defined', 0);
		$this->source  = $input->getInt('source', 0);
		$this->target  = $input->getInt('target', 0);

		if (!$user->authorise('core.edit', 'com_collector') || !$user->authorise('core.delete', 'com_collector')) {
			$app->enqueueMessage(Text::_('JERROR_ALERTNOAUTHOR'), 'error');
			$app->redirect(Route::_('index.php?option=com_collector&view=listitems&defined=' . $this->defined, false));
		}

		/** @var \Steevo\Component\Collector\Administrator\Model\ListmergeModel $model */
		$model = $this->getModel();

		$this->summary = $model->getSummary($this->defined, $this->source, $this->target);

		if (!$this->summary->list) {
			$app->redirect(Route::_('index.php?option=com_collector&view=lists', false));
		}

		$this->values   = $model->getValues($this->defined);
		$this->usage    = $model->getUsage($this->defined);
		$this->canMerge = $this->summary->source && $this->summary->target && !$this->summary->errors;

		$this->addToolbar();

		parent::display($tpl);
	}

	protected function addToolbar()
	{
		ToolbarHelper::title(Text::sprintf('COM_COLLECTOR_LISTMERGE_TITLE', $this->summary->list->name), 'shuffle');
		ToolbarHelper::link(
			Route::_('index.php?option=com_collector&view=listitems&defined=' . $this->defined),
			'JTOOLBAR_CLOSE',
			'times'
		);
	}
}
