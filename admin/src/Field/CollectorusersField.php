<?php
/**
 * @package    Collector
 * @copyright  Copyright (C) 2010 - 2026 Philippe Ousset. All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace Steevo\Component\Collector\Administrator\Field;

defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\Form\Field\ListField;
use Joomla\Database\DatabaseInterface;

/**
 * Choix de plusieurs utilisateurs du site (comptes actifs), avec recherche : « Nom (identifiant) ».
 * Valeur enregistrée : la liste des identifiants numériques.
 */
class CollectorusersField extends ListField
{
	protected $type = 'Collectorusers';

	protected $layout = 'joomla.form.field.list-fancy-select';

	protected function getOptions()
	{
		$db    = Factory::getContainer()->get(DatabaseInterface::class);
		$users = $db->setQuery(
			$db->getQuery(true)
				->select($db->quoteName(['id', 'name', 'username']))
				->from($db->quoteName('#__users'))
				->where($db->quoteName('block') . ' = 0')
				->order($db->quoteName('name') . ' ASC')
		)->loadObjectList() ?: [];

		$options = parent::getOptions();

		foreach ($users as $user) {
			$options[] = (object) [
				'value' => (string) $user->id,
				'text'  => $user->name . ' (' . $user->username . ')',
			];
		}

		return $options;
	}
}
