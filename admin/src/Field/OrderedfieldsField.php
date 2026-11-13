<?php
/**
 * @package    Collector
 * @copyright  Copyright (C) 2010 - 2026 Philippe Ousset. All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace Steevo\Component\Collector\Administrator\Field;

defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\Form\FormField;
use Joomla\CMS\Language\Text;
use Joomla\Database\DatabaseInterface;

/**
 * Liste ordonnée de champs de la collection (titre personnalisé, tri suivant…).
 *
 * Les champs choisis forment une liste qu'on réordonne (glisser-déposer ou flèches) ;
 * une liste déroulante propose les champs encore libres. La valeur enregistrée est
 * la suite des identifiants séparés par « / », ou 0 si aucun champ n'est choisi.
 */
abstract class OrderedfieldsField extends FormField
{
	/** Texte quand aucun champ de la collection ne convient */
	protected string $unableKey = '';

	/** Texte quand la liste est vide */
	protected string $emptyKey = '';

	/** Aperçu : « … %s » où %s reçoit les noms des champs joints par $joinKey */
	protected string $previewKey = '';

	protected string $joinKey = '';

	/**
	 * Champs de la collection qui peuvent être choisis, dans l'ordre de la collection
	 *
	 * @return  object[]  value (id), text (nom)
	 */
	abstract protected function getCandidates(): array;

	protected function getInput()
	{
		$candidates = [];

		foreach ($this->getCandidates() as $field) {
			$candidates[(int) $field->value] = (string) $field->text;
		}

		$value    = (string) $this->value;
		$selected = $value === '' || $value === '0' ? [] : array_map('intval', explode('/', $value));
		// Champs choisis qui existent encore, dans l'ordre enregistré
		$selected = array_values(array_filter($selected, fn ($id) => isset($candidates[$id])));
		$hidden   = '<input type="hidden" name="' . $this->name . '" id="' . $this->id . '" value="'
			. htmlspecialchars($selected ? implode('/', $selected) : '0', ENT_QUOTES) . '">';

		if (!$candidates) {
			return '<p class="form-control-plaintext text-muted mb-0">' . Text::_($this->unableKey) . '</p>' . $hidden;
		}

		$wa = Factory::getApplication()->getDocument()->getWebAssetManager();
		$wa->getRegistry()->addExtensionRegistryFile('com_collector');
		$wa->useScript('com_collector.ordered-fields');

		$esc  = fn ($s) => htmlspecialchars((string) $s, ENT_QUOTES, 'UTF-8');
		$data = [
			'data-empty="' . $esc(Text::_($this->emptyKey)) . '"',
			'data-preview="' . $esc(Text::_($this->previewKey)) . '"',
			'data-join="' . $esc(Text::_($this->joinKey)) . '"',
			'data-up="' . $esc(Text::_('COM_COLLECTOR_ORDERED_UP')) . '"',
			'data-down="' . $esc(Text::_('COM_COLLECTOR_ORDERED_DOWN')) . '"',
			'data-remove="' . $esc(Text::_('COM_COLLECTOR_ORDERED_REMOVE')) . '"',
			'data-names="' . $esc(json_encode($candidates, JSON_UNESCAPED_UNICODE)) . '"',
		];

		$html   = [];
		$html[] = '<div class="collector-ordered" data-input="' . $esc($this->id) . '" ' . implode(' ', $data) . '>';
		$html[] = $hidden;
		$html[] = '<ol class="list-group mb-2" data-role="list" aria-label="' . $esc(Text::_($this->element['label'] ?? '')) . '"></ol>';
		$html[] = '<div class="input-group">';
		$html[] = '<select class="form-select" data-role="add" aria-label="' . $esc(Text::_('COM_COLLECTOR_ORDERED_ADD')) . '"></select>';
		$html[] = '<button type="button" class="btn btn-secondary" data-role="add-button"><span class="icon-plus" aria-hidden="true"></span> '
			. Text::_('COM_COLLECTOR_ORDERED_ADD_BUTTON') . '</button>';
		$html[] = '</div>';
		$html[] = '<div class="small mt-2" data-role="preview" aria-live="polite"></div>';
		$html[] = '<div class="small text-muted">' . Text::_('COM_COLLECTOR_ORDERED_HELP') . '</div>';
		$html[] = '</div>';

		return implode("\n", $html);
	}

	/**
	 * Champs d'une collection dont le type accepte un usage (colonne du type : intitle, sortable…)
	 *
	 * @return  object[]
	 */
	protected function fieldsOfCollection(int $collection, string $typeFlag, int $exclude = 0): array
	{
		if ($collection <= 0) {
			return [];
		}

		$db    = Factory::getContainer()->get(DatabaseInterface::class);
		$query = $db->getQuery(true)
			->select(['f.id AS value', 'f.field AS text'])
			->from($db->quoteName('#__collector_fields', 'f'))
			->join('LEFT', $db->quoteName('#__collector_fields_type', 't'), 'f.type = t.id')
			->where('f.collection = ' . $collection)
			->where($db->quoteName('t.' . $typeFlag) . ' = 1')
			->order('f.ordering');

		if ($exclude) {
			$query->where('f.id <> ' . $exclude);
		}

		return $db->setQuery($query)->loadObjectList();
	}
}
