<?php
namespace Steevo\Component\Collector\Administrator\Plugin;

defined('_JEXEC') or die;

use Joomla\CMS\Plugin\CMSPlugin;

abstract class CollectorContactPlugin extends CMSPlugin
{
    /**
     * Retourne l'URL de contact pour un utilisateur Joomla donné.
     *
     * @param  int  $userId  ID Joomla de l'utilisateur à contacter
     * @return string|null   URL de contact ou null si non disponible
     */
    abstract public function onCollectorGetContactUrl(int $userId, ?string $subject = null): ?string;

    /**
     * Retourne le libellé du bouton de contact.
     */
    public function onCollectorGetContactLabel(): string
    {
        return 'Contacter';
    }

    /**
     * Retourne l'icône Bootstrap/IcoMoon du bouton.
     */
    public function onCollectorGetContactIcon(): string
    {
        return 'icon-envelope';
    }
}