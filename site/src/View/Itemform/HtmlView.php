<?php
/**
 * @package    Collector
 * @copyright  Copyright (C) 2010 - 2026 Philippe Ousset. All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace Steevo\Component\Collector\Site\View\Itemform;

defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\View\HtmlView as BaseHtmlView;
use Joomla\CMS\Router\Route;
use Joomla\CMS\Uri\Uri;

/**
 * Vue « Proposition d'objet » : formulaire de création d'un objet dans une collection.
 */
class HtmlView extends BaseHtmlView
{
	protected $collection;
	protected $fields;
	protected $form;
	protected $params;
	protected $user;

	/**
	 * L'utilisateur peut choisir l'état de publication (droit core.edit.state)
	 *
	 * @var  bool
	 */
	protected $canChangeState = false;

	protected $pageclass_sfx = '';

	public function display($tpl = null)
	{
		$app        = Factory::getApplication();
		$this->user = $app->getIdentity();

		/** @var \Steevo\Component\Collector\Site\Model\ItemformModel $model */
		$model            = $this->getModel();
		$this->params     = $model->getState('params');
		$this->collection = $model->getCollection();

		if (!$this->collection || empty($this->collection->id)) {
			throw new \Exception(Text::sprintf('COM_COLLECTOR_COLLECTION_NOT_FOUND', $app->getInput()->getInt('collection')), 404);
		}

		// Droit de créer un objet dans la collection (à défaut, celui du composant)
		$canCreate = $this->user->authorise('core.create', 'com_collector.collection.' . (int) $this->collection->id);

		if ($canCreate === null) {
			$canCreate = $this->user->authorise('core.create', 'com_collector');
		}

		if (!$canCreate) {
			if ($this->user->guest) {
				// Visiteur : connexion, puis retour sur ce formulaire
				$return = base64_encode(Uri::getInstance()->toString());
				$app->enqueueMessage(Text::_('COM_COLLECTOR_SUBMIT_LOGIN'), 'notice');
				$app->redirect(Route::_('index.php?option=com_users&view=login&return=' . $return, false));

				return;
			}

			throw new \Exception(Text::_('JERROR_ALERTNOAUTHOR'), 403);
		}

		$this->fields = $model->getFields() ?: [];
		$this->form   = $model->getForm();

		if (!$this->form) {
			throw new \Exception(Text::_('JERROR_AN_ERROR_HAS_OCCURRED'), 500);
		}

		$this->canChangeState = (bool) ($this->user->authorise('core.edit.state', 'com_collector.collection.' . (int) $this->collection->id)
			?? $this->user->authorise('core.edit.state', 'com_collector'));

		$this->pageclass_sfx = htmlspecialchars($this->params->get('pageclass_sfx', ''));

		$this->prepareDocument();

		parent::display($tpl);
	}

	protected function prepareDocument()
	{
		$app  = Factory::getApplication();
		$menu = $app->getMenu()->getActive();

		$default = Text::_('COM_COLLECTOR_SUBMIT_AN_ITEM');

		if ($menu && ($menu->query['option'] ?? '') === 'com_collector' && ($menu->query['view'] ?? '') === 'itemform') {
			// Lien de menu « Proposition d'objet » : ses titres
			$this->params->def('page_heading', $this->params->get('page_title', $menu->title));
			$title = $this->params->get('page_title', '') ?: $menu->title;
		} else {
			// Autre lien de menu actif (accueil…) : ses titres ne concernent pas ce formulaire
			$this->params->set('page_heading', $default);
			$this->params->set('show_page_heading', 1);
			$title = $default;
			$app->getPathway()->addItem($default, '');
		}

		$this->setDocumentTitle($title);

		// Pas d'indexation d'un formulaire
		$this->getDocument()->setMetaData('robots', 'noindex, nofollow');
	}
}
