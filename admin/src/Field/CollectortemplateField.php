<?php
/**
 * Joomla! component Collector
 *
 * @package     Collector
 * @copyright   Copyright (C) 2010 - 2026 Philippe Ousset. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace Steevo\Component\Collector\Administrator\Field;

\defined('_JEXEC') or die;

use Joomla\CMS\Form\Field\ListField;
use Joomla\CMS\Language\Text;
use Joomla\Database\ParameterType;

/**
 * Choix du template d'affichage d'une collection, pour une vue (attribut client : 1 = liste, 0 = fiche).
 */
class CollectortemplateField extends ListField
{
    protected $type = 'Collectortemplate';

    protected function getOptions()
    {
        $options    = parent::getOptions();
        $collection = (int) $this->form->getValue('id');
        $client     = (int) $this->element['client'];

        if ($collection <= 0) {
            return $options;
        }

        $db    = $this->getDatabase();
        $query = $db->getQuery(true)
            ->select('id AS value, name AS text, state')
            ->from($db->quoteName('#__collector_templates'))
            ->where('collection = :collection')
            ->where('client = :client')
            ->order('name')
            ->bind(':collection', $collection, ParameterType::INTEGER)
            ->bind(':client', $client, ParameterType::INTEGER);

        foreach ($db->setQuery($query)->loadObjectList() as $template) {
            if ((int) $template->state !== 1) {
                $template->text .= ' (' . Text::_('JUNPUBLISHED') . ')';
            }

            unset($template->state);
            $options[] = $template;
        }

        return $options;
    }
}
