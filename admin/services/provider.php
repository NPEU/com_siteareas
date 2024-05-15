<?php
/**
 * @package     Joomla.Administrator
 * @subpackage  com_siteareas
 *
 * @copyright   Copyright (C) NPEU 2023.
 * @license     MIT License; see LICENSE.md
 */

defined('_JEXEC') or die;

#use NPEU\Component\Siteareas\Administrator\Helper\AssociationsHelper;
use Joomla\CMS\Component\Router\RouterFactoryInterface;
use Joomla\CMS\Dispatcher\ComponentDispatcherFactoryInterface;
use Joomla\CMS\Extension\ComponentInterface;
use Joomla\CMS\Extension\MVCComponent;
use Joomla\CMS\Extension\Service\Provider\ComponentDispatcherFactory;
use Joomla\CMS\Extension\Service\Provider\MVCFactory;
use Joomla\CMS\Extension\Service\Provider\RouterFactory;
use Joomla\CMS\HTML\Registry;
use Joomla\CMS\MVC\Factory\MVCFactoryInterface;
use Joomla\Database\DatabaseInterface;
use Joomla\DI\Container;
use Joomla\DI\ServiceProviderInterface;
use NPEU\Component\Siteareas\Administrator\Extension\SiteareasComponent;

use NPEU\Component\Siteareas\Administrator\Service\Provider;

return new class implements ServiceProviderInterface {

    public function register(Container $container): void {

        $container->registerServiceProvider(new MVCFactory('\\NPEU\\Component\\Siteareas'));
        $container->registerServiceProvider(new ComponentDispatcherFactory('\\NPEU\\Component\\Siteareas'));
        $container->registerServiceProvider(new RouterFactory('\\NPEU\\Component\\Siteareas'));
        // Why doesn't the following work in conjunction with the 'use' above? I get 'class not found' but I can't tell the difference.
    /**$container->registerServiceProvider(new RouterFactoryProvider('\\NPEU\\Component\\Siteareas'));*/
    /**/$container->registerServiceProvider(new \NPEU\Component\Siteareas\Administrator\Service\Provider\RouterFactoryProvider('\\NPEU\\Component\\Siteareas'));/**/

        $container->set(
            ComponentInterface::class,
            function (Container $container) {
                // Use the following instead if an Extension class isn't needed (i.e. it won't use
                // anything that class implements, like routing)
                #$component = new MVCComponent($container->get(ComponentDispatcherFactoryInterface::class));
                $component = new SiteareasComponent($container->get(ComponentDispatcherFactoryInterface::class));
                $component->setMVCFactory($container->get(MVCFactoryInterface::class));
                $component->setRegistry($container->get(Registry::class));
                $component->setRouterFactory($container->get(RouterFactoryInterface::class));
                $component->setDatabase($container->get(DatabaseInterface::class));


                return $component;
            }
        );
    }
};