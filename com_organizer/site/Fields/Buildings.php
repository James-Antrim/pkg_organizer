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
use THM\Organizer\Helpers;

/** @inheritDoc */
class Buildings extends ListField
{
    /** @inheritDoc */
    protected function getOptions(): array
    {
        $defaultOptions = parent::getOptions();
        $options        = Helpers\Buildings::options();

        return array_merge($defaultOptions, $options);
    }
}
