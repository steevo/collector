<?php

/**
 * @package     Joomla.Administrator
 * @subpackage  com_collector
 *
 * @copyright   Copyright (C) 2005 - 2019 Open Source Matters, Inc. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

// phpcs:disable PSR1.Files.SideEffects
\defined('_JEXEC') or die;
// phpcs:enable PSR1.Files.SideEffects
use Joomla\CMS\Association\AssociationExtensionInterface;
use Joomla\CMS\Categories\CategoryFactoryInterface;
use Joomla\CMS\Component\Router\RouterFactoryInterface;
use Joomla\CMS\Dispatcher\ComponentDispatcherFactoryInterface;
use Joomla\CMS\Extension\ComponentInterface;
use Joomla\CMS\Extension\Service\Provider\CategoryFactory;
use Joomla\CMS\Extension\Service\Provider\ComponentDispatcherFactory;
use Joomla\CMS\Extension\Service\Provider\MVCFactory;
use Joomla\CMS\Extension\Service\Provider\RouterFactory;
use Joomla\CMS\HTML\Registry;
use Joomla\CMS\MVC\Factory\MVCFactoryInterface;
use Steevo\Component\Collector\Administrator\Extension\CollectorComponent;
//use Joomla\Component\Weblinks\Administrator\Helper\AssociationsHelper;
use Joomla\DI\Container;
use Joomla\DI\ServiceProviderInterface;

/**
 * The collector service provider.
 *
 * @since  4.0.0
 */
return new class () implements ServiceProviderInterface {
    /**
     * Registers the service provider with a DI container.
     *
     * @param   Container  $container  The DI container.
     *
     * @return  void
     *
     * @since   4.0.0
     */
    public function register(Container $container)
    {
        // $container->set(AssociationExtensionInterface::class, new AssociationsHelper());
        $componentNamespace = '\\Steevo\\Component\\Collector';
        $container->registerServiceProvider(new CategoryFactory($componentNamespace));
        $container->registerServiceProvider(new MVCFactory($componentNamespace));
        $container->registerServiceProvider(new ComponentDispatcherFactory($componentNamespace));
        $container->registerServiceProvider(new RouterFactory($componentNamespace));
        $container->set(ComponentInterface::class, function (Container $container) {
            $component = new CollectorComponent($container->get(ComponentDispatcherFactoryInterface::class));
            $component->setRegistry($container->get(Registry::class));
            $component->setMVCFactory($container->get(MVCFactoryInterface::class));
            $component->setCategoryFactory($container->get(CategoryFactoryInterface::class));
            // $component->setAssociationExtension($container->get(AssociationExtensionInterface::class));
            $component->setRouterFactory($container->get(RouterFactoryInterface::class));
            return $component;
        });
    }
};
