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
use THM\Organizer\Helpers\RoomKeys as Helper;

/** @inheritDoc */
class RoomKeys extends ListField
{
    /** @inheritDoc */
    protected function getOptions(): array
    {
        return array_merge(parent::getOptions(), Helper::options());
    }
}
