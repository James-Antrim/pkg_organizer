<?php
/**
 * @package     Organizer
 * @extension   com_organizer
 * @author      James Antrim, <james.antrim@nm.thm.de>
 * @copyright   2021 TH Mittelhessen
 * @license     GNU GPL v.3
 * @link        www.thm.de
 */

namespace THM\Organizer\Controllers;

/** @inheritDoc */
class Equipment extends ListController
{
    use FacilityManageable;

    protected string $item = 'EquipmentItem';
}