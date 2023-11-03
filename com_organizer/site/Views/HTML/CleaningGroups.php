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

use THM\Organizer\Adapters\{Application, HTML, Text};
use THM\Organizer\Helpers;

/**
 * Class loads a filtered set of buildings into the display context.
 */
class CleaningGroups extends ListView
{
    protected array $rowStructure = [
        'checkbox'  => '',
        'name'      => 'link',
        'days'      => 'link',
        'valuation' => 'link',
        'relevant'  => 'value'
    ];

    /**
     * @inheritdoc
     */
    protected function authorize(): void
    {
        if (!Helpers\Can::manage('facilities')) {
            Application::error(403);
        }
    }

    /**
     * @inheritdoc
     */
    protected function completeItems(): void
    {
        $link            = 'index.php?option=com_organizer&view=CleaningGroupEdit&id=';
        $index           = 0;
        $structuredItems = [];

        foreach ($this->items as $item) {
            $item->days      = $item->days === '0.00' ? '-' : $item->days;
            $item->valuation = $item->valuation === '0.00' ? '-' : $item->valuation;

            $tip = 'ORGANIZER_CLICK_TO_MARK_';
            $tip .= $item->relevant ? 'IRRELEVANT' : 'RELEVANT';

            $item->relevant = $this->getToggle('CleaningGroups', $item->id, $item->relevant, $tip);

            $structuredItems[$index] = $this->completeItem($index, $item, $link . $item->id);
            $index++;
        }

        $this->items = $structuredItems;
    }

    /**
     * @inheritdoc
     */
    public function initializeColumns(): void
    {
        $direction = $this->state->get('list.direction');
        $headers   = [
            'checkbox'  => '',
            'name'      => HTML::sort('NAME', 'name', $direction, 'name'),
            'days'      => Text::_('CLEANING_DAYS_PER_MONTH'),
            'valuation' => Text::_('CALCULATED_SURFACE_PERFORMANCE_VALUE'),
            'relevant'  => Text::_('COST_ACCOUNTING')
        ];

        $this->headers = $headers;
    }
}
