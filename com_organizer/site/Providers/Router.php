<?php
/**
 * @package     Organizer
 * @extension   com_organizer
 * @author      James Antrim, <james.antrim@nm.thm.de>
 * @copyright   2026 TH Mittelhessen
 * @license     GNU GPL v.3
 * @link        www.thm.de
 */

namespace THM\Organizer\Providers;

use Joomla\CMS\Component\Router\RouterFactoryInterface;
use Joomla\Database\DatabaseInterface;
use Joomla\DI\{Container, ServiceProviderInterface};
use THM\Organizer\Adapters\RouterFactory;

/**
 * Service provider for the service router factory.
 */
class Router implements ServiceProviderInterface
{
    /**
     * Registers the service provider with a DI container.
     *
     * @param Container $container The DI container.
     *
     * @return  void
     */
    public function register(Container $container): void
    {
        $container->set(
            RouterFactoryInterface::class,
            function (Container $container) {
                return new RouterFactory(
                    $container->get(DatabaseInterface::class)
                );
            }
        );
    }
}
