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

use Joomla\CMS\Form\Form;
use Joomla\CMS\Form\Field\SubformField as Core;
use THM\Organizer\Adapters\Form as Adapter;

class SubForm extends Core
{
    /** @inheritDoc */
    public function loadSubForm(): Adapter
    {
        $control  = $this->name . '[' . $this->fieldname . 'X]';
        $formname = 'subform.' . str_replace(['jform[', '[', ']'], ['', '.', ''], $this->name);

        return Adapter::getInstance($formname, $this->formsource, ['control' => $control]);
    }

    /** @inheritDoc */
    protected function loadSubFormData(Form $subForm): array
    {
        $forms = [];
        $value = $this->value ? (array) $this->value : [];
        $value = array_values($value);

        for ($i = 0; $i < count($value); $i++) {
            $control  = $this->name . '[' . $this->fieldname . $i . ']';
            $itemForm = Adapter::getInstance($subForm->getName() . $i, $this->formsource, ['control' => $control]);

            if (!empty($value[$i])) {
                $itemForm->bind($value[$i]);
            }

            $forms[] = $itemForm;
        }

        return $forms;
    }
}