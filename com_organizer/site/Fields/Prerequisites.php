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
use THM\Organizer\Adapters\{HTML, Input, Text};
use THM\Organizer\Helpers\Subjects;

/** @inheritDoc */
class Prerequisites extends ListField
{
    /** @inheritDoc */
    protected function getOptions(): array
    {
        $options = [0 => HTML::option(-1, Text::_('NO_PREREQUISITES'))];

        if (!$subjectID = Input::id()) {
            return $options;
        }

        return Subjects::preOptions($subjectID, Subjects::programs($subjectID));
    }
}
