<?php
/**
 * @package     Organizer
 * @extension   com_organizer
 * @author      James Antrim, <james.antrim@nm.thm.de>
 * @copyright   2023 TH Mittelhessen
 * @license     GNU GPL v.3
 * @link        www.thm.de
 */

require_once 'autoloader.php';

//use Joomla\CMS\Categories\CategoryFactoryInterface;
//use Joomla\CMS\Component\Router\RouterFactoryInterface;
use Joomla\CMS\Dispatcher\ComponentDispatcherFactoryInterface;
use Joomla\CMS\Extension\ComponentInterface;
use Joomla\CMS\Extension\Service;

// Adds default Joomla default implementations, not to be confused with Joomla\Registry\Registry
use Joomla\CMS\HTML\Registry;
use Joomla\CMS\MVC\Factory\MVCFactoryInterface;
use Joomla\DI\Container;
use Joomla\DI\ServiceProviderInterface;
use THM\Organizer\Component;
use THM\Organizer\Providers;

/**
 * The service provider.
 */
return new class implements ServiceProviderInterface {
    /**
     * Registers the service provider with a DI container.
     *
     * @param Container $container The DI container.
     *
     * @return  void
     */
    public function register(Container $container): void
    {
        $container->registerServiceProvider(new Providers\Dispatcher());
        $container->registerServiceProvider(new Providers\MVC());
        //TODO these items!!
        //$container->registerServiceProvider(new Service\Provider\CategoryFactory('\\THM\\Groups'));
        //$container->registerServiceProvider(new Service\Provider\RouterFactory('\\THM\\Groups'));
        $container->set(
            ComponentInterface::class,
            function (Container $container) {
                $component = new Component($container->get(ComponentDispatcherFactoryInterface::class));
                $component->setMVCFactory($container->get(MVCFactoryInterface::class));
                //$component->setCategoryFactory($container->get(CategoryFactoryInterface::class));
                //$component->setRouterFactory($container->get(RouterFactoryInterface::class));
                $component->setRegistry($container->get(Registry::class));

                return $component;
            }
        );
    }
};