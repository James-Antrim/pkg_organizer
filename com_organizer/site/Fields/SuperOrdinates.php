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
use THM\Organizer\Adapters\{Form, Input};
use THM\Organizer\Helpers\{Pools, Subjects};

/**
 * Class creates a select box for superordinate pool resources.
 */
class SuperOrdinates extends ListField
{
    /**
     * Gets available superordinate options.
     * @return string[]
     */
    protected function getOptions(): array
    {
        /** @var Form $form */
        $form       = $this->form;
        $resource   = $form->view();
        $resourceID = Input::getID();

        // Initial program ranges are dependent on existing ranges.
        $ranges = $resource === 'pool' ? Pools::programs($resourceID) : Subjects::programs($resourceID);

        return Pools::superOptions($resource, $ranges);
    }
}
