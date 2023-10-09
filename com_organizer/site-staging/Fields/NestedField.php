<?php
/**
 * @package     Organizer
 * @extension   com_organizer
 * @author      James Antrim, <james.antrim@nm.thm.de>
 * @copyright   2021 TH Mittelhessen
 * @license     GNU GPL v.3
 * @link        www.thm.de
 */

namespace Organizer\Fields;

require_once JPATH_ROOT . '/libraries/joomla/form/fields/subform.php';

use JFormFieldSubform;

class NestedField extends JFormFieldSubform
{
    use Translated;

    /**
     * @var  string
     */
    protected $type = 'Nested';
}