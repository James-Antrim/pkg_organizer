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
use THM\Organizer\Helpers;

/**
 * Class loads persistent information a filtered set of organizations into the display context.
 */
class Organizations extends ListView
{
    protected array $rowStructure = ['checkbox' => '', 'shortName' => 'link', 'name' => 'link'];

    /**
     * @inheritdoc
     */
    protected function addToolBar(bool $delete = true): void
    {
        $this->setTitle('ORGANIZER_ORGANIZATIONS');
        $toolbar = Toolbar::getInstance();
        $toolbar->appendButton('Standard', 'new', Text::_('ORGANIZER_ADD'), 'organizations.add', false);
        $toolbar->appendButton('Standard', 'edit', Text::_('ORGANIZER_EDIT'), 'organizations.edit', true);
        $toolbar->appendButton(
            'Confirm',
            Text::_('ORGANIZER_DELETE_CONFIRM'),
            'delete',
            Text::_('ORGANIZER_DELETE'),
            'organizations.delete',
            true
        );
    }

    /**
     * @inheritdoc
     */
    public function setHeaders(): void
    {
        $ordering  = $this->state->get('list.ordering');
        $direction = $this->state->get('list.direction');
        $headers   = [
            'checkbox' => '',
            'shortName' => Helpers\HTML::sort('SHORT_NAME', 'shortName', $direction, $ordering),
            'name' => Helpers\HTML::sort('NAME', 'name', $direction, $ordering)
        ];

        $this->headers = $headers;
    }
}
