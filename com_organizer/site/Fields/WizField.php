<?php
/**
 * @package     Organizer
 * @extension   com_organizer
 * @author      James Antrim, <james.antrim@nm.thm.de>
 * @copyright   2020 TH Mittelhessen
 * @license     GNU GPL v.3
 * @link        www.thm.de
 */

namespace Organizer\Fields;

use Joomla\CMS\Form\Field\EditorField;

/**
 * Class creates text input.
 */
class WizField extends EditorField
{
    use Translated;

    /**
     * The form field type.
     * @var    string
     */
    public $type = 'Wiz';
}
