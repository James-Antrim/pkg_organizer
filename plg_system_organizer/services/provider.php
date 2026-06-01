<?php
/**
 * @package     Organizer
 * @extension   plg_system_organizer
 * @author      James Antrim, <james.antrim@nm.thm.de>
 * @copyright   2026 TH Mittelhessen
 * @license     GNU GPL v.3
 * @link        www.thm.de
 */

require_once JPATH_ADMINISTRATOR . '/components/com_organizer/services/autoloader.php';

use Joomla\CMS\{Extension\PluginInterface, Factory, Plugin\PluginHelper};
use Joomla\DI\{Container, ServiceProviderInterface};
use Joomla\Event\DispatcherInterface;
use THM\Organizer\Plugin\System\Organizer;

return new class() implements ServiceProviderInterface {
    public function register(Container $container): void
    {
        $container->set(
            PluginInterface::class,
            function (Container $container) {
                $plugin = new Organizer(
                    $container->get(DispatcherInterface::class),
                    (array) PluginHelper::getPlugin('system', 'organizer')
                );
                $plugin->setApplication(Factory::getApplication());

                return $plugin;
            }
        );
    }
};