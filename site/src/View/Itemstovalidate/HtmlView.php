<?php
/**
 * @package    Collector
 * @copyright  Copyright (C) 2010 - 2020 Philippe Ousset. All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace Steevo\Component\Collector\Site\View\Itemstovalidate;

defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\View\GenericDataException;
use Joomla\CMS\MVC\View\HtmlView as BaseHtmlView;
use Joomla\CMS\Router\Route;

/**
 * Vue frontend : liste des items d'une collection ayant des versions à valider.
 */
class HtmlView extends BaseHtmlView
{
	protected $items;
	protected $pagination;
	protected $collection;
	protected $fields;
	protected $state;

	public function display($tpl = null)
	{
		$app  = Factory::getApplication();
		$user = $app->getIdentity();

		/** @var \Steevo\Component\Collector\Site\Model\ItemstovalidateModel $model */
		$model = $this->getModel();

		$this->collection = $model->getCollection();

		// Accès : l'utilisateur doit pouvoir éditer (validateurs = modérateurs)
		if (!$this->collection
			|| (!$user->authorise('core.edit', 'com_collector.collection.' . $this->collection->id)
			    && !$user->authorise('core.edit.own', 'com_collector.collection.' . $this->collection->id)
			    && !$user->authorise('core.edit', 'com_collector'))
		) {
			$app->enqueueMessage(Text::_('JERROR_ALERTNOAUTHOR'), 'error');
			return;
		}

		$this->items      = $model->getItems();
		$this->pagination = $model->getPagination();
		$this->fields     = $model->getTitleFields();
		$this->state      = $model->getState();

		if (count($errors = $this->get('Errors'))) {
			throw new GenericDataException(implode("\n", $errors), 500);
		}

		$this->_prepareDocument();
		parent::display($tpl);
	}

	protected function _prepareDocument()
	{
		$app = Factory::getApplication();
		$app->getDocument()->setTitle(
			$this->collection
				? Text::sprintf('COM_COLLECTOR_ITEMS_TO_VALIDATE_TITLE', $this->collection->name)
				: Text::_('COM_COLLECTOR_ITEMS_TO_VALIDATE')
		);
	}
}