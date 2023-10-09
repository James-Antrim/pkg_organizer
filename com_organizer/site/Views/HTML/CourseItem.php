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

use THM\Organizer\Adapters\Toolbar;
use THM\Organizer\Helpers;
use THM\Organizer\Helpers\Languages;

/**
 * Class loads the subject into the display context.
 */
class CourseItem extends ItemView
{
    // Participant statuses
    const UNREGISTERED = null, WAITLIST = 0, ACCEPTED = 1;

    // Course Statuses
    const EXPIRED = -1, ONGOING = 1;

    private $manages = false;

    /**
     * @inheritdoc
     */
    public function __construct($config = [])
    {
        parent::__construct($config);

        if (Helpers\Can::scheduleTheseOrganizations() or Helpers\Can::manage('courses')) {
            $this->manages = true;
        }
    }

    /**
     * Adds supplemental information to the display output.
     * @return void modifies the object property supplement
     */
    protected function addSupplement()
    {
        if ($this->manages) {
            return;
        }

        $course = $this->item;

        $text = '<div class="tbox-' . $course['courseStatus'] . '">' . $course['courseText'] . '</div>';

        $this->supplement = $text;
    }

    /**
     * Adds a toolbar and title to the view.
     * @return void  adds toolbar items to the view
     */
    protected function addToolBar()
    {
        if (!$this->manages and $participantID = Helpers\Users::getID()) {
            $courseID        = $this->item['id'];
            $deadline        = $this->item['deadline'];
            $link            = 'index.php?option=com_organizer';
            $maxParticipants = $this->item['maxParticipants'];
            $participants    = $this->item['participants'];
            $startDate       = $this->item['startDate'];
            $today           = Helpers\Dates::standardizeDate();
            $toolbar         = Toolbar::getInstance();

            $deadline = $deadline ? date('Y-m-d', strtotime("-$deadline Days", strtotime($startDate))) : $startDate;

            if (Helpers\Participants::exists()) {
                $toolbar->appendButton(
                    'Link',
                    'vcard',
                    Languages::_('ORGANIZER_PROFILE_EDIT'),
                    'index.php?option=com_organizer&view=participant_edit'
                );

                if ($deadline > $today) {
                    $full         = $participants >= $maxParticipants;
                    $link         .= '&id=' . $this->item['id'];
                    $state        = Helpers\CourseParticipants::getState($courseID, $participantID);
                    $validProfile = Helpers\CourseParticipants::validProfile($courseID, $participantID);
                    if (!$full and $state === self::UNREGISTERED and $validProfile) {
                        $rLink = $link . '&task=courses.register';
                        $toolbar->appendButton('Link', 'enter', Languages::_('ORGANIZER_REGISTER'), $rLink);
                    } elseif ($state === self::ACCEPTED or $state === self::WAITLIST) {
                        $drLink = $link . '&task=courses.deregister';
                        $toolbar->appendButton('Link', 'exit', Languages::_('ORGANIZER_DEREGISTER'), $drLink);

                        $hasPaid = Helpers\CourseParticipants::hasPaid($courseID, $participantID);
                        if ($state === self::ACCEPTED and $hasPaid) {
                            $bLink = $link . '&view=CourseItem&task=Courses.badge';
                            $toolbar->appendButton(
                                'Link',
                                'tags-2',
                                Languages::_('ORGANIZER_DOWNLOAD_BADGE'),
                                $bLink,
                                true
                            );
                        }
                    }
                }

            } else {
                $toolbar->appendButton(
                    'Link',
                    'user-plus',
                    Languages::_('ORGANIZER_PROFILE_NEW'),
                    $link . '&view=participant_edit'
                );
            }
        }
    }

    /**
     * Creates a subtitle element from the term name and the start and end dates of the course.
     * @return void modifies the course
     */
    protected function setSubtitle()
    {
        $this->subtitle = '<h6 class="sub-title">';

        if ($this->item['campusID']) {
            $campusName     = Helpers\Campuses::getName($this->item['campusID']);
            $this->subtitle .= Languages::_('ORGANIZER_CAMPUS') . " $campusName: ";
        }

        $this->subtitle .= Helpers\Courses::getDateDisplay($this->item['id']) . '</h6>';
    }
}