<?php
/**
 * @package     Collector
 * @copyright   Copyright (C) 2010 - 2026 Philippe Ousset. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace Steevo\Component\Collector\Site\Service;

\defined('_JEXEC') or die;

use Joomla\CMS\Component\Router\Rules\MenuRules;

/**
 * Règle de menu de Collector : comme celle de Joomla, sauf pour la fiche d'un objet ouverte depuis
 * une liste de membre (« Mes bulletins »…). Joomla rattacherait la fiche au lien de menu de la
 * collection générale ; on garde le lien de menu de la liste, pour que la fiche conserve sa
 * navigation (menu actif, fil d'Ariane).
 */
class CollectorMenuRules extends MenuRules
{
    public function preprocess(&$query)
    {
        if ($this->isUserlistItem($query)) {
            return;
        }

        parent::preprocess($query);
    }

    /**
     * Fiche d'un objet demandée avec le lien de menu d'une liste de membre de la même collection
     */
    public function isUserlistItem(array $query): bool
    {
        if (($query['view'] ?? '') !== 'item' || empty($query['Itemid']) || empty($query['collection'])) {
            return false;
        }

        $menuItem = $this->router->menu->getItem((int) $query['Itemid']);

        return $menuItem
            && ($menuItem->query['option'] ?? '') === 'com_collector'
            && ($menuItem->query['view'] ?? '') === 'usercollection'
            && (int) ($menuItem->query['collection'] ?? 0) === (int) $query['collection'];
    }
}
