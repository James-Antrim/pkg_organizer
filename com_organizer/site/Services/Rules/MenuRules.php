<?php
/**
 * @package     Organizer
 * @extension   com_organizer
 * @author      James Antrim, <james.antrim@nm.thm.de>
 * @copyright   2026 TH Mittelhessen
 * @license     GNU GPL v.3
 * @link        www.thm.de
 */

namespace THM\Organizer\Services\Rules;

use Joomla\CMS\Component\Router\Rules\MenuRules as Core;

/**
 * Rule to identify the right Itemid for a view in a component
 */
class MenuRules extends Core
{
    /** @inheritDoc */
    public function build(&$query, &$segments): void
    {
        if (isset($query['view'])) {
            unset($query['view']);
        }
    }

    /** @inheritDoc */
    public function parse(&$segments, &$vars)
    {
        // Nothing to parse on a menu item
    }
}
