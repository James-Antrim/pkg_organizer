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

use THM\Organizer\Helpers;

/**
 * Class loads persistent information a filtered set of degrees into the display context.
 */
class Degrees extends ListView
{
    protected array $rowStructure = ['checkbox' => '', 'name' => 'link', 'abbreviation' => 'link', 'code' => 'link'];

    /**
     * @inheritdoc
     */
    public function setHeaders(): void
    {
        $ordering  = $this->state->get('list.ordering');
        $direction = $this->state->get('list.direction');
        $headers   = [
            'checkbox' => '',
            'name' => Helpers\HTML::sort('NAME', 'name', $direction, $ordering),
            'abbreviation' => Helpers\HTML::sort('ABBREVIATION', 'abbreviation', $direction, $ordering),
            'code' => Helpers\HTML::sort('DEGREE_CODE', 'code', $direction, $ordering)
        ];

        $this->headers = $headers;
    }
}
