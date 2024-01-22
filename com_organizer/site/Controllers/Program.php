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

/**
 * @inheritDoc
 */
class Program extends CurriculumResource
{
    protected string $list = 'Programs';

    protected function import(int $resourceID): void
    {
        // TODO: Implement import() method.
    }
}