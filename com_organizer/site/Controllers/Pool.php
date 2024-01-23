<?php
/**
 * @package     Organizer
 * @extension   pkg_organizer
 * @author      James Antrim, <james.antrim@nm.thm.de>
 * @copyright   2023 TH Mittelhessen
 * @license     GNU GPL v.3
 * @link        www.thm.de
 */

namespace THM\Organizer\Controllers;

use THM\Organizer\Adapters\Application;

/**
 * @inheritDoc
 */
class Pool extends CurriculumResource
{
    protected string $list = 'Pools';

    /**
     * @inheritDoc
     */
    public function import(int $resourceID): bool
    {
        /**
         * This resource is completely inadequately maintained for actual documentation purposes, and is instead used for internal
         * metric validation. The actual data used for basic temporary modeling is delivered with its superordinate program.
         */
        Application::error(501);
        return false;
    }
}