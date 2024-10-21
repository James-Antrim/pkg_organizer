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
use THM\Organizer\Adapters\MVCFactory;

class Component extends MVCComponent implements RouterServiceInterface
{
    use HTMLRegistryAwareTrait;

    /** @inheritDoc */
    public function createRouter(CMSApplicationInterface $application, AbstractMenu $menu): RouterInterface
    {
        /** @var SiteApplication $application */
        return new Router($application, $menu);
    }

    /**
     * Wrapper for the getMVCFactory function to accurately return type the unnecessarily private property mvcFactory.
     * @return MVCFactory
     */
    public function mvcFactory(): MVCFactory
    {
        /** @var MVCFactory $factory */
        $factory = $this->getMVCFactory();

        return $factory;
    }

    public function setRouterFactory($get)
    {

    }
}

