<?php
/**
 * @package     Organizer
 * @extension   com_organizer
 * @author      James Antrim, <james.antrim@nm.thm.de>
 * @copyright   2020 TH Mittelhessen
 * @license     GNU GPL v.3
 * @link        www.thm.de
 */

namespace THM\Organizer\Layouts\PDF;

use THM\Organizer\Helpers;
use THM\Organizer\Helpers\Languages;
use THM\Organizer\Views\PDF\CourseParticipants;

/**
 * Base class for a Joomla View
 * Class holding methods for displaying presentation data.
 */
abstract class BadgeLayout extends BaseLayout
{
    protected array $rectangleStyle = [
        'width' => 0.1,
        'cap' => 'butt',
        'join' => 'miter',
        'dash' => 0,
        'color' => [0, 0, 0]
    ];

    /**
     * @inheritDoc
     */
    public function __construct(CourseParticipants $view)
    {
        parent::__construct($view);
        $view->margins();
        $view->setPageOrientation($view::LANDSCAPE);
        $view->showPrintOverhead(false);
    }

    /**
     * Adds a badge position to the sheet
     *
     * @param object $participant the participant being iterated
     * @param int    $xOffset     the reference value for x
     * @param int    $yOffset     the reference value for y
     *
     * @return void modifies the pdf document
     */
    protected function addBadge(object $participant, int $xOffset, int $yOffset)
    {
        /* @var CourseParticipants $view */
        $view = $this->view;
        $view->SetLineStyle($this->rectangleStyle);
        $view->Rect($xOffset, $yOffset + 10, 90, 80);

        $left = $xOffset + 4;
        $view->Image(K_PATH_IMAGES . 'logo.png', $left, $yOffset + 15, 30);

        $view->changePosition($xOffset + 70, $yOffset + 15);
        $view->changeFont($view::REGULAR, 10);
        $view->renderCell(16, 5, $participant->id, $view::CENTER, $view::ALL);

        $view->changePosition($left, $yOffset + 29);
        $view->changeFont($view::BOLD, 12);
        $headerLine = $view->course;
        $view->renderMultiCell(80, 5, $headerLine, $view::CENTER);

        $titleOffset = strlen($headerLine) > 35 ? 9 : 2;

        $view->changeFont($view::REGULAR, 10);
        $dates = $view->startDate == $view->endDate ? $view->startDate : "$view->startDate - $view->endDate";

        if ($view->campus) {
            $view->changePosition($left, $yOffset + $titleOffset + 33);
            $view->renderCell(80, 5, $view->campus, $view::CENTER);
            $view->changePosition($left, $yOffset + $titleOffset + 38);
        } else {
            $view->changePosition($left, $yOffset + $titleOffset + 34);
        }

        $view->renderCell(80, 5, $dates, $view::CENTER);
        $halfTitleOffset = $titleOffset / 2;
        $view->Ln();
        $view->changeFont($view::BOLD, 20);
        $view->changePosition($left, $yOffset + $halfTitleOffset + 47);
        $view->renderCell(80, 5, Languages::_('ORGANIZER_BADGE'), $view::CENTER);

        $view->changePosition($left, $yOffset + 45);
        $view->changeFont($view::REGULAR, 10);

        $yOffset += 63;
        $view->Ln();
        $view->changePosition($left, $yOffset);
        $view->renderCell(20, 5, Languages::_('ORGANIZER_NAME') . ': ');
        $view->changeFont($view::BOLD);
        $surname = $participant->surname ?: '';
        $view->renderCell(65, 5, $surname);

        $view->Ln();
        $yOffset += 5;
        $view->changePosition($left, $yOffset);
        $view->renderCell(20, 5, '');
        $forename = $participant->forename ?: '';
        $view->renderCell(65, 5, $forename);


        $view->Ln();
        $yOffset += 5;
        $view->changeFont();
        $view->changePosition($left, $yOffset);
        $view->renderCell(20, 5, Languages::_('ORGANIZER_ADDRESS') . ': ');
        $address1 = $participant->address ?: '';
        $view->renderCell(65, 5, $address1);

        $view->Ln();
        $yOffset += 5;
        $view->changePosition($left, $yOffset);
        $view->renderCell(20, 5, Languages::_('ORGANIZER_RESIDENCE') . ': ');
        $view->renderCell(65, 5, "$participant->zipCode $participant->city");
    }

