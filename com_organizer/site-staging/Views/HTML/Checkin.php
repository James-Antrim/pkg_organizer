<?php
/**
 * @package     Organizer\Views\HTML
 * @extension   Organizer
 * @author      James Antrim, <james.antrim@nm.thm.de>
 * @copyright   2020 TH Mittelhessen
 * @license     GNU GPL v.3
 * @link        www.thm.de
 */

namespace THM\Organizer\Views\HTML;

use Joomla\CMS\Factory;
use THM\Organizer\Adapters\{Document, Input};
use THM\Organizer\Tables\Participants as Table;

/**
 * Generates the checkin form.
 */
class Checkin extends OldFormView
{
    public bool $complete = true;

    public bool $edit = false;

    public bool $privacy = false;

    public array $instances;

    public Table $participant;

    public int|null $roomID;

    public string|null $seat;

    /** @inheritDoc */
    protected function addToolBar(): void
    {
        if ($this->privacy) {
            $title = "Besondere Datenschutzhinweis zum THM Checkin-Verfahren im Zusammenhang mit der Coronapandemie";
        }
        elseif ($this->edit or !$this->complete) {
            $title = 'CONTACT_INFORMATION';
        }
        elseif ($this->instances) {
            if (count($this->instances) > 1) {
                $title = 'CONFIRM_EVENT';
            }
            elseif (!$this->roomID or $this->seat === null) {
                $title = 'CONFIRM_SEATING';
            }
            else {
                $title = 'CHECKED_IN';
            }
        }
        else {
            $title = 'CHECKIN';
        }

        $this->title($title);
    }

    /** @inheritDoc */
    public function display($tpl = null): void
    {
        $session = Factory::getSession();

        if ($layout = Input::cmd('layout')) {
            if ($this->privacy = $layout === 'privacy') {
                if (!$session->get('organizer.checkin.referrer')) {
                    $session->set(
                        'organizer.checkin.referrer',
                        Input::instance()->server->getString('HTTP_REFERER')
                    );
                }
            }

            $this->edit = $layout === 'profile';
        }

        if (!$layout or $layout !== 'privacy') {
            $session->set('organizer.checkin.referrer', '');
        }

        $this->instances   = $this->get('Instances');
        $this->layout      = 'checkin-wrapper';
        $this->participant = $this->get('Participant');
        $this->roomID      = $this->get('RoomID');
        $this->seat        = $this->get('Seat');

        $this->complete = true;

        if ($this->participant->id) {
            $requiredColumns = ['address', 'city', 'forename', 'surname', 'telephone', 'zipCode'];
            foreach ($requiredColumns as $column) {
                $this->complete = ($this->complete and !empty($this->participant->$column));
            }
        }

        parent::display($tpl);
    }

    /** @inheritDoc */
    protected function modifyDocument(): void
    {
        parent::modifyDocument();

        Document::script('checkin');
        Document::style('checkin');
    }
}