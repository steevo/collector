<?php
/**
 * Joomla! 3.0 component Collector
 *
 * @package 	Collector
 * @copyright   Copyright (C) 2010 - 2020 Philippe Ousset. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 *
 * Collector is a Multi Purpose Listing Tool.
 * Originaly developped to list Collections
 * it can be used for several purpose.
 */

namespace Steevo\Component\Collector\Administrator\View\Copycollection;

defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\Helper\ContentHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\View\GenericDataException;
use Joomla\CMS\MVC\View\HtmlView as BaseHtmlView;
use Joomla\CMS\Toolbar\Toolbar;
use Joomla\CMS\Toolbar\ToolbarHelper;

/**
 * HTML Update View class for the Collector component
 *
 * @package	Collector
 */
class HtmlView extends BaseHtmlView
{
	/** @var int Collection source */
	public $collection = 0;

	/** @var int Mode de copie (1 = champs, 2 = champs et objets sans historique, 3 = avec historique) */
	public $copy_mode = 0;

	/** @var int Niveau d'accès de la copie (0 = inchangé) */
	public $assetgroup_id = 0;

	/**
	 * Display function
	 */
	function display($tpl = null)
	{
		// Initialise variables.
		$app = Factory::getApplication();

		$input = $app->getInput();

		$this->collection    = $input->getInt('id', 0);
		$this->copy_mode     = $input->getInt('copy_mode', 0);
		$this->assetgroup_id = $input->getInt('assetgroup_id', 0);

		// Set the toolbar
		$this->addToolBar();
 
		// Display the template
		parent::display($tpl);
	}
 
	/**
	 * Setting the toolbar
	 */
	protected function addToolBar() 
	{
		Factory::getApplication()->getInput()->set('hidemainmenu', true);

		ToolBarHelper::title(Text::_('COM_COLLECTOR_BATCH_COLLECTION'));

		ToolBarHelper::custom('collections.back', 'arrow-left-2', '', 'JTOOLBAR_BACK', false);
	}
}
?>