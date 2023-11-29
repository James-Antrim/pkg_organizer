<?php
/**
 * @package     Organizer
 * @extension   com_organizer
 * @author      James Antrim, <james.antrim@nm.thm.de>
 * @copyright   2018 TH Mittelhessen
 * @license     GNU GPL v.3
 * @link        www.thm.de
 */

namespace THM\Organizer\Views\HTML;

/**
 * Class loads the person merge form into display context.
 */
class MergeParticipants extends MergeView
{
    /**
     * @inheritDoc
     */
    protected string $controller = 'Participant';
}
