<?php
/**
 * @package     Organizer
 * @extension   com_organizer
 * @author      James Antrim, <james.antrim@nm.thm.de>
 * @copyright   2020 TH Mittelhessen
 * @license     GNU GPL v.3
 * @link        www.thm.de
 */

namespace THM\Organizer\Models;

use THM\Organizer\Adapters\Application;
use THM\Organizer\Helpers\Can;

/**
 * Class loads a form for editing campus data.
 */
class EquipmentItem extends EditModel
{
    protected string $tableClass = 'Equipment';

    /**
     * Checks access to edit the resource.
     * @return void
     */
    protected function authorize(): void
    {
        if (!Can::fm()) {
            Application::error(403);
        }
    }
}
