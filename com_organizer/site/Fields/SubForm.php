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

use THM\Organizer\Adapters\FileLayout;
use Joomla\CMS\Form\Field\SubformField as Core;

class SubForm extends Core
{
    /** @inheritDoc */
    protected function getLayoutPaths(): array
    {
        $paths   = parent::getLayoutPaths();
        $paths[] = JPATH_ROOT . '/com_organizer/Layouts/HTML';
        return $paths;
    }

    /** @inheritDoc */
    protected function getRenderer($layoutId = 'default'): FileLayout
    {
        $renderer = new FileLayout($layoutId);

        $renderer->setDebug($this->isDebugEnabled());

        $layoutPaths = $this->getLayoutPaths();

        if ($layoutPaths) {
            $renderer->setIncludePaths($layoutPaths);
        }

        return $renderer;
    }
}