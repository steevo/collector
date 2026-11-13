<?php
/**
 * @package    Collector
 * @copyright  Copyright (C) 2010 - 2026 Philippe Ousset. All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace Steevo\Component\Collector\Administrator\Field;

defined('_JEXEC') or die;

use Joomla\CMS\Factory;

/**
 * Titre personnalisé d'une collection : champs dont les valeurs, dans l'ordre choisi,
 * forment le titre des objets. Sans champ choisi, le titre est le champ principal.
 */
class CustomtitleField extends OrderedfieldsField
{
	protected $type = 'CustomTitle';

	protected string $unableKey  = 'COM_COLLECTOR_FIELD_CUSTOM_TITLE_UNABLE';
	protected string $emptyKey   = 'COM_COLLECTOR_CUSTOM_TITLE_EMPTY';
	protected string $previewKey = 'COM_COLLECTOR_CUSTOM_TITLE_PREVIEW';
	protected string $joinKey    = 'COM_COLLECTOR_CUSTOM_TITLE_JOIN';

	protected function getCandidates(): array
	{
		// Formulaire d'une collection : son identifiant est celui de l'enregistrement
		$collection = (int) ($this->form ? $this->form->getValue('id') : 0) ?: Factory::getApplication()->getInput()->getInt('id', 0);

		return $this->fieldsOfCollection($collection, 'intitle');
	}
}
