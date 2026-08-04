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

use Joomla\CMS\Form\Field\SubformField as Core;

class SubFormField extends Core
{
    /** @inheritDoc */
    protected function getLayoutPaths(): array
    {
        $paths   = parent::getLayoutPaths();
        $paths[] = JPATH_ROOT . '/com_organizer/Layouts/HTML';
        return $paths;
    }
}