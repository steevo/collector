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
 * Tri suivant d'un champ : quand la liste est triée sur ce champ, les objets de même valeur
 * sont ensuite triés sur ces champs, dans l'ordre choisi.
 */
class NextsortedfieldField extends OrderedfieldsField
{
	protected $type = 'NextSortedField';

	protected string $unableKey  = 'COM_COLLECTOR_FIELD_NEXT_FIELD_UNABLE';
	protected string $emptyKey   = 'COM_COLLECTOR_NEXT_SORT_EMPTY';
	protected string $previewKey = 'COM_COLLECTOR_NEXT_SORT_PREVIEW';
	protected string $joinKey    = 'COM_COLLECTOR_NEXT_SORT_JOIN';

	protected function getCandidates(): array
	{
		$input      = Factory::getApplication()->getInput();
		$collection = (int) ($this->form ? $this->form->getValue('collection') : 0) ?: $input->getInt('collection', 0);
		// Le champ en cours ne peut pas être son propre tri suivant
		$self = (int) ($this->form ? $this->form->getValue('id') : 0) ?: $input->getInt('id', 0);

		return $this->fieldsOfCollection($collection, 'sortable', $self);
	}
}
