<?php
/**
 * @package     Organizer
 * @extension   com_organizer
 * @author      James Antrim, <james.antrim@nm.thm.de>
 * @copyright   2020 TH Mittelhessen
 * @license     GNU GPL v.3
 * @link        www.thm.de
 */

namespace THM\Organizer\Fields;

/**
 * Class loads multiple/repeatable grid blocks from database and make it possible to advance them.
 * This needs an own form field to load the values, maybe because the periods are saved as json string.
 */
class Periods extends SubForm
{
    /** @inheritDoc */
    protected function getInput(): string
    {
        $this->value = $this->value['periods'] ?? [];

        return parent::getInput();
    }
}
