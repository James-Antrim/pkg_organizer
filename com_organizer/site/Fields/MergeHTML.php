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
use THM\Organizer\Adapters\{HTML, Text};

/** @inheritDoc */
class MergeHTML extends ListField
{
    use Mergeable;

    /** @inheritDoc */
    protected function getOptions(): array
    {
        if (!$this->validateContext()) {
            return [];
        }

        if (!$values = $this->getValues()) {
            return [HTML::option('-1', Text::_('NONE_GIVEN'))];
        }

        $values = array_unique($values);

        foreach ($values as &$value) {
            $value = preg_replace('/ style="[^"]+"/', '', $value);
        }

        return $this->createOptions($values);
    }
}
