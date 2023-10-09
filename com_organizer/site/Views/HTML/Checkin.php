<?php
/**
 * @package     Organizer\Views\HTML
 * @extension   Organizer
 * @author      James Antrim, <james.antrim@nm.thm.de>
 * @copyright   2020 TH Mittelhessen
 * @license     GNU GPL v.3
 * @link        www.thm.de
 */

namespace Organizer\Views\HTML;

use Joomla\CMS\Factory;
use Joomla\CMS\Uri\Uri;
use Organizer\Adapters\Document;
use Organizer\Helpers;
use Organizer\Tables\Participants as Table;

/**
 * Generates the checkin form.
 */
class Checkin extends FormView
{
    public $complete = true;

    public $edit = false;

    public $privacy = false;

    /**
     * @var array
     */
    public $instances;

    /**
     * @var Table
     */
    public $participant;

    /**
     * @var int|null
     */
    public $roomID;

    /**
     * @var null|string
     */
    public $seat;

    /**
     * @inheritDoc
     */
    protected function addToolBar()
    {
        if ($this->privacy) {
            $title = "Besondere Datenschutzhinweis zum THM Checkin-Verfahren im Zusammenhang mit der Coronapandemie";
        } elseif ($this->edit or !$this->complete) {
            $title = 'ORGANIZER_CONTACT_INFORMATION';
        } elseif ($this->instances) {
            if (count($this->instances) > 1) {
                $title = 'ORGANIZER_CONFIRM_EVENT';
            } elseif (!$this->roomID or $this->seat === null) {
                $title = 'ORGANIZER_CONFIRM_SEATING';
            } else {
                $title = 'ORGANIZER_CHECKED_IN';
            }
        } else {
            $title = 'ORGANIZER_CHECKIN';
        }

        $this->setTitle($title);
    }

    /**
     * @inheritDoc
     */
    public function display($tpl = null)
    {
        $session = Factory::getSession();

        if ($layout = Helpers\Input::getCMD('layout')) {
            if ($this->privacy = $layout === 'privacy') {
                if (!$session->get('organizer.checkin.referrer')) {
                    $session->set(
                        'organizer.checkin.referrer',
                        Helpers\Input::getInput()->server->getString('HTTP_REFERER')
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

    /**
     * @inheritDoc
     */
    protected function modifyDocument()
    {
        parent::modifyDocument();

        Document::addScript(Uri::root() . 'components/com_organizer/js/checkin.js');
        Document::addStyleSheet(Uri::root() . 'components/com_organizer/css/checkin.css');
    }
}