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
use THM\Organizer\Helpers\Degrees;

/** @inheritDoc */
class StatisticCodes extends ListField
{
    /** @inheritDoc */
    protected function getOptions(): array
    {
        $defaultOptions = parent::getOptions();
        $options        = Degrees::statisticOptions();

        return array_merge($defaultOptions, $options);
    }
}
