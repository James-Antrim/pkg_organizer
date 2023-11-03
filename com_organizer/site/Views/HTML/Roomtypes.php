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

use THM\Organizer\Adapters\{Application, HTML};
use THM\Organizer\Helpers;

/**
 * Class loads persistent information a filtered set of room types into the display context.
 */
class Roomtypes extends ListView
{
    protected array $rowStructure = [
        'checkbox' => '',
        'rns'      => 'link',
        'name'     => 'link',
        'useCode'  => 'link'
    ];

    /**
     * @inheritDoc
     */
    protected function addToolBar(bool $delete = false): void
    {
        parent::addToolBar($delete);
    }

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
    public function initializeColumns(): void
    {
        $ordering  = $this->state->get('list.ordering');
        $direction = $this->state->get('list.direction');
        $headers   = [
            'checkbox' => '',
            'rns'      => HTML::sort('ROOMKEY', 'rns', $direction, $ordering),
            'name'     => HTML::sort('NAME', 'name', $direction, $ordering),
            'useCode'  => HTML::sort('USE_CODE_TEXT', 'useCode', $direction, $ordering)
        ];

        $this->headers = $headers;
    }
}
