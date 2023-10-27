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

use THM\Organizer\Adapters\HTML;

/**
 * Class loads persistent information a filtered set of (lesson) methods into the display context.
 */
class Methods extends ListView
{
    protected array $rowStructure = ['checkbox' => '', 'abbreviation' => 'link', 'name' => 'link'];

    /**
     * @inheritdoc
     */
    public function setHeaders(): void
    {
        $ordering  = $this->state->get('list.ordering');
        $direction = $this->state->get('list.direction');
        $headers   = [
            'checkbox'     => '',
            'abbreviation' => HTML::sort('ABBREVIATION', 'abbreviation', $direction, $ordering),
            'name'         => HTML::sort('NAME', 'name', $direction, $ordering)
        ];

        $this->headers = $headers;
    }
}
