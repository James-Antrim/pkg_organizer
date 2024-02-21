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

use THM\Organizer\Helpers\{Persons, Subjects as Helper};

/**
 * @inheritDoc
 */
class Subject extends EditModel
{
    protected string $tableClass = 'Subjects';

    /**
     * @inheritDoc
     */
    public function getItem(): object
    {
        $item = parent::getItem();

        $item->coordinators = [];
        foreach (Helper::persons($item->id, Persons::COORDINATES) as $coordinator) {
            $item->coordinators[$coordinator['id']] = $coordinator['id'];
        }
        $item->persons = [];
        foreach (Helper::persons($item->id, Persons::TEACHES) as $teacher) {
            $item->persons[$teacher['id']] = $teacher['id'];
        }
        $item->prerequisites = Helper::prerequisites($item->id);

        return $item;
    }
}
