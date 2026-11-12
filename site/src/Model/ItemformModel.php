<?php
/**
 * @package    Collector
 * @copyright  Copyright (C) 2010 - 2026 Philippe Ousset. All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace Steevo\Component\Collector\Site\Model;

defined('_JEXEC') or die;

/**
 * Proposition d'un nouvel objet depuis le site.
 *
 * Reprend le modèle de la fiche (collection, champs, formulaire) pour un objet vierge ;
 * l'enregistrement passe par la tâche item.save, comme la modification d'un objet.
 */
class ItemformModel extends ItemModel
{
	protected function populateState()
	{
		parent::populateState();

		// Toujours un nouvel objet : la modification d'un objet existant se fait sur sa fiche
		$this->setState('item.id', 0);
	}
}
