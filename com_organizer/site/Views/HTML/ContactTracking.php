<?php
/**
 * @package     Organizer
 * @extension   com_organizer
 * @author      James Antrim, <james.antrim@nm.thm.de>
 * @copyright   2020 TH Mittelhessen
 * @license     GNU GPL v.3
 * @link        www.thm.de
 */

namespace Organizer\Views\HTML;

use Organizer\Adapters\Toolbar;
use Organizer\Helpers;
use Organizer\Helpers\Languages;

/**
 * Class loads persistent information a filtered set of colors into the display context.
 */
class ContactTracking extends ListView
{
    private const BY_DAY = 1, BY_EVENT = 2;

    /**
     * @inheritdoc
     */
    public function __construct($config = [])
    {
        parent::__construct($config);

        $listFormat = (int)Helpers\Input::getListItems()->get('listFormat', self::BY_DAY);
        $structure  = ['index' => 'value', 'person' => 'value', 'data' => 'value'];

        switch ($listFormat) {
            case self::BY_EVENT:
                $structure = array_merge($structure, ['contacts' => 'value']);
                break;
            case self::BY_DAY:
            default:
                $structure = array_merge($structure, ['dates' => 'value', 'length' => 'value']);
                break;

        }

        $this->rowStructure = $structure;
    }

    /**
     * @inheritdoc
     */
    protected function addToolBar()
    {
        Helpers\HTML::setTitle(Languages::_("ORGANIZER_CONTACT_TRACKING"), 'list-2');

        if (($this->state->get('participantID') or $this->state->get('personID')) and count($this->items)) {
            $toolbar = Toolbar::getInstance();
            //$toolbar->appendButton('Standard', 'envelope', Languages::_('ORGANIZER_NOTIFY'), '', false);
            $toolbar->appendButton('NewTab', 'file-pdf', Languages::_('Download as PDF'), 'ContactTracking.pdf', false);
        }
    }

    /**
     * @inheritdoc
     */
    protected function authorize()
    {
        if (!Helpers\Users::getID()) {
            Helpers\OrganizerHelper::error(401);
        }

        if (!Helpers\Can::traceContacts()) {
            Helpers\OrganizerHelper::error(403);
        }
    }

    /**
     * @inheritDoc
     */
    public function display($tpl = null)
    {
        $filterItems = Helpers\Input::getFilterItems();

        // If a query string was entered feedback is a part of a system message.
        if ($filterItems->get('search')) {
            $this->empty = ' ';
        } else {
            $this->empty = Languages::_('ORGANIZER_ENTER_SEARCH_TERM');
        }

        parent::display($tpl);
    }

    /**
     * @inheritdoc
     */
    public function setHeaders()
    {
        $listFormat = (int)Helpers\Input::getListItems()->get('listFormat', self::BY_DAY);
        $headers    = [
            'index'  => '#',
            'person' => Languages::_('ORGANIZER_PERSON'),
            'data'   => Languages::_('ORGANIZER_CONTACT_INFORMATION')
        ];

        switch ($listFormat) {
            case self::BY_EVENT:
                $otherHeaders = ['contacts' => Languages::_('ORGANIZER_CONTACTS')];
                break;
            case self::BY_DAY:
            default:
                $otherHeaders = ['dates' => Languages::_('ORGANIZER_DATES'), 'length' => Languages::_('ORGANIZER_CONTACT_LENGTH')];
                break;

        }

        $headers       = array_merge($headers, $otherHeaders);
        $this->headers = $headers;
    }

    /**
     * @inheritDoc
     */
    protected function setSubtitle()
    {
        $then           = Helpers\Dates::formatDate(date('Y-m-d', strtotime("-28 days")));
        $today          = Helpers\Dates::formatDate(date('Y-m-d'));
        $this->subtitle = Languages::_('ORGANIZER_INTERVAL') . ": $then - $today";
    }

    /**
     * @inheritdoc
     */
    protected function structureItems()
    {
        $index           = 1;
        $link            = '';
        $listFormat      = (int)Helpers\Input::getListItems()->get('listFormat', self::BY_DAY);
        $mText           = Languages::_('ORGANIZER_MINUTES');
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

            $structuredItems[$index] = $this->structureItem($index, $item, $link);
            $index++;
        }

        $this->items = $structuredItems;
    }
}
