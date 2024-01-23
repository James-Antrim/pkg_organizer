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

use THM\Organizer\Adapters\Input;
use THM\Organizer\Tables\Subjects as Table;

/**
 * Class which manages stored subject data.
 */
class Subject extends CurriculumResource
{
    use SubOrdinate;

    protected string $helper = 'Subjects';

    protected string $resource = 'subject';

    /**
     * @inheritDoc
     */
    public function save(array $data = []): int
    {
        $data = empty($data) ? Input::getFormItems() : $data;

        $this->authorize();

        $data['creditPoints'] = (int) $data['creditPoints'];

        $table = new Table();

        if (!$table->save($data)) {
            return false;
        }

        $data['id'] = $table->id;

        if (!empty($data['organizationIDs']) and !$this->updateAssociations($data['id'], $data['organizationIDs'])) {
            return false;
        }

        if (!$this->assignments($data)) {
            return false;
        }

        $superOrdinates = $this->getSuperOrdinates($data);

        if (!$this->addNew($data, $superOrdinates)) {
            return false;
        }

        $this->removeDeprecated($table->id, $superOrdinates);

        // Dependant on curricula entries.
        if (!$this->processPrerequisites($data['id'], $data['prerequisites'])) {
            return false;
        }

        /*if (!$this->processEvents($data))
        {
            return false;
        }*/

        return $table->id ?: 0;
    }
}
