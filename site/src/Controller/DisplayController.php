<?php

/**
 * @package     Joomla.Site
 * @subpackage  Collector
 *
 * @copyright   Copyright (C) 2005 - 2017 Open Source Matters, Inc. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace Steevo\Component\Collector\Site\Controller;

use Joomla\CMS\Application\CMSApplication;
use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\Controller\BaseController;
use Joomla\CMS\MVC\Factory\MVCFactoryInterface;

// phpcs:disable PSR1.Files.SideEffects
\defined('_JEXEC') or die;
// phpcs:enable PSR1.Files.SideEffects

/**
 * Content Component Controller
 *
 * @since  1.5
 */
class DisplayController extends BaseController
{
    /**
     * Method to display a view.
     *
     * @param   boolean  $cachable   If true, the view output will be cached.
     * @param   boolean  $urlparams  An array of safe URL parameters and their variable types, for valid values see {@link JFilterInput::clean()}.
     *
     * @return  DisplayController  This object to support chaining.
     *
     * @since   1.5
     */
    public function display($cachable = false, $urlparams = false)
    {
        $cachable = true;

        /**
         * Set the default view name and format from the Request.
         * Note we are using a_id to avoid collisions with the router and the return page.
         * Frontend is a bit messier than the backend.
         */
        $id    = $this->input->getInt('id');
        $vName = $this->input->getCmd('view', 'collections');
        $this->input->set('view', $vName);

		if (in_array($vName, ['itemversions'])) {
			$cachable = false;
		}

        $user = $this->app->getIdentity();

        // Check for edit form.
        if ($vName === 'form' && !$this->checkEditId('com_collector.edit.item', $id)) {
            // Somehow the person just went to the form - we don't allow that.
            throw new \Exception(Text::sprintf('JLIB_APPLICATION_ERROR_UNHELD_ID', $id), 403);
        }

        // Couleurs de Collector adaptées au thème du site (clair / sombre)
        $document = $this->app->getDocument();

        if ($document->getType() === 'html') {
            $document->getWebAssetManager()->useStyle('com_collector.theme');
        }

        parent::display($cachable, $urlparams);

        return $this;
    }
}
