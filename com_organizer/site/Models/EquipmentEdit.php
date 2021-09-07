<?php
/**
 * @package     Organizer
 * @extension   com_organizer
 * @author      James Antrim, <james.antrim@nm.thm.de>
 * @copyright   2020 TH Mittelhessen
 * @license     GNU GPL v.3
 * @link        www.thm.de
 */

namespace Organizer\Models;

use Organizer\Helpers;
use Organizer\Tables;

/**
 * Class loads a form for editing building data.
 */
class EquipmentEdit extends EditModel
{
    /**
     * Checks access to edit the resource.
     *
     * @return void
     */
    protected function authorize()
    {
        if (!Helpers\Can::manage('facilities'))
        {
            Helpers\OrganizerHelper::error(403);
        }
    }

    public function getTable($name = '', $prefix = '', $options = [])
    {
        return new Tables\Equipment();
    }
}
