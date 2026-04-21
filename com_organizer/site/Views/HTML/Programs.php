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

use THM\Organizer\Adapters\{Application, HTML, Text, Toolbar};
use stdClass;
use THM\Organizer\Helpers\{Can, Organizations, Programs as Helper};
use THM\Organizer\Layouts\HTML\Row;

/** @inheritDoc */
class Programs extends ListView
{
    use Activated;

    /** @inheritDoc */
    protected function addToolBar(bool $delete = true): void
    {
        $this->toDo[] = 'Add a no category option.';

        if (Organizations::documentableIDs()) {
            $this->addAdd();

            $toolbar = Toolbar::instance();
            $toolbar->standardButton('upload', Text::_('IMPORT_HI1'), 'Programs.import')->listCheck(true)->icon('fa fa-upload');
            $toolbar->standardButton('update', Text::_('UPDATE_SUBJECTS'), 'Programs.update')
                ->listCheck(true)->icon('fa fa-sync');

            if (Can::administrate()) {
                $toolbar->addNew('programs.import', Text::_('IMPORT'))->icon('fa fa-upload');
                $this->addDelete();
            }

            // No implicit basis in scheduling to deactivate programs.
            $this->addActa();
        }

        parent::addToolBar();
    }

    /** @inheritDoc */
    protected function completeItem(int $index, stdClass $item, array $options = []): void
    {
        $item->fullName = Helper::fullName($item);

        $item->active    = HTML::toggle($index, Helper::ACTIVE_STATES[$item->active], 'Programs');
        $item->campus    = $item->campus ?? '-';
        $item->focus     = $item->focus ?? '-';
        $item->frequency = $item->frequency ?? '-';
        $item->links     = '';
        $item->minor     = $item->minor ?? '-';

        foreach ($options['links'] as $view => $icon) {
            $context     = strtolower($view) . "-$item->id";
            $tip         = strtoupper($view);
            $url         = "index.php?option=com_organizer&view=$view&programID=$item->id";
            $item->links .= HTML::tip($icon, $context, $tip, [], $url, true);
        }
        //echo "<pre>" . print_r($item, true) . "</pre>";
    }

    /** @inheritDoc */
    protected function completeItems(array $options = []): void
    {
        $options['links'] = [
            'curriculum' => HTML::icon('fa fa-th'),
            'subjects'   => HTML::icon('fa fa-list'),
        ];

        parent::completeItems($options);
    }

    /** @inheritDoc */
    public function initializeColumns(): void
    {
        $headers = [
            'check'    => ['type' => 'check'],
            'fullName' => [
                'link'       => Row::DIRECT,
                'properties' => ['class' => 'w-10 d-md-table-cell', 'scope' => 'col'],
                'title'      => Text::_('Name'),
                'type'       => 'text'
            ],
            'campus'   => [
                'properties' => ['class' => 'w-5 d-md-table-cell', 'scope' => 'col'],
                'title'      => Text::_('CAMPUS'),
                'type'       => 'text'
            ],
        ];

        if (!Application::backend()) {
            $headers['links'] = [
                'properties' => ['class' => 'w-5 d-md-table-cell', 'scope' => 'col'],
                'title'      => '',
                'type'       => 'value'
            ];
        }
        else {
            $headers['active'] = [
                'properties' => ['class' => 'w-5 d-md-table-cell', 'scope' => 'col'],
                'title'      => Text::_('ACTIVE'),
                'type'       => 'value'
            ];
        }

        $this->headers = $headers;
    }
}
