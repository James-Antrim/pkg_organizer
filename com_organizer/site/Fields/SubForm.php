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
use THM\Organizer\Adapters\Form;

class SubForm extends Core
{
    /**
     * Loads the form instance for the subform.
     *
     * @return  Form
     */
    public function loadSubForm(): Form
    {
        $control = $this->name;

        if ($this->multiple) {
            $control .= '[' . $this->fieldname . 'X]';
        }

        $formname = 'subform.' . str_replace(['jform[', '[', ']'], ['', '.', ''], $this->name);

        return Form::getInstance($formname, $this->formsource, ['control' => $control]);
    }
}