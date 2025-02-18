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

use Joomla\CMS\Form\Field\ListField;
use THM\Organizer\Adapters\{Application, HTML, Input, Text};
use THM\Organizer\Helpers;

/** @inheritDoc */
class Programs extends ListField
{
    use Malleable;

    /** @inheritDoc */
    protected function getOptions(): array
    {
        $options = parent::getOptions();

        $participantEdit = (strtolower(str_replace('_', '', Input::getView())) === 'Participant');
        if ($participantEdit and Helpers\Can::administrate()) {
            $options[] = HTML::option(-1, Text::_('UNKNOWN'));
        }

        $access   = Application::backend() ? $this->getAttribute('access', '') : '';
        $programs = Helpers\Programs::options($access);

        return array_merge($options, $programs);
    }
}
