<?php
/**
 * @package     Organizer
 * @extension   com_organizer
 * @author      James Antrim, <james.antrim@nm.thm.de>
 * @copyright   2020 TH Mittelhessen
 * @license     GNU GPL v.3
 * @link        www.thm.de
 */

namespace THM\Organizer\Views\PDF;

use THM\Organizer\Adapters\{Application, Text};
use THM\Organizer\Helpers;
use THM\Organizer\Tables\Participants;
use THM\Organizer\Tables\Persons;

/**
 * Class loads persistent information about a course into the display context.
 */
class ContactTracking extends ListView
{
    public $participantName;

    /**
     * Performs initial construction of the TCPDF Object.
     *
     * @param   string  $orientation  page orientation
     * @param   string  $unit         unit of measure
     * @param   mixed   $format       page format; possible values: string - common format name, array - parameters
     *
     * @see \TCPDF_STATIC::getPageSizeFromFormat(), setPageFormat()
     */
    public function __construct($orientation = self::PORTRAIT, $unit = 'mm', $format = 'A4')
    {
        parent::__construct($orientation, $unit, $format);

        $name  = '';
        $state = $this->formState;

        if ($participantID = $state->get('participantID')) {
            $user = Helpers\Users::getUser($participantID);
            $name = $user->name;
        }
        elseif ($personID = $state->get('personID')) {
            $person = new Persons();
            if ($person->load($personID)) {
                if ($person->forename) {
                    $name .= "$person->forename ";
                }

                $name .= "$person->surname ";
            }
        }

        if (!$name) {
            Application::error(400);
        }

        $this->participantName = $name;
    }

    /**
     * @inheritDoc
     */
    protected function authorize()
    {
        if (!Helpers\Users::getID()) {
            Application::error(401);
        }

        if (!Helpers\Can::traceContacts()) {
            Application::error(403);
        }
    }

    /**
     * @inheritDoc
     */
    public function Footer()
    {
        //set style for cell border
        $pageWidth = (0.85 / $this->k);
        $this->SetLineStyle(['width' => $pageWidth, 'color' => $this->footer_line_color]);

        $pnText = $this->getAliasNumPage() . ' / ' . $this->getAliasNbPages();

        $this->SetX($this->original_lMargin);
        $text = Text::_('ORGANIZER_COVID_CONTACT');
        $this->Cell(0, 0, $text, self::TOP, 0, self::CENTER);
        $this->Cell(0, 0, $this->getAliasRightShift() . $pnText, self::TOP, 0, self::RIGHT);
    }

    /**
     * Set header items.
     * @return void
     */
    public function setOverhead()
    {
        $title = Text::_('ORGANIZER_COVID_CONTACTS') . $this->participantName;

        $then  = Helpers\Dates::formatDate(date('Y-m-d', strtotime("-28 days")));
        $today = Helpers\Dates::formatDate(date('Y-m-d'));
        $title .= " $then - $today";

        $participant = new Participants();
        $subTitles   = [];

        if ($participantID = $this->formState->get('participantID') and $participant->load($participantID)) {
            $user        = Helpers\Users::getUser($participantID);
            $subTitles[] = Text::_('ORGANIZER_EMAIL') . ": $user->email";

            if ($participant->telephone) {
                $subTitles[] = Text::_('ORGANIZER_TELEPHONE') . ": $participant->telephone";
            }

            if ($participant->address or $participant->zipCode or $participant->city) {
                $line3       = [$participant->address, $participant->zipCode, $participant->city];
                $subTitles[] = Text::_('ORGANIZER_ADDRESS') . ': ' . implode(' ', $line3);
            }
        }

        $this->setHeaderData('pdf_logo.png', '55', $title, implode("\n", $subTitles), self::BLACK, self::WHITE);
        $this->setFooterData(self::BLACK, self::WHITE);

        parent::setHeader();
    }
}
