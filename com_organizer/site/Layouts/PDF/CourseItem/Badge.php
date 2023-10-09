<?php
/**
 * @package     Organizer
 * @extension   com_organizer
 * @author      James Antrim, <james.antrim@nm.thm.de>
 * @copyright   2020 TH Mittelhessen
 * @license     GNU GPL v.3
 * @link        www.thm.de
 */

namespace Organizer\Layouts\PDF\CourseItem;

use Organizer\Helpers;
use Organizer\Layouts\PDF\BadgeLayout;
use Organizer\Tables;
use Organizer\Views\PDF\CourseItem;

/**
 * Class loads persistent information about a course into the display context.
 */
class Badge extends BadgeLayout
{
    /**
     * @inheritDoc
     */
    public function __construct(CourseItem $view)
    {
        parent::__construct($view);
    }

    /**
     * @inheritdoc
     */
    public function fill(array $data)
    {
        $participant = new Tables\Participants();
        /** @noinspection PhpPossiblePolymorphicInvocationInspection */
        $participant->load($this->view->participantID);

        $yOffset = 0;

        $this->view->AddPage();
        $xOffset = 10;
        $this->addBadge($participant, $xOffset, $yOffset);
        $xOffset += 92;
        $this->addBadgeBack($xOffset, $yOffset);
    }

    /**
     * Generates the title and sets name related properties.
     */
    public function setTitle()
    {
        /* @var CourseItem $view */
        $view         = $this->view;
        $documentName = "$view->course - $view->campus - $view->startDate - " . Helpers\Languages::_('ORGANIZER_BADGE');
        $view->setNames($documentName);
    }
}
