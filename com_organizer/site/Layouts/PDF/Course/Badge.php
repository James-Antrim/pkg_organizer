<?php
/**
 * @package     Organizer
 * @extension   com_organizer
 * @author      James Antrim, <james.antrim@nm.thm.de>
 * @copyright   2020 TH Mittelhessen
 * @license     GNU GPL v.3
 * @link        www.thm.de
 */

namespace THM\Organizer\Layouts\PDF\Course;

use THM\Organizer\Adapters\Text;
use THM\Organizer\Layouts\PDF\BadgeLayout;
use THM\Organizer\Tables;
use THM\Organizer\Views\PDF\Course;

/**
 * Class loads persistent information about a course into the display context.
 */
class Badge extends BadgeLayout
{
    /** @inheritDoc */
    public function __construct(Course $view)
    {
        parent::__construct($view);
    }

    /** @inheritDoc */
    public function fill(array $data): void
    {
        $participant = new Tables\Participants();
        /** @noinspection PhpPossiblePolymorphicInvocationInspection */
        $participant->load($this->view->participantID);

        $yOffset = 0;

        $this->view->AddPage();
        $xOffset = 10;
        $this->front($participant, $xOffset, $yOffset);
        $xOffset += 92;
        $this->back($xOffset, $yOffset);
    }

    /** @inheritDoc */
    public function title(): void
    {
        /* @var Course $view */
        $view = $this->view;

        $documentName = "$view->course - $view->campus - $view->startDate - " . Text::_('ORGANIZER_BADGE');
        $view->titles($documentName);
    }
}