    /**
     * Adds a badge reverse to the sheet reverse
     *
     * @param int $xOffset the reference x offset for the box
     * @param int $yOffset the reference y offset for the box
     *
     * @return void modifies the pdf document
     */
    protected function addBadgeBack(int $xOffset, int $yOffset)
    {
        /* @var CourseParticipants $view */
        $view = $this->view;
        $view->SetLineStyle($this->rectangleStyle);
        $view->Rect($xOffset, 10 + $yOffset, 90, 80);

        $badgeCenter = $xOffset + 5;

        if ($view->fee) {
            $headerOffset    = 12 + $yOffset;
            $titleOffset     = 24 + $yOffset;
            $labelOffset     = 55 + $yOffset;
            $signatureOffset = 61 + $yOffset;
            $nameOffset      = 76 + $yOffset;
            $addressOffset   = 80 + $yOffset;
            $contactOffset   = 83 + $yOffset;
        } else {
            $headerOffset    = 17 + $yOffset;
            $titleOffset     = 29 + $yOffset;
            $labelOffset     = 42 + $yOffset;
            $signatureOffset = 47 + $yOffset;
            $nameOffset      = 62 + $yOffset;
            $addressOffset   = 73 + $yOffset;
            $contactOffset   = 76 + $yOffset;
        }

        $view->changeFont($view::BOLD, 20);
        $view->changePosition($badgeCenter, $headerOffset);
        $view->renderCell(80, 5, Languages::_('ORGANIZER_RECEIPT'), $view::CENTER);

        $view->changeFont($view::BOLD, 12);
        $title       = $view->course;
        $longTitle   = strlen($title) > 50;
        $titleOffset = $longTitle ? $titleOffset - 3 : $titleOffset;
        $view->changePosition($badgeCenter, $titleOffset);
        $view->renderMultiCell(80, 5, $title, $view::CENTER);

        $dates      = $view->startDate == $view->endDate ? $view->startDate : "$view->startDate - $view->endDate";
        $dateOffset = $longTitle ? $titleOffset + 10 : $titleOffset + 6;
        $view->changePosition($badgeCenter, $dateOffset);
        $view->changeFont($view::REGULAR, 10);
        $view->renderMultiCell(80, 5, $dates, $view::CENTER);

        if ($view->fee) {
            $view->changePosition($badgeCenter, 37 + $yOffset);
            $view->changeFont($view::REGULAR, 11);
            $view->renderMultiCell(
                80,
                5,
                sprintf(Languages::_('ORGANIZER_BADGE_PAYMENT_TEXT'), $view->fee),
                $view::CENTER
            );

            $view->changePosition($badgeCenter, 50 + $yOffset);
            $view->changeFont($view::ITALIC, 6);
            $view->renderMultiCell(80, 5, Languages::_('ORGANIZER_BADGE_TAX_TEXT'), $view::CENTER);
        }

        $view->changeSize(8);
        $view->changePosition($badgeCenter, $labelOffset);
        $view->renderCell(80, 5, Languages::_('ORGANIZER_REPRESENTATIVE'), $view::CENTER);

        $params = Helpers\Input::getParams();
        if (!empty($params->get('signatureFile'))) {
            $signaturePath = K_PATH_IMAGES . $params->get('signatureFile');
            $view->Image($signaturePath, $xOffset + 35, $signatureOffset, 20);
        }

        $view->changeSize(7);
        $view->changePosition($badgeCenter, $nameOffset);
        $view->renderCell(80, 5, $params->get('representativeName', ''), $view::CENTER);

        $view->changeSize(6);
        $view->changePosition($badgeCenter, $addressOffset);
        $view->renderCell(80, 5, $params->get('address'), $view::CENTER);

        $view->changePosition($badgeCenter, $contactOffset);
        $view->renderCell(80, 5, $params->get('contact'), $view::CENTER);
    }
}
