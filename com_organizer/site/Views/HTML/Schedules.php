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

use THM\Organizer\Adapters\{HTML, Text, Toolbar};
use THM\Organizer\Helpers\{Can, Dates};

/**
 * Class loads persistent information a filtered set of schedules into the display context.
 */
class Schedules extends ListView
{
    protected array $rowStructure = [
        'checkbox' => '',
        'organizationName' => 'value',
        'termName' => 'value',
        'userName' => 'value',
        'created' => 'value'
    ];

    /**
     * @inheritdoc
     */
    protected function addToolBar(): void
    {
        $toolbar = Toolbar::getInstance();
        $toolbar::setTitle('ORGANIZER_SCHEDULES');
        $admin   = Can::administrate();
        $toolbar = Toolbar::getInstance();

        $toolbar->standardButton('upload', Text::_('ORGANIZER_UPLOAD'), 'Schedules.edit')->icon('fa fa-upload');

        if ($this->state->get('filter.organizationID') and $this->state->get('filter.termID')) {
            /*$toolbar->standardButton('envelope', Text::_('ORGANIZER_NOTIFY_CHANGES'), 'schedules.notify', true);*/

            $toolbar->confirmButton('reference', Text::_('REFERENCE_CONFIRM'), 'Schedules.reference')->icon('fa fa-share');

            if ($admin) {
                $toolbar->standardButton('rebuild', Text::_('REBUILD'), 'Schedules.rebuild')->icon('fa fa-sync');
                $toolbar->delete('Schedules.delete', Text::_('DELETE'))->message(Text::_('DELETE_CONFIRM'));
            }
        }

        parent::addToolBar();
    }

    /**
     * @inheritdoc
     */
    public function setHeaders(): void
    {
        $headers = [
            'checkbox' => HTML::_('grid.checkall'),
            'organizationName' => Text::_('ORGANIZER_ORGANIZATION'),
            'termName' => Text::_('ORGANIZER_TERM'),
            'userName' => Text::_('ORGANIZER_USERNAME'),
            'created' => Text::_('ORGANIZER_CREATION_DATE')
        ];

        $this->headers = $headers;
    }

    /**
     * @inheritdoc
     */
    protected function structureItems(): void
    {
        $index           = 0;
        $structuredItems = [];

        foreach ($this->items as $item) {
            $creationDate  = Dates::formatDate($item->creationDate);
            $creationTime  = Dates::formatTime($item->creationTime);
            $item->created = "$creationDate / $creationTime";

            $structuredItems[$index] = $this->structureItem($index, $item);
            $index++;
        }

        $this->items = $structuredItems;
    }
}
