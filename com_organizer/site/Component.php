<?php
/**
 * @package     Organizer
 * @extension   com_organizer
 * @author      James Antrim, <james.antrim@nm.thm.de>
 * @copyright   2022 TH Mittelhessen
 * @license     GNU GPL v.3
 * @link        www.thm.de
 */

namespace THM\Organizer;

use Joomla\CMS\Component\Router\{RouterServiceInterface, RouterServiceTrait};
use Joomla\CMS\Extension\MVCComponent;
use Joomla\CMS\HTML\HTMLRegistryAwareTrait;
use THM\Organizer\Adapters\MVCFactory;

class Component extends MVCComponent implements RouterServiceInterface
{
    use HTMLRegistryAwareTrait;
    use RouterServiceTrait;

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
}

