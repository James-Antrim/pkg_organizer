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
use THM\Organizer\Helpers\{Programs, Subjects};

/**
 * Class creates a select box for superordinate pool resources.
 */
class Prerequisites extends ListField
{
    /**
     * @inheritDoc
     */
    protected function getOptions(): array
    {
        $options = [0 => HTML::option(-1, Text::_('NO_PREREQUISITES'))];

        if (!$subjectID = Input::getID()) {
            return $options;
        }

        foreach (Subjects::programs($subjectID) as $pRange) {
            foreach (Programs::subjects($pRange['programID']) as $sRange) {
                $value = $sRange['subjectID'];

                if ($value === $subjectID or !$text = Subjects::getFullName($value)) {
                    continue;
                }

                $options[$text] = HTML::option($value, $text);
            }
        }

        ksort($options);

        return $options;
    }
}
