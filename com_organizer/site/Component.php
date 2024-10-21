<?php
/**
 * @package     Organizer
 * @extension   com_organizer
 * @author      James Antrim, <james.antrim@nm.thm.de>
 * @copyright   2022 TH Mittelhessen
 * @license     GNU GPL v.2
 * @link        www.thm.de
 */

namespace THM\Organizer;

use Joomla\CMS\{Application\CMSApplicationInterface, Application\SiteApplication, HTML\HTMLRegistryAwareTrait, Menu\AbstractMenu};
use Joomla\CMS\Component\Router\{RouterInterface, RouterServiceInterface};
use Joomla\CMS\Extension\MVCComponent;

class Component extends MVCComponent implements RouterServiceInterface
{
    use HTMLRegistryAwareTrait;

    /** @inheritDoc */
    public function createRouter(CMSApplicationInterface $application, AbstractMenu $menu): RouterInterface
    {
        /** @var SiteApplication $application */
        return new Router($application, $menu);
    }

    public function setRouterFactory($get)
    {

    }
}

