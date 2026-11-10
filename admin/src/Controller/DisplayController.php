<?php
/**
 * @package     Joomla.Administrator
 * @subpackage  Collector
 *
 * @copyright   Copyright (C) 2005 - 2017 Open Source Matters, Inc. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace Steevo\Component\Collector\Administrator\Controller;

defined('_JEXEC') or die;

use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\Controller\BaseController;
use Joomla\CMS\Router\Route;

/**
 * Weblinks Main Controller
 *
 * @since  1.5
 */
class DisplayController extends BaseController
{
	/**
	 * The default view.
	 *
	 * @var    string
	 * @since  1.6
	 */
	protected $default_view = 'collector';

	/**
	 * Method to display a view.
	 *
	 * @param   boolean  $cacheable  If true, the view output will be cached
	 * @param   array    $urlparams  An array of safe url parameters and their variable types,
	 *                               for valid values see {@link JFilterInput::clean()}.
	 *
	 * @return  BaseController|boolean  This object to support chaining.
	 *
	 * @since   1.5
	 */
	public function display($cacheable = false, $urlparams = false)
	{
		$view   = $this->input->get('view', 'collector');
		$layout = $this->input->get('layout', 'default');
		$id     = $this->input->getInt('id');

		// L'ancien gestionnaire de fichiers (Joomla 3) est remplacé par l'onglet « Vérifier les fichiers » des outils
		if ($view === 'filemanager')
		{
			$this->setRedirect(Route::_('index.php?option=com_collector&view=tools&active_tab=%23tab-filecheck', false));

			return $this;
		}

		// Check for edit form.
		if ($view == 'collection' && $layout == 'edit' && !$this->checkEditId('com_collector.edit.collection', $id))
		{
			// Somehow the person just went to the form - we don't allow that.
			if (!\count($this->app->getMessageQueue()))
			{
				$this->setMessage(Text::sprintf('JLIB_APPLICATION_ERROR_UNHELD_ID', $id), 'error');
			}

			$this->setRedirect(Route::_('index.php?option=com_collector&view=collector', false));

			return false;
		}

		return parent::display();
	}
}
