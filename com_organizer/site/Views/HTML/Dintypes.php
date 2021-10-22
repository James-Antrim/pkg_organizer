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

use Organizer\Helpers;

/**
 * Class loads persistent information a filtered set of room types into the display context.
 */
class Dintypes extends ListView
{
    protected $rowStructure = [
        'checkbox'    => '',
        'din_code'        => 'link',
        'name'        => 'link',
    ];

    /**
     * @inheritdoc
     */
    protected function authorize()
    {
        if (!Helpers\Can::manage('facilities'))
        {
            Helpers\OrganizerHelper::error(403);
        }
    }

    /**
     * @inheritdoc
     */
    public function setHeaders()
    {
        $ordering  = $this->state->get('list.ordering');
        $direction = $this->state->get('list.direction');
        $headers   = [
            'checkbox'    =>  Helpers\HTML::_('grid.checkall'),
            'din_code'        => Helpers\HTML::sort('DIN_CODE', 'din_code', $direction, $ordering),
            'name'        => Helpers\HTML::sort('DIN_DESCRIPTION', 'name', $direction, $ordering),
        ];

        $this->headers = $headers;
    }


}
