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
use Joomla\CMS\Form\Field\ListField;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Language\Text;
use Joomla\Database\DatabaseInterface;

defined('_JEXEC') or die;

/**
 * Choix d'un livre de la liseuse (com_flipbook), avec une recherche.
 */
class CollectorFlipbookField extends ListField
{
	protected $type = 'CollectorFlipbook';

	protected $layout = 'joomla.form.field.list-fancy-select';

	protected function getInput()
	{
		$db = Factory::getContainer()->get(DatabaseInterface::class);

		$enabled = $db->setQuery(
			$db->getQuery(true)
				->select('enabled')
				->from($db->quoteName('#__extensions'))
				->where($db->quoteName('type') . ' = ' . $db->quote('component'))
				->where($db->quoteName('element') . ' = ' . $db->quote('com_flipbook'))
		)->loadResult();

		if (!$enabled) {
			return '<div class="alert alert-warning mb-0">' . Text::_('COM_COLLECTOR_FLIPBOOK_NOT_INSTALLED') . '</div>';
		}

		return parent::getInput();
	}

	protected function getOptions()
	{
		$db      = Factory::getContainer()->get(DatabaseInterface::class);
		$options = [HTMLHelper::_('select.option', '', Text::_('COM_COLLECTOR_FLIPBOOK_SELECT'))];
		$query   = $db->getQuery(true)
			->select('id, title, folder, page_count, state')
			->from($db->quoteName('#__flipbook_books'))
			->where('state IN (0, 1)')
			->order('ordering, title');

		foreach ($db->setQuery($query)->loadObjectList() as $book) {
			$text = $book->title . ' — ' . $book->folder . ' (' . Text::plural('COM_COLLECTOR_FLIPBOOK_N_PAGES', (int) $book->page_count) . ')'
				. ($book->state == 1 ? '' : ' ' . Text::_('COM_COLLECTOR_FLIPBOOK_UNPUBLISHED'));

			$options[] = HTMLHelper::_('select.option', (string) $book->id, $text);
		}

		return array_merge(parent::getOptions(), $options);
	}
}
