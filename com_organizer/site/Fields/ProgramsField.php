<?php
/**
 * @package     Organizer
 * @extension   com_organizer
 * @author      James Antrim, <james.antrim@nm.thm.de>
 * @copyright   2020 TH Mittelhessen
 * @license     GNU GPL v.3
 * @link        www.thm.de
 */

namespace THM\Organizer\Fields;

use THM\Organizer\Adapters\{Application, HTML, Input, Text};
use THM\Organizer\Helpers;

/**
 * Class creates a select box for (degree) programs.
 */
class ProgramsField extends OptionsField
{
    /**
     * @var  string
     */
    protected $type = 'Programs';

    /**
     * Method to get the field options.
     * @return  array  The field option objects.
     */
    protected function getOptions(): array
    {
        $options = parent::getOptions();

        $participantEdit = (strtolower(str_replace('_', '', Input::getView())) === 'participantedit');
        if ($participantEdit and Helpers\Can::administrate()) {
            $options[] = HTML::option(-1, Text::_('ORGANIZER_UNKNOWN'));
        }

        $access   = Application::backend() ? $this->getAttribute('access', '') : '';
        $programs = Helpers\Programs::getOptions($access);

        return array_merge($options, $programs);
    }
}
