<?php
/**
 * @package    Collector
 * @copyright  Copyright (C) 2010 - 2026 Philippe Ousset. All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace Steevo\Component\Collector\Site\View\Myproposals;

defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\View\HtmlView as BaseHtmlView;
use Joomla\CMS\Router\Route;
use Joomla\CMS\Uri\Uri;

/**
 * « Mes propositions » : suivi par le membre de ses propositions (voir, corriger, retirer)
 */
class HtmlView extends BaseHtmlView
{
	protected $items;
	protected $pagination;
	protected $state;
	protected $params;
	protected $pageclass_sfx = '';

	public function display($tpl = null)
	{
		$app  = Factory::getApplication();
		$user = $app->getIdentity();

		// Visiteur : connexion, puis retour sur cette page
		if ($user->guest) {
			$return = base64_encode(Uri::getInstance()->toString());
			$app->enqueueMessage(Text::_('COM_COLLECTOR_MYPROPOSALS_LOGIN'), 'notice');
			$app->redirect(Route::_('index.php?option=com_users&view=login&return=' . $return, false));

			return;
		}

		$this->items      = $this->get('Items');
		$this->pagination = $this->get('Pagination');
		$this->state      = $this->get('State');
		$this->params     = $this->state->get('params');

		$this->pageclass_sfx = htmlspecialchars((string) $this->params->get('pageclass_sfx', ''));

		$menu = $app->getMenu()->getActive();
		$this->params->def('page_heading', $this->params->get('page_title', $menu->title ?? Text::_('COM_COLLECTOR_MYPROPOSALS_TITLE')));

		if (!$menu || ($menu->query['view'] ?? '') !== 'myproposals') {
			$this->params->set('page_heading', Text::_('COM_COLLECTOR_MYPROPOSALS_TITLE'));
		}

		$this->setDocumentTitle($this->params->get('page_title', '') ?: $this->params->get('page_heading'));

		parent::display($tpl);
	}
}
