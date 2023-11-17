<?php
/**
 * @package     Organizer
 * @extension   com_organizer
 * @author      James Antrim, <james.antrim@nm.thm.de>
 * @copyright   2020 TH Mittelhessen
 * @license     GNU GPL v.3
 * @link        www.thm.de
 */

namespace THM\Organizer\Layouts\PDF\ContactTracking;

use THM\Organizer\Adapters\{Input, Text};
use THM\Organizer\Layouts\PDF\ListLayout;
use THM\Organizer\Views\PDF\ContactTracking as View;

class ContactTracking extends ListLayout
{
    private const BY_DAY = 1, BY_EVENT = 2;

    /**
     * @var View
     */
    protected $view;

    protected $widths = [
        'contacts' => 65,
        'data'     => 70,
        'dates'    => 20,
        'index'    => 10,
        'length'   => 25,
        'person'   => 50
    ];

    /**
     * @inheritDoc
     */
    public function __construct(View $view)
    {
        parent::__construct($view);
        $view->margins(10, 30, -1, 0, 8);

        $headers = [
            'index'  => '#',
            'person' => Text::_('ORGANIZER_PERSON'),
            'data'   => Text::_('ORGANIZER_CONTACT_INFORMATION')
        ];

        $listFormat = (int) Input::getListItems()->get('listFormat', self::BY_DAY);

        switch ($listFormat) {
            case self::BY_EVENT:
                $otherHeaders = ['contacts' => Text::_('ORGANIZER_CONTACTS')];
                break;
            case self::BY_DAY:
            default:
                $otherHeaders = ['dates' => Text::_('ORGANIZER_DATES'), 'length' => Text::_('ORGANIZER_CONTACT_LENGTH')];
                break;
        }

        $this->headers = array_merge($headers, $otherHeaders);
    }

    /**
     * @inheritDoc
     */
    public function fill(array $data)
    {
        $itemNo = 1;
        $mText  = Text::_('ORGANIZER_MINUTES');
        $view   = $this->view;
        $this->addListPage();

        foreach ($data as $person) {
            // Get the starting coordinates for later use with borders
            $maxLength = 0;
            $startX    = $view->GetX();
            $startY    = $view->GetY();

            foreach (array_keys($this->headers) as $columnName) {
                switch ($columnName) {
                    case 'contacts':
                        $values = [];

                        foreach ($person->dates as $date => $events) {
                            $values[] = $date;
                            ksort($events);

                            foreach ($events as $event => $minutes) {
                                $values[] = " - $event: $minutes $mText";
                            }
                        }

                        $value = implode("\n", $values);
                        break;
                    case 'data' :
                        $values = [$person->telephone, $person->email, $person->address, "$person->zipCode $person->city"];
                        foreach ($values as $index => $dataPoint) {
                            $values[$index] = trim($dataPoint);
                        }
                        $values = array_filter($values);
                        $value  = implode("\n", $values);
                        break;
                    case 'dates' :
                        $values = array_keys($person->dates);
                        $value  = implode("\n", $values);
                        break;
                    case 'index':
                        $value = $itemNo;
                        break;
                    case 'length':
                        $values = [];
                        foreach ($person->dates as $minutes) {
                            $minutes  = array_sum($minutes);
                            $values[] = "$minutes $mText";
                        }
                        $value = implode("\n", $values);
                        break;
                    case 'person':
                        $value = $person->person;
                        break;
                    default:
                        $value = '';
                        break;
                }

                $length = $view->renderMultiCell($this->widths[$columnName], 5, $value);

                if ($length > $maxLength) {
                    $maxLength = $length;
                }
            }

            // Reset for borders
            $view->changePosition($startX, $startY);

            foreach (array_keys($this->headers) as $columnName) {
                $border = $columnName === 'index' ? ['BLR' => $view->border] : ['BR' => $view->border];
                $view->renderMultiCell($this->widths[$columnName], $maxLength * 5, '', $view::LEFT, $border);
            }

            $this->addLine();

            $itemNo++;
        }
    }

    /**
     * Generates the title and sets name related properties.
     */
    public function setTitle()
    {
        $documentName = $this->view->participantName;
        $this->view->setNames($documentName);
    }
}
