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

use THM\Organizer\Adapters\Text;
use THM\Organizer\Helpers\Dates;

/**
 * Class loads persistent information a filtered set of degrees into the display context.
 */
class Terms extends ListView
{
    protected array $rowStructure = ['checkbox' => '', 'term' => 'link', 'startDate' => 'link', 'endDate' => 'link'];

    /**
     * @inheritDoc
     */
    protected function completeItems(): void
    {
        $link            = "index.php?option=com_organizer&view=term_edit&id=";
        $index           = 0;
        $structuredItems = [];

        foreach ($this->items as $item) {
            $thisLink = empty($item->link) ? $link . $item->id : $item->link;

            $item->endDate   = Dates::formatDate($item->endDate);
            $item->startDate = Dates::formatDate($item->startDate);

            $structuredItems[$index] = $this->completeItem($index, $item, $thisLink);
            $index++;
        }

        $this->items = $structuredItems;
    }

    /**
     * @inheritdoc
     */
    public function initializeColumns(): void
    {
        $headers = [
            'checkbox'  => '',
            'term'      => Text::_('ORGANIZER_NAME'),
            'startDate' => Text::_('ORGANIZER_START_DATE'),
            'endDate'   => Text::_('ORGANIZER_END_DATE')
        ];

        $this->headers = $headers;
    }
}
