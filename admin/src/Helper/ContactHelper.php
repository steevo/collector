<?php
namespace Steevo\Component\Collector\Administrator\Helper;

defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\Plugin\PluginHelper;

class ContactHelper
{
    /**
     * Retourne les infos de contact pour un utilisateur
     * en interrogeant les plugins collector.contact actifs.
     *
     * @return array|null  ['url' => '...', 'label' => '...', 'icon' => '...'] ou null
     */
    public static function getAllContactInfo(int $userId, ?string $subject = null): ?array
	{
		// Charger les plugins du groupe collector
		PluginHelper::importPlugin('collector');

		$app    = Factory::getApplication();
		$urls   = $app->triggerEvent('onCollectorGetContactUrl',   [$userId, $subject]);
		$labels = $app->triggerEvent('onCollectorGetContactLabel', []);
		$icons  = $app->triggerEvent('onCollectorGetContactIcon',  []);

		$contacts = [];
		foreach ($urls as $i => $url) {
			if (!empty($url)) {
				$contacts[] = [
					'url'   => $url,
					'label' => $labels[$i] ?? 'Contacter',
					'icon'  => $icons[$i]  ?? 'icon-envelope',
				];
			}
		}
		return $contacts;
	}
}