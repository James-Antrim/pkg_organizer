<?php
/**
 * @package     Organizer
 * @extension   com_organizer
 * @author      James Antrim, <james.antrim@nm.thm.de>
 * @copyright   2020 TH Mittelhessen
 * @license     GNU GPL v.3
 * @link        www.thm.de
 */

namespace THM\Organizer\Views\HTML;

/**
 * Class loads the room form into display context.
 */
class Room extends FormView
{
    // Everything is taken care of in the inheritance hierarchy.
    public array $toDo = [
        'Add additional fields for FM relevant attributes and values.',
        'Add sanity checks for eff./max. capacity.'
    ];
}
