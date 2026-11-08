<?php
/**
 * Joomla! component Collector
 *
 * @package     Collector
 * @copyright   Copyright (C) 2010 - 2026 Philippe Ousset. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace Steevo\Component\Collector\Administrator\Table;

\defined('_JEXEC') or die;

use Joomla\CMS\Application\ApplicationHelper;
use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Table\Table;
use Joomla\Database\DatabaseDriver;

/**
 * Template d'affichage d'une collection (vue liste ou vue détaillée).
 */
class CollectorTemplatesTable extends Table
{
    public function __construct(DatabaseDriver $db)
    {
        parent::__construct('#__collector_templates', 'id', $db);

        // comme les autres tables de Collector, l'état de publication est la colonne `state`
        $this->setColumnAlias('published', 'state');
    }

    public function check(): bool
    {
        if (trim((string) $this->name) === '') {
            $this->setError(Text::_('COM_COLLECTOR_TEMPLATE_ERROR_NAME'));

            return false;
        }

        if ((int) $this->collection <= 0) {
            $this->setError(Text::_('COM_COLLECTOR_TEMPLATE_ERROR_COLLECTION'));

            return false;
        }

        $this->client = (int) $this->client === 1 ? 1 : 0;
        $this->alias  = ApplicationHelper::stringURLSafe($this->alias ?: $this->name) ?: 'template';
        $this->content = (string) $this->content;
        $this->css     = (string) $this->css;

        return true;
    }

    public function store($updateNulls = true): bool
    {
        $date = Factory::getDate()->toSql();
        $user = Factory::getApplication()->getIdentity();

        if ($this->id) {
            $this->modified    = $date;
            $this->modified_by = (int) $user->id;
        } else {
            $this->created    = $date;
            $this->created_by = (int) $user->id;
        }

        return parent::store($updateNulls);
    }
}
