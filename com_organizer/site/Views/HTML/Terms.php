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

use Organizer\Helpers\Dates;
use Organizer\Helpers\Languages;

/**
 * Class loads persistent information a filtered set of degrees into the display context.
 */
class Terms extends ListView
{
    protected $rowStructure = ['checkbox' => '', 'term' => 'link', 'startDate' => 'link', 'endDate' => 'link'];

    /**
     * @inheritdoc
     */
    public function setHeaders()
    {
        $headers = [
            'checkbox' => '',
            'term' => Languages::_('ORGANIZER_NAME'),
            'startDate' => Languages::_('ORGANIZER_START_DATE'),
            'endDate' => Languages::_('ORGANIZER_END_DATE')
        ];

        $this->headers = $headers;
    }

    /**
     * @inheritDoc
     */
    protected function structureItems()
    {
        $link            = "index.php?option=com_organizer&view=term_edit&id=";
        $index           = 0;
        $structuredItems = [];

        foreach ($this->items as $item) {
            $thisLink = empty($item->link) ? $link . $item->id : $item->link;

            $item->endDate   = Dates::formatDate($item->endDate);
            $item->startDate = Dates::formatDate($item->startDate);

            $structuredItems[$index] = $this->structureItem($index, $item, $thisLink);
            $index++;
        }

        $this->items = $structuredItems;
    }
}
