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

use THM\Organizer\Adapters\{Text, Toolbar};
use stdClass;
use THM\Organizer\Helpers;
use THM\Organizer\Layouts\HTML\ListItem;

/**
 * Class loads persistent information a filtered set of runs into the display context.
 */
class Runs extends ListView
{
    protected array $rowStructure = [
        'checkbox'  => '',
        'name'      => 'link',
        'term'      => 'link',
        'startDate' => 'link',
        'endDate'   => 'link',
        'sections'  => 'value'
    ];

    /**
     * @inheritDoc
     */
    protected function addToolBar(bool $delete = true): void
    {
        $toolbar = Toolbar::getInstance();
        $toolbar->addNew('Run.add');
        $toolbar->delete('Runs.delete')->message(Text::_('DELETE_CONFIRM'));
        parent::addToolBar();
    }

    /**
     * @inheritDoc
     */
    protected function completeItem(int $index, stdClass $item, array $options = []): void
    {
        $item->editLink = $options['query'] . $item->id;
        $run            = json_decode($item->run, true);

        if (empty($run) or empty($run['runs'])) {
            $item->endDate   = '';
            $item->sections  = '';
            $item->startDate = '';
        }
        else {
            $runs      = $run['runs'];
            $sections  = [];
            $startDate = '';

            foreach ($runs as $run) {
                $startDate = (!$startDate or $startDate > $run['startDate']) ? $run['startDate'] : $startDate;
            }

            ksort($sections);

            $item->endDate   = Helpers\Dates::formatDate($item->endDate);
            $item->sections  = count($runs);
            $item->startDate = Helpers\Dates::formatDate($startDate);
        }
    }

    /**
     * @param   array  $options  *
     *
     * @inheritDoc
     */
    protected function completeItems(array $options = []): void
    {
        $options = ['query' => "index.php?option=com_organizer&view=run_edit&id="];
        parent::completeItems($options);
    }

    /**
     * @inheritDoc
     */
    public function initializeColumns(): void
    {
        $this->headers = [
            'check'     => ['type' => 'check'],
            'name'      => [
                'link'       => ListItem::DIRECT,
                'properties' => ['class' => 'w-10 d-md-table-cell', 'scope' => 'col'],
                'title'      => Text::_('NAME'),
                'type'       => 'value'
            ],
            'term'      => [
                'properties' => ['class' => 'w-10 d-md-table-cell', 'scope' => 'col'],
                'title'      => Text::_('TERM'),
                'type'       => 'text'
            ],
            'startDate' => [
                'properties' => ['class' => 'w-5 d-md-table-cell', 'scope' => 'col'],
                'title'      => Text::_('START_DATE'),
                'type'       => 'text'
            ],
            'endDate'   => [
                'properties' => ['class' => 'w-5 d-md-table-cell', 'scope' => 'col'],
                'title'      => Text::_('END_DATE'),
                'type'       => 'text'
            ],
            'sections'  => [
                'properties' => ['class' => 'w-5 d-md-table-cell', 'scope' => 'col'],
                'title'      => Text::_('SECTIONS'),
                'type'       => 'text'
            ],
        ];
    }
}
