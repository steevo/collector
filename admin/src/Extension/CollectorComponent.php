<?php
/**
 * @package     Joomla.Administrator
 * @subpackage  com_weblinks
 *
 * @copyright   Copyright (C) 2005 - 2019 Open Source Matters, Inc. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace Steevo\Component\Collector\Administrator\Extension;

defined('_JEXEC') or die;


use Steevo\Component\Collector\Administrator\Service\Html\Collector;
use Joomla\CMS\Application\SiteApplication;
// use Joomla\CMS\Association\AssociationServiceInterface;
// use Joomla\CMS\Association\AssociationServiceTrait;
use Joomla\CMS\Categories\CategoryServiceInterface;
use Joomla\CMS\Categories\CategoryServiceTrait;
use Joomla\CMS\Component\Router\RouterServiceInterface;
use Joomla\CMS\Component\Router\RouterServiceTrait;
use Joomla\CMS\Extension\BootableExtensionInterface;
use Joomla\CMS\Extension\MVCComponent;
use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\Factory;
use Joomla\CMS\Uri\Uri;
use Joomla\CMS\Fields\FieldsServiceInterface;
use Joomla\CMS\HTML\HTMLRegistryAwareTrait;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Tag\TagServiceInterface;
use Joomla\CMS\Tag\TagServiceTrait;
// use Joomla\Component\Weblinks\Administrator\Service\HTML\AdministratorService;
// use Joomla\Component\Weblinks\Administrator\Service\HTML\Icon;
use Psr\Container\ContainerInterface;
use Steevo\Component\Collector\Administrator\Helper\CollectorGrid;

/**
 * Component class for com_collector
 *
 * @since  4.0.0
 */
class CollectorComponent extends MVCComponent implements
	CategoryServiceInterface,
	// AssociationServiceInterface,
	TagServiceInterface,
	RouterServiceInterface,
	BootableExtensionInterface,
	FieldsServiceInterface
{
	use CategoryServiceTrait;
	// use AssociationServiceTrait;
	use HTMLRegistryAwareTrait;
	use RouterServiceTrait;
	use CategoryServiceTrait, TagServiceTrait
	{
		CategoryServiceTrait::getTableNameForSection insteadof TagServiceTrait;
		CategoryServiceTrait::getStateColumnForSection insteadof TagServiceTrait;
	}

	/**
	 * The container we were created with
	 *
	 * @var   Container
	 * @since 7.2.0
	 */
	private $container;

	/**
	 * Booting the extension. This is the function to set up the environment of the extension like
	 * registering new class loaders, etc.
	 *
	 * If required, some initial set up can be done from services of the container, eg.
	 * registering HTML services.
	 *
	 * @param   ContainerInterface  $container  The container
	 *
	 * @return  void
	 *
	 * @since   4.0.0
	 */
	public function boot(ContainerInterface $container)
	{
		$this->container = $container;

		// Dossier racine des fichiers de Collector (défini en Joomla 3 dans admin/collector.php,
		// toujours utilisé par le gestionnaire de fichiers, l'import et les sélecteurs de dossier)
		if (!\defined('COM_COLLECTOR_BASE'))
		{
			$filePath = trim((string) ComponentHelper::getParams('com_collector')->get('file_path', 'images/collector'), '/\\');
			\define('COM_COLLECTOR_BASE', JPATH_ROOT . '/' . $filePath);
			\define('COM_COLLECTOR_BASEURL', Uri::root() . $filePath);
		}

		// $this->getRegistry()->register('collectoradministrator', new AdministratorService);
		// $this->getRegistry()->register('collectoricon', new Icon($container->get(SiteApplication::class)));
		$this->getRegistry()->register('collector', new Collector());
		$this->getRegistry()->register('collectorgrid', CollectorGrid::class);
	}

	/**
	 * Returns a valid section for the given section. If it is not valid then null
	 * is returned.
	 *
	 * @param   string  $section  The section to get the mapping for
	 * @param   object  $item     The item
	 *
	 * @return  string|null  The new section
	 *
	 * @since   4.0.0
	 */
	public function validateSection($section, $item = null)
	{
		if ($section != 'collector')
		{
			// We don't know other sections
			return null;
		}

		return $section;
	}

	/**
	 * Returns valid contexts
	 *
	 * @return  array
	 *
	 * @since   4.0.0
	 */
	public function getContexts(): array
	{
		Factory::getApplication()->getLanguage()->load('com_collector', JPATH_ADMINISTRATOR);

		$contexts = array(
			'com_collector.collection'    => Text::_('COM_COLLECTOR'),
		);

		return $contexts;
	}


	/**
	 * Returns the table for the count items functions for the given section.
	 *
	 * @param   string  $section  The section
	 *
	 * @return  string|null
	 *
	 * @since   4.0.0
	 */
	protected function getTableNameForSection(?string $section = null)
	{
		return ($section === 'category' ? 'categories' : 'collector');
	}

	/**
	 * Returns the state column for the count items functions for the given section.
	 *
	 * @param   string  $section  The section
	 *
	 * @return  string|null
	 *
	 * @since   4.0.0
	 */
	protected function getStateColumnForSection(?string $section = null)
	{
		return 'state';
	}
}
