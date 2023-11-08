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

use THM\Organizer\Adapters\{Application, HTML, Text, Toolbar};
use THM\Organizer\Helpers;

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
    protected function addToolBar(bool $delete = true): void
    {
        $toolbar = Toolbar::getInstance();

        $toolbar->addNew('Course.add', Text::_('ADD_COURSE'))->listCheck(true)->icon('fa fa-plus');

        parent::addToolBar();
    }

    /**
     * @inheritdoc
     */
    protected function completeItems(): void
    {
        $index           = 0;
        $structuredItems = [];

        foreach ($this->items as $item) {
            $endDate   = Helpers\Dates::formatDate($item->endDate);
            $startDate = Helpers\Dates::formatDate($item->startDate);

            $structuredItems[$index]             = [];
            $structuredItems[$index]['checkbox'] = HTML::checkBox($index, $item->id);
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

    /**
     * Created a structure for displaying status information as necessary.
     *
     * @param   object  $item  the instance item being iterated
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
            $title = Text::sprintf('ORGANIZER_UNIT_REMOVED_ON', $date);
        }
        elseif ($item->status === 'new' and $item->modified >= $this->statusDate) {
            $date  = Helpers\Dates::formatDate($item->modified);
            $class .= ' unit-new';
            $title = Text::sprintf('ORGANIZER_UNIT_ADDED_ON', $date);

        }

        return $title ? ['attributes' => ['class' => $class, 'title' => $title], 'value' => ''] : ['value' => ''];
    }

    /**
     * @inheritdoc
     */
    public function initializeColumns(): void
    {
        $headers = [
            'checkbox' => HTML::checkAll(),
            'status'   => '',
            'name'     => Text::_('ORGANIZER_NAME'),
            'method'   => Text::_('ORGANIZER_METHOD'),
            'dates'    => Text::_('ORGANIZER_DATES'),
            'grid'     => Text::_('ORGANIZER_GRID'),
            'code'     => Text::_('ORGANIZER_UNTIS_ID'),
            //'run'      => Text::_('ORGANIZER_RUN')
        ];

        $this->headers = $headers;
    }
}
