<?php
/**
 * @package    Collector
 * @copyright  Copyright (C) 2010 - 2026 Philippe Ousset. All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace Steevo\Component\Collector\Administrator\Helper;

defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\Plugin\PluginHelper;

/**
 * Moyens de contacter un membre (mise en relation), fournis par les plugins du groupe « collector »
 * (courriel, messagerie du forum…).
 */
class ContactHelper
{
    /**
     * Contacts proposés pour un membre, un par plugin qui sait le joindre.
     *
     * Chaque plugin est interrogé séparément : son adresse, son libellé et son icône restent ensemble,
     * même quand un autre plugin ne sait pas joindre ce membre (avec un seul appel d'événement par
     * information, les réponses vides décalaient libellés et icônes d'un plugin à l'autre).
     *
     * @return  array<int, array{url: string, label: string, icon: string}>
     */
    public static function getAllContactInfo(int $userId, ?string $subject = null): array
    {
        $app      = Factory::getApplication();
        $contacts = [];

        foreach (PluginHelper::getPlugin('collector') as $plugin) {
            $instance = $app->bootPlugin($plugin->name, 'collector');

            if (!$instance || !method_exists($instance, 'onCollectorGetContactUrl')) {
                continue;
            }

            $url = $instance->onCollectorGetContactUrl($userId, $subject);

            if (empty($url)) {
                continue;
            }

            $contacts[] = [
                'url'   => (string) $url,
                'label' => method_exists($instance, 'onCollectorGetContactLabel') ? (string) $instance->onCollectorGetContactLabel() : 'Contacter',
                'icon'  => method_exists($instance, 'onCollectorGetContactIcon') ? (string) $instance->onCollectorGetContactIcon() : 'icon-envelope',
            ];
        }

        return $contacts;
    }
}
