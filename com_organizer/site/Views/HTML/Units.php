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

use stdClass;
use THM\Organizer\Adapters\{HTML, Text, Toolbar};
use THM\Organizer\Helpers\{Dates};
use THM\Organizer\Layouts\HTML\Row;

/**
 * Class which loads data into the view output context
 */
class Units extends ListView
{
    /**
     * @inheritDoc
     */
    protected function addToolBar(bool $delete = true): void
    {
        $toolbar = Toolbar::getInstance();

        $toolbar->addNew('units.addCourse', Text::_('ADD_COURSE'))->icon('fa fa-plus')->listCheck(true);

        parent::addToolBar();
    }

    /**
     * @inheritDoc
     */
    protected function completeItem(int $index, stdClass $item, array $options = []): void
    {
        $date      = Dates::formatDate($item->modified);
        $endDate   = Dates::formatDate($item->endDate);
        $startDate = Dates::formatDate($item->startDate);

        $item->dates = "$startDate - $endDate";
        $item->name  = implode('<br>', $item->name);

        // If removed are here at all, the status holds relevance regardless of date
        if ($item->status === 'removed') {
            $icon         = HTML::icon('fa fa-minus');
            $tip          = Text::sprintf('UNIT_REMOVED_ON', $date);
            $item->status = HTML::tip($icon, "status-$item->id", $tip);
        }
        elseif ($item->status === 'new' and $item->modified >= $options['statusDate']) {
            $icon         = HTML::icon('fa fa-plus');
            $tip          = Text::sprintf('UNIT_ADDED_ON', $date);
            $item->status = HTML::tip($icon, "status-$item->id", $tip);
        }
        else {
            $item->status = '';
        }
    }

    /**
     * @inheritDoc
     */
    protected function completeItems(array $options = []): void
    {
        $options['statusDate'] = date('Y-m-d H:i:s', strtotime('-14 days'));
        parent::completeItems($options);
    }

    /**
     * @inheritDoc
     */
    public function initializeColumns(): void
    {
        $this->headers = [
            'check'  => ['type' => 'check'],
            'status' => [
                'properties' => ['class' => 'w-3 d-md-table-cell', 'scope' => 'col'],
                'title'      => '',
                'type'       => 'value'
            ],
            'name'   => [
                'link'       => Row::DIRECT,
                'properties' => ['class' => 'w-10 d-md-table-cell', 'scope' => 'col'],
                'title'      => Text::_('NAME'),
                'type'       => 'text'
            ],
            'method' => [
                'properties' => ['class' => 'w-10 d-md-table-cell', 'scope' => 'col'],
                'title'      => Text::_('METHOD'),
                'type'       => 'text'
            ],
            'dates'  => [
                'properties' => ['class' => 'w-10 d-md-table-cell', 'scope' => 'col'],
                'title'      => Text::_('DATES'),
                'type'       => 'text'
            ],
            'grid'   => [
                'properties' => ['class' => 'w-10 d-md-table-cell', 'scope' => 'col'],
                'title'      => Text::_('GRID'),
                'type'       => 'text'
            ],
            'code'   => [
                'properties' => ['class' => 'w-10 d-md-table-cell', 'scope' => 'col'],
                'title'      => Text::_('UNTIS_ID'),
                'type'       => 'text'
            ],
            /*'run'      => [
                'properties' => ['class' => 'w-10 d-md-table-cell', 'scope' => 'col'],
                'title'      => Text::_('RUN'),
                'type'       => 'text'
            ],*/
        ];
    }
}
