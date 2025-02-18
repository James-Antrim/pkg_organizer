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
use THM\Organizer\{Adapters\Application, Helpers};

/** @inheritDoc */
class Pools extends ListField
{
    /** @inheritDoc */
    protected function getOptions(): array
    {
        $options = parent::getOptions();
        $access  = Application::backend() ? 'document' : '';
        $pools   = Helpers\Pools::options($access);

        return array_merge($options, $pools);
    }
}
