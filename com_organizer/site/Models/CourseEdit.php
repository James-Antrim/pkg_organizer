<?php
/**
 * @package     Organizer
 * @extension   com_organizer
 * @author      James Antrim, <james.antrim@nm.thm.de>
 * @copyright   2020 TH Mittelhessen
 * @license     GNU GPL v.3
 * @link        www.thm.de
 */

namespace THM\Organizer\Models;

use THM\Organizer\Adapters\{Application, Text};
use THM\Organizer\Helpers\{Can, Courses, Terms};
use THM\Organizer\Tables;

/**
 * Class loads forms for managing basic course attributes.
 */
class CourseEdit extends EditModel
{
    /**
     * Checks access to edit the resource.
     * @return void
     */
    protected function authorize()
    {
        if (!Can::coordinate('course', (int) $this->item->id)) {
            Application::error(403);
        }
    }

    /**
     * @inheritDoc
     */
    public function getForm($data = [], $loadData = true)
    {
        $form = parent::getForm($data, $loadData);

        if (empty($this->item->id)) {
            $form->removeField('campusID');
        }

        return empty($form) ? false : $form;
    }

    /**
     * Method to get a single record.
     *
     * @param   int  $pk  The id of the primary key.
     *
     * @return mixed    Object on success, false on failure.
     */
    public function getItem($pk = 0)
    {
        $this->item = parent::getItem($pk);

        if (empty($this->item->id)) {
            $this->item->name   = Text::_('ORGANIZER_NONE');
            $this->item->termID = Terms::getNextID();
        }
        else {
            $this->item->name = Courses::getName($this->item->id);
        }

        return $this->item;
    }

    /**
     * Method to get a table object, load it if necessary.
     *
     * @param   string  $name     The table name. Optional.
     * @param   string  $prefix   The class prefix. Optional.
     * @param   array   $options  Configuration array for model. Optional.
     *
     * @return Tables\Courses A Table object
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function getTable($name = '', $prefix = '', $options = []): Tables\Courses
    {
        return new Tables\Courses();
    }
}
