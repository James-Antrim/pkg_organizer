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

use THM\Organizer\Adapters\{Text, Toolbar, User};
use THM\Organizer\Helpers\{Campuses, CourseParticipants, Courses, Dates, Participants};

/**
 * Class loads the subject into the display context.
 */
class CourseItem extends ItemView
{
    private $manages = false;

    /**
     * @inheritDoc
     */
    public function __construct($config = [])
    {
        parent::__construct($config);

        if (Courses::coordinatable()) {
            $this->manages = true;
        }
    }

    /**
     * Adds a toolbar and title to the view.
     * @return void  adds toolbar items to the view
     */
    protected function addToolBar(): void
    {
        if (!$this->manages and $participantID = User::id()) {
            $courseID        = $this->item['id'];
            $deadline        = $this->item['deadline'];
            $link            = 'index.php?option=com_organizer';
            $maxParticipants = $this->item['maxParticipants'];
            $participants    = $this->item['participants'];
            $startDate       = $this->item['startDate'];
            $today           = Dates::standardize();
            $toolbar         = Toolbar::getInstance();

            $deadline = $deadline ? date('Y-m-d', strtotime("-$deadline Days", strtotime($startDate))) : $startDate;

            if (Participants::exists()) {
                $toolbar->appendButton(
                    'Link',
                    'vcard',
                    Text::_('PROFILE_EDIT'),
                    'index.php?option=com_organizer&view=participant_edit'
                );

                if ($deadline > $today) {
                    $full         = $participants >= $maxParticipants;
                    $link         .= '&id=' . $this->item['id'];
                    $state        = CourseParticipants::state($courseID, $participantID);
                    $validProfile = CourseParticipants::validProfile($courseID, $participantID);
                    if (!$full and $state === CourseParticipants::UNREGISTERED and $validProfile) {
                        $rLink = $link . '&task=courses.register';
                        $toolbar->appendButton('Link', 'enter', Text::_('REGISTER'), $rLink);
                    }
                    elseif ($state === CourseParticipants::ACCEPTED or $state === CourseParticipants::WAITLIST) {
                        $drLink = $link . '&task=courses.deregister';
                        $toolbar->appendButton('Link', 'exit', Text::_('DEREGISTER'), $drLink);

                        $hasPaid = CourseParticipants::paid($courseID, $participantID);
                        if ($state === CourseParticipants::ACCEPTED and $hasPaid) {
                            $bLink = $link . '&view=CourseItem&task=Courses.badge';
                            $toolbar->appendButton(
                                'Link',
                                'tags-2',
                                Text::_('DOWNLOAD_BADGE'),
                                $bLink,
                                true
                            );
                        }
                    }
                }

            }
            else {
                $toolbar->appendButton(
                    'Link',
                    'user-plus',
                    Text::_('ADD_PROFILE'),
                    $link . '&view=participant_edit'
                );
            }
        }
    }

    /**
     * Creates a subtitle element from the term name and the start and end dates of the course.
     * @return void modifies the course
     */
    protected function setSubtitle(): void
    {
        $this->subtitle = '<h6 class="sub-title">';

        if ($this->item['campusID']) {
            $campusName     = Campuses::name($this->item['campusID']);
            $this->subtitle .= Text::_('CAMPUS') . " $campusName: ";
        }

        $this->subtitle .= Courses::displayDate($this->item['id']) . '</h6>';
    }

    /**
     * Adds supplemental information to the display output.
     * @return void modifies the object property supplement
     */
    protected function setSupplement(): void
    {
        if ($this->manages) {
            return;
        }

        $course = $this->item;

        $text = '<div class="tbox-' . $course['courseStatus'] . '">' . $course['courseText'] . '</div>';

        $this->supplement = $text;
    }
}