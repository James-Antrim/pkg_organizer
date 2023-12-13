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

use THM\Organizer\Adapters\{Application, Input, Text, Toolbar, User};
use THM\Organizer\Buttons\FormTarget;
use THM\Organizer\Helpers;

/**
 * Class loads persistent information a filtered set of colors into the display context.
 */
class ContactTracking extends ListView
{
    private const BY_DAY = 1, BY_EVENT = 2;

    /**
     * @inheritDoc
     */
    public function __construct($config = [])
    {
        parent::__construct($config);

        $listFormat = (int) Input::getListItems()->get('listFormat', self::BY_DAY);
        $structure  = ['index' => 'value', 'person' => 'value', 'data' => 'value'];

        $structure = match ($listFormat) {
            self::BY_EVENT => array_merge($structure, ['contacts' => 'value']),
            default => array_merge($structure, ['dates' => 'value', 'length' => 'value']),
        };

        $this->rowStructure = $structure;
    }

    /**
     * @inheritDoc
     */
    protected function setSubTitle(): void
    {
        $then           = Helpers\Dates::formatDate(date('Y-m-d', strtotime("-28 days")));
        $today          = Helpers\Dates::formatDate(date('Y-m-d'));
        $this->subtitle = Text::_('INTERVAL') . ": $then - $today";
    }

    /**
     * @inheritDoc
     */
    protected function addToolBar(bool $delete = true): void
    {
        $this->setTitle('CONTACT_TRACKING');

        if (($this->state->get('participantID') or $this->state->get('personID')) and count($this->items)) {
            $toolbar = Toolbar::getInstance();
            //$toolbar->standardButton('notify', Text::_('NOTIFY'));
            $button = new FormTarget('contactmap', 'Download as PDF');
            $button->icon('fa fa-file-pdf')->task('ContactTracking.pdf');
            $toolbar->appendButton($button);
        }
    }

    /**
     * @inheritDoc
     */
    protected function authorize(): void
    {
        if (!User::id()) {
            Application::error(401);
        }

        if (!Helpers\Can::traceContacts()) {
            Application::error(403);
        }
    }

    /**
     * @inheritDoc
     */
    protected function completeItems()
    {
        $index           = 1;
        $link            = '';
        $listFormat      = (int) Input::getListItems()->get('listFormat', self::BY_DAY);
        $mText           = Text::_('MINUTES');
        $structuredItems = [];

        foreach ($this->items as $item) {
            $data = [$item->telephone, $item->email, $item->address, "$item->zipCode $item->city"];
            $data = array_filter($data);

            $item->index  = $index;
            $item->data   = implode('<br>', $data);
            $item->person .= $item->username ? " ($item->username)" : '';

            switch ($listFormat) {
                case self::BY_EVENT:

                    $contacts = '';

                    foreach ($item->dates as $date => $events) {
                        $contacts .= "$date<br>";
                        ksort($events);

                        foreach ($events as $event => $minutes) {
                            $contacts .= " - $event: $minutes $mText<br>";
                        }

                    }

                    $item->contacts = $contacts;

                    break;
                case self::BY_DAY:
                default:

                    $dates   = [];
                    $lengths = [];

                    foreach ($item->dates as $date => $minutes) {
                        $dates[]   = Helpers\Dates::formatDate($date);
                        $minutes   = array_sum($minutes);
                        $lengths[] = "$minutes $mText";
                    }

                    $item->dates  = implode('<br>', $dates);
                    $item->length = implode('<br>', $lengths);

                    break;

            }

            $structuredItems[$index] = $this->completeItem($index, $item, $link);
            $index++;
        }

        $this->items = $structuredItems;
    }

    /**
     * @inheritDoc
     */
    public function display($tpl = null)
    {
        $filterItems = Input::getFilterItems();

        // If a query string was entered feedback is a part of a system message.
        if ($filterItems->get('search')) {
            $this->empty = ' ';
        }
        else {
            $this->empty = Text::_('ENTER_SEARCH_TERM');
        }

        parent::display($tpl);
    }

    /**
     * @inheritDoc
     */
    public function initializeColumns()
    {
        $listFormat = (int) Input::getListItems()->get('listFormat', self::BY_DAY);
        $headers    = [
            'index'  => '#',
            'person' => Text::_('PERSON'),
            'data'   => Text::_('CONTACT_INFORMATION')
        ];

        $otherHeaders = match ($listFormat) {
            self::BY_EVENT => ['contacts' => Text::_('CONTACTS')],
            default => [
                'dates'  => Text::_('DATES'),
                'length' => Text::_('CONTACT_LENGTH')
            ],
        };

        $headers       = array_merge($headers, $otherHeaders);
        $this->headers = $headers;
    }
}
