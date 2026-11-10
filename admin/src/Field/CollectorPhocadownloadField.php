<?php
/**
 * Joomla! component Collector
 *
 * @package     Collector
 * @copyright   Copyright (C) 2010 - 2026 Philippe Ousset. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace Steevo\Component\Collector\Administrator\Field;

use Joomla\CMS\Factory;
use Joomla\CMS\Form\Field\GroupedlistField;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Language\Text;
use Joomla\Database\DatabaseInterface;

defined('_JEXEC') or die;

/**
 * Choix d'un fichier de Phoca Download, regroupés par catégorie, avec une recherche.
 */
class CollectorPhocadownloadField extends GroupedlistField
{
	protected $type = 'CollectorPhocadownload';

	protected $layout = 'joomla.form.field.groupedlist-fancy-select';

	protected function getInput()
	{
		$db = Factory::getContainer()->get(DatabaseInterface::class);

		$enabled = $db->setQuery(
			$db->getQuery(true)
				->select('enabled')
				->from($db->quoteName('#__extensions'))
				->where($db->quoteName('type') . ' = ' . $db->quote('component'))
				->where($db->quoteName('element') . ' = ' . $db->quote('com_phocadownload'))
		)->loadResult();

		if (!$enabled) {
			return '<div class="alert alert-warning mb-0">' . Text::_('COM_COLLECTOR_PHOCADOWNLOAD_NOT_INSTALLED') . '</div>';
		}

		return parent::getInput();
	}

	protected function getGroups()
	{
		$db    = Factory::getContainer()->get(DatabaseInterface::class);
		$query = $db->getQuery(true)
			->select('f.id, f.title, f.filename, f.published, c.title AS category')
			->from($db->quoteName('#__phocadownload', 'f'))
			->join('LEFT', $db->quoteName('#__phocadownload_categories', 'c') . ' ON c.id = f.catid')
			->order('c.ordering, c.title, f.ordering, f.title');

		// clé numérique : option hors groupe
		$groups = [[HTMLHelper::_('select.option', '', Text::_('COM_COLLECTOR_PHOCADOWNLOAD_SELECT'))]];

		foreach ($db->setQuery($query)->loadObjectList() as $file) {
			$text = $file->title . ' — ' . basename($file->filename)
				. ($file->published == 1 ? '' : ' ' . Text::_('COM_COLLECTOR_PHOCADOWNLOAD_UNPUBLISHED'));

			$groups[$file->category ?: '-'][] = HTMLHelper::_('select.option', (string) $file->id, $text);
		}

		return array_merge(parent::getGroups(), $groups);
	}
}
