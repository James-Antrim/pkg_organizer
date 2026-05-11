<?php
/**
 * @package     Organizer
 * @extension   com_organizer
 * @author      James Antrim, <james.antrim@nm.thm.de>
 * @copyright   2026 TH Mittelhessen
 * @license     GNU GPL v.3
 * @link        www.thm.de
 */

namespace THM\Organizer\Fields;

use Joomla\CMS\Form\Field\ListField;
use THM\Organizer\Adapters\{Application, HTML, Text};

/** @inheritDoc */
class Languages extends ListField
{
    /** @inheritDoc */
    protected function getOptions(): array
    {
        $options = parent::getOptions();
        foreach (Application::LANGUAGES as $tag => $language) {
            $options[] = HTML::option($tag, Text::_($language));
        }

        return $options;
    }
}
