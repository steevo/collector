<?php

/**
 * @package     Collector
 *
 * @copyright   Copyright (C) 2010 - 2026 Philippe Ousset. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace Steevo\Component\Collector\Administrator\Helper;

// phpcs:disable PSR1.Files.SideEffects
\defined('_JEXEC') or die;
// phpcs:enable PSR1.Files.SideEffects

use Joomla\CMS\Factory;
use Joomla\CMS\Helper\ContentHelper;
use Joomla\CMS\User\User;

/**
 * Collector helper : droits sur les objets.
 *
 * - modifier directement : core.edit (objet, collection ou Collector), ou core.edit.own sur ses propres objets ;
 * - valider les versions en attente d'une collection : core.edit sur la collection ou sur Collector ;
 * - proposer (site) : action « collector.propose » ; modifications et nouveaux objets enregistrés en attente
 *   de validation, pour qui ne peut pas modifier directement.
 */
class CollectorHelper extends ContentHelper
{
	/**
	 * L'utilisateur peut-il valider les versions de cette collection (droit de modifier) ?
	 */
	public static function canValidate(int $collection, ?User $user = null): bool
	{
		$user = $user ?: Factory::getApplication()->getIdentity();

		return $user->authorise('core.edit', 'com_collector.collection.' . $collection)
			|| $user->authorise('core.edit', 'com_collector');
	}

	/**
	 * L'utilisateur peut-il modifier directement cet objet (sans validation) ?
	 *
	 * @param   int  $createdBy  Auteur de l'objet (pour « Modifier ses propres objets »)
	 */
	public static function canEditDirectly(int $collection, int $itemId, int $createdBy = 0, ?User $user = null): bool
	{
		$user   = $user ?: Factory::getApplication()->getIdentity();
		$assets = ['com_collector.item.' . $itemId, 'com_collector.collection.' . $collection, 'com_collector'];

		foreach ($assets as $asset) {
			if ($user->authorise('core.edit', $asset)) {
				return true;
			}
		}

		if (!$user->guest && $createdBy && $createdBy === (int) $user->id) {
			foreach ($assets as $asset) {
				if ($user->authorise('core.edit.own', $asset)) {
					return true;
				}
			}
		}

		return false;
	}

	/**
	 * L'utilisateur peut-il proposer une modification de cet objet, ou un nouvel objet ($itemId = 0),
	 * dans cette collection ? (droit « Proposer une modification »)
	 */
	public static function canPropose(int $collection, int $itemId = 0, ?User $user = null): bool
	{
		$user = $user ?: Factory::getApplication()->getIdentity();

		if ($user->guest) {
			return false;
		}

		if ($itemId && $user->authorise('collector.propose', 'com_collector.item.' . $itemId)) {
			return true;
		}

		return $user->authorise('collector.propose', 'com_collector.collection.' . $collection)
			|| $user->authorise('collector.propose', 'com_collector');
	}
}
