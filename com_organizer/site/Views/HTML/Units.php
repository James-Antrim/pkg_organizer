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

use Organizer\Adapters\Toolbar;
use Organizer\Helpers;
use Organizer\Helpers\Languages;

/**
 * Class which loads data into the view output context
 */
class Units extends ListView
{
    private $statusDate;

    /**
     * @inheritdoc
     */
    public function __construct($config = [])
    {
        parent::__construct($config);
        $this->statusDate = date('Y-m-d H:i:s', strtotime('-14 days'));
    }

    /**
     * @inheritdoc
     */
    protected function authorize()
    {
        if (!Helpers\Can::scheduleTheseOrganizations()) {
            Helpers\OrganizerHelper::error(403);
        }
    }

    /**
     * @inheritdoc
     */
    protected function addToolBar(bool $delete = true)
    {
        $this->setTitle('ORGANIZER_UNITS');
        $toolbar = Toolbar::getInstance();

        $toolbar->appendButton(
            'Standard',
            'plus',
            Helpers\Languages::_('ORGANIZER_ADD_COURSE'),
            "units.addCourse",
            true
        );

        /*if (Helpers\Can::administrate())
        {
            $toolbar->appendButton('Standard', 'edit', Helpers\Languages::_('ORGANIZER_EDIT'), "units.edit", true);
            $toolbar->appendButton(
                'Confirm',
                Helpers\Languages::_('ORGANIZER_DELETE_CONFIRM'),
                'delete',
                Helpers\Languages::_('ORGANIZER_DELETE'),
                "units.delete",
                true
            );
        }*/
    }

    /**
     * Created a structure for displaying status information as necessary.
     *
     * @param object $item the instance item being iterated
     *
     * @return array
     */
    private function getStatus(object $item): array
    {
        $class = 'status-display hasToolTip';
        $title = '';

        // If removed are here at all, the status holds relevance regardless of date
        if ($item->status === 'removed') {
            $date  = Helpers\Dates::formatDate($item->modified);
            $class .= ' unit-removed';
            $title = sprintf(Languages::_('ORGANIZER_UNIT_REMOVED_ON'), $date);
        } elseif ($item->status === 'new' and $item->modified >= $this->statusDate) {
            $date  = Helpers\Dates::formatDate($item->modified);
            $class .= ' unit-new';
            $title = sprintf(Languages::_('ORGANIZER_UNIT_ADDED_ON'), $date);

        }

        return $title ? ['attributes' => ['class' => $class, 'title' => $title], 'value' => ''] : ['value' => ''];
    }

    /**
     * @inheritdoc
     */
    public function setHeaders()
    {
        $headers = [
            'checkbox' => Helpers\HTML::_('grid.checkall'),
            'status' => '',
            'name' => Languages::_('ORGANIZER_NAME'),
            'method' => Languages::_('ORGANIZER_METHOD'),
            'dates' => Languages::_('ORGANIZER_DATES'),
            'grid' => Languages::_('ORGANIZER_GRID'),
            'code' => Languages::_('ORGANIZER_UNTIS_ID'),
            //'run'      => Languages::_('ORGANIZER_RUN')
        ];

        $this->headers = $headers;
    }

    /**
     * @inheritdoc
     */
    protected function structureItems()
    {
        $index           = 0;
        $structuredItems = [];

        foreach ($this->items as $item) {
            $endDate   = Helpers\Dates::formatDate($item->endDate);
            $startDate = Helpers\Dates::formatDate($item->startDate);

            $structuredItems[$index]             = [];
            $structuredItems[$index]['checkbox'] = Helpers\HTML::_('grid.id', $index, $item->id);
            $structuredItems[$index]['status']   = $this->getStatus($item);
            $structuredItems[$index]['name']     = $item->name;
            $structuredItems[$index]['method']   = $item->method;
            $structuredItems[$index]['dates']    = "$startDate - $endDate";
            $structuredItems[$index]['grid']     = $item->grid;
            $structuredItems[$index]['code']     = $item->code;

            $index++;
        }

        $this->items = $structuredItems;
    }
}
