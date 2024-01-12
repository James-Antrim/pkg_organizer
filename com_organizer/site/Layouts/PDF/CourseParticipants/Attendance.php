<?php
/**
 * @package     Organizer
 * @extension   com_organizer
 * @author      James Antrim, <james.antrim@nm.thm.de>
 * @copyright   2020 TH Mittelhessen
 * @license     GNU GPL v.3
 * @link        www.thm.de
 */

namespace THM\Organizer\Layouts\PDF\CourseParticipants;

use THM\Organizer\Adapters\Text;
use THM\Organizer\Helpers;
use THM\Organizer\Layouts\PDF\ListLayout;
use THM\Organizer\Views\PDF\CourseParticipants;

/**
 * Class loads persistent information about a course into the display context.
 */
class Attendance extends ListLayout
{
    /**
     * @var CourseParticipants
     */
    protected $view;

    protected $widths = [
        'index'        => 10,
        'name'         => 55,
        'organization' => 25,
        'program'      => 85,
        'room'         => 15
    ];

    /**
     * @inheritDoc
     */
    public function __construct(CourseParticipants $view)
    {
        parent::__construct($view);
        $view->margins(10, 30, -1, 0, 8);

        $this->headers = [
            'index'        => '#',
            'name'         => 'Name',
            'organization' => Text::_('ORGANIZER_ORGANIZATION'),
            'program'      => Text::_('ORGANIZER_PROGRAM'),
            'room'         => Text::_('ORGANIZER_ROOM')
        ];

        // Adjust for more information
        if ($view->fee) {
            $this->headers['paid'] = Text::_('ORGANIZER_PAID');
            $this->widths['name']  = 42;
            $this->widths['paid']  = 14;
            $this->widths['room']  = 14;
        }
    }

    /**
     * @inheritDoc
     */
    public function fill(array $data)
    {
        $itemNo = 1;
        $view   = $this->view;
        $this->addListPage();

        foreach ($data as $participant) {
            // Get the starting coordinates for later use with borders
            $maxLength = 0;
            $startX    = $view->GetX();
            $startY    = $view->GetY();

            foreach (array_keys($this->headers) as $columnName) {
                $value = match ($columnName) {
                    'index' => $itemNo,
                    'name' => empty($participant->forename) ?
                        $participant->surname : "$participant->surname,  $participant->forename",
                    'organization' => Helpers\Programs::organization((int) $participant->programID, true),
                    'program' => Helpers\Programs::name((int) $participant->programID),
                    default => '',
                };

                $length = $view->renderMultiCell($this->widths[$columnName], 5, $value);

                if ($length > $maxLength) {
                    $maxLength = $length;
                }
            }

            $this->addLineBorders($startX, $startY, $maxLength);
            $this->addLine();
            $itemNo++;
        }
    }

    /**
     * Generates the title and sets name related properties.
     */
    public function setTitle()
    {
        $view         = $this->view;
        $documentName = "$view->course - $view->campus - $view->startDate - " . Text::_('ORGANIZER_PARTICIPANTS');
        $view->setNames($documentName);
    }
}
