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
use THM\Organizer\Helpers\CleaningGroups as Helper;
use THM\Organizer\Layouts\HTML\ListItem;

/**
 * Class loads a filtered set of buildings into the display context.
 */
class CleaningGroups extends ListView
{
    /**
     * @inheritDoc
     */
    protected function addToolBar(): void
    {
        $toolbar = Toolbar::getInstance();
        $toolbar->addNew('CleaningGroups.add');

        switch ($this->state->get('filter.relevant')) {
            case Helper::EXCLUDED:
                $toolbar->standardButton('include', Text::_('INCLUDE'), "CleaningGroups.activate")
                    ->icon('fa fa-check')
                    ->listCheck(true);
                break;
            case Helper::INCLUDED:
                $toolbar->standardButton('exclude', Text::_('EXCLUDE'), "CleaningGroups.deactivate")
                    ->icon('fa fa-times')
                    ->listCheck(true);
                break;
            default:
                $toolbar->standardButton('include', Text::_('INCLUDE'), "CleaningGroups.activate")
                    ->icon('fa fa-check')
                    ->listCheck(true);
                $toolbar->standardButton('exclude', Text::_('EXCLUDE'), "CleaningGroups.deactivate")
                    ->icon('fa fa-times')
                    ->listCheck(true);
                break;
        }

        $toolbar->delete('CleaningGroups.delete')->message(Text::_('DELETE_CONFIRM'))->listCheck(true);
        parent::addToolBar();
    }

    /**
     * @inheritDoc
     */
    protected function completeItem(int $index, stdClass $item, array $options = []): void
    {
        $item->days      = $item->days === '0.00' ? '-' : $item->days;
        $item->relevant  = HTML::toggle($index, Helper::STATES[$item->relevant], 'CleaningGroups');
        $item->valuation = $item->valuation === '0.00' ? '-' : $item->valuation;
    }

    /**
     * @inheritDoc
     */
    public function initializeColumns(): void
    {
        $direction = $this->state->get('list.direction');
        $headers   = [
            'check'     => ['type' => 'check'],
            'name'      => [
                'link'       => ListItem::DIRECT,
                'properties' => ['class' => 'w-10 d-md-table-cell', 'scope' => 'col'],
                'title'      => HTML::sort('NAME', 'name', $direction, 'name'),
                'type'       => 'value'
            ],
            'days'      => [
                'properties' => ['class' => 'w-5 d-md-table-cell', 'scope' => 'col'],
                'title'      => Text::_('CLEANING_DAYS_PER_MONTH'),
                'type'       => 'text'
            ],
            'valuation' => [
                'properties' => ['class' => 'w-5 d-md-table-cell', 'scope' => 'col'],
                'title'      => Text::_('CALCULATED_SURFACE_PERFORMANCE_VALUE'),
                'type'       => 'text'
            ],
            'relevant'  => [
                'properties' => ['class' => 'w-5 d-md-table-cell', 'scope' => 'col'],
                'title'      => Text::_('COST_ACCOUNTING'),
                'type'       => 'value'
            ],
        ];

        $this->headers = $headers;
    }
}
