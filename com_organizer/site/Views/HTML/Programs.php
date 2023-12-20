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
use THM\Organizer\Helpers\{Can, Programs as Helper};
use THM\Organizer\Layouts\HTML\ListItem;

/**
 * Class loads persistent information a filtered set of degree programs into the display context.
 */
class Programs extends ListView
{
    use Activated;

    private bool $documentAccess = false;

    /**
     * @inheritDoc
     */
    protected function addToolBar(bool $delete = true): void
    {
        if (Can::documentTheseOrganizations()) {
            $toolbar = Toolbar::getInstance();

            $toolbar->addNew('Programs.add');
            $toolbar->standardButton('upload', Text::_('IMPORT_LSF'), 'Programs.import')->listCheck(true)->icon('fa fa-upload');
            $toolbar->standardButton('update', Text::_('UPDATE_SUBJECTS'), 'Programs.update')
                ->listCheck(true)->icon('fa fa-sync');

            if (Can::administrate()) {
                $toolbar->delete('Programs.delete')->message(Text::_('DELETE_CONFIRM'))->listCheck(true);
            }

            // No implicit basis in scheduling to deactivate programs.
            $this->addActa();
        }

        parent::addToolBar();
    }

    /**
     * @inheritDoc
     */
    protected function completeItem(int $index, stdClass $item, array $options = []): void
    {
        if (Application::backend()) {
            $item->active = HTML::toggle($index, Helper::activeStates[$item->active], 'Programs');
        }
        else {
            $item->links = '';

            foreach ($options['links'] as $view => $icon) {
                $context     = strtolower($view) . "-$item->id";
                $tip         = strtoupper($view);
                $url         = "index.php?option=com_organizer&view=$view&programID=$item->id";
                $item->links .= HTML::tip($icon, $context, $tip, [], $url, true);
            }
        }
    }

    /**
     * @inheritDoc
     */
    protected function completeItems(array $options = []): void
    {
        $options['links'] = [
            'Curriculum' => HTML::icon('fa fa-th'),
            'Subjects'   => HTML::icon('fa fa-list'),
        ];

        parent::completeItems($options);
    }

    /**
     * @inheritDoc
     */
    public function initializeColumns(): void
    {
        $direction = $this->state->get('list.direction');

        $headers = [
            'check' => ['type' => 'check'],
            'name'  => [
                'link'       => ListItem::DIRECT,
                'properties' => ['class' => 'w-10 d-md-table-cell', 'scope' => 'col'],
                'title'      => HTML::sort('NAME', 'name', $direction, 'name'),
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
