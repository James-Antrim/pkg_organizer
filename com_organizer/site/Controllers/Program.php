<?php
/**
 * @package     Organizer
 * @extension   pkg_organizer
 * @author      James Antrim, <james.antrim@nm.thm.de>
 * @copyright   2023 TH Mittelhessen
 * @license     GNU GPL v.3
 * @link        www.thm.de
 */

namespace THM\Organizer\Controllers;

use THM\Organizer\Adapters\{Application, Input, Text};
use THM\Organizer\Helpers\{Can, Categories, Curricula, Documentable, HISinOne, Organizations as OHelper, Programs as Helper};
use THM\Organizer\Tables\Programs as Table;

/** @inheritDoc */
class Program extends CurriculumResource
{
    use Activated;

    /** @inheritDoc */
    protected function authorize(): void
    {
        if (Can::administrate()) {
            return;
        }

        /** @var Documentable $helper */
        $helper = "THM\\Organizer\\Helpers\\" . $this->list;
        $id     = Input::id();

        // Existing over document access, new explicitly over document access and implicitly over schedule access.
        if ($id ? !$helper::documentable($id) : !(OHelper::documentableIDs() or OHelper::schedulableIDs())) {
            Application::error(403);
        }
    }

    /**
     * Creates a program stub from imported category data
     *
     * @param array $data       key value pairs for old format entries nomen code, degree code, year of accreditation
     * @param int   $categoryID the id of the calling category to associate the program with
     *
     * @return void
     */
    public function fromSchedule(array $data, int $categoryID): void
    {
        if (!Categories::schedulable($categoryID)) {
            Application::error(403);
        }

        $table = $this->getTable();

        if ($table->load($data)) {
            return;
        }

        $data['categoryID']      = $categoryID;
        $data['organizationIDs'] = [Input::integer('organizationID')];
        $data['subordinates']    = [];
        $this->data              = $data;

        /** @var Table $table */
        $table = $this->getTable();

        if (!$this->data['id'] = $this->store($table, $data)) {
            return;
        }

        $this->updateAssociations();
        $this->postProcess();
    }

    /** @inheritDoc */
    protected function import(int $resourceID = 0): bool
    {
        if (!$resourceID) {
            return false;
        }

        $client = new HISinOne();

        if (!$HISinOneID = Helper::HISinOneID($resourceID)) {
            Application::message('HIO_DATA_MISSING', Application::WARNING);
            return false;
        }

        if (!$program = $client->program($HISinOneID)) {
            Application::message(Text::sprintf('HIO_PROGRAM_DATA_INCONSISTENT', $HISinOneID, $resourceID), Application::WARNING);
            return false;
        }

        if (!$program = Helper::filterPrograms($program)) {
            Application::message('HIO_STRUCTURE_INVALID', Application::ERROR);
            return false;
        }

        return Helper::importSingle($program);
    }

    /** @inheritDoc */
    protected function prepareData(): array
    {
        $data = parent::prepareData();

        /**
         * External references are not in the table and as such won't be automatically prepared. Subordinates are picked up
         * individually during further processing.
         * @see Curricula::addSubordinate(), Curricula::subordinates()
         */
        $data['organizationIDs'] = Input::resourceIDs('organizationIDs');
        $data['subordinates']    = Helper::subordinates();

        $this->validate($data, ['accredited', 'aTypeID', 'campusID', 'degreeID', 'expiration', 'formID', 'nomenID', 'organizationIDs', 'typeID']);

        return $data;
    }

    /** @inheritDoc */
    public function postProcess(): void
    {
        $range = [
            'parentID' => null,
            'programID' => $this->data['id'],
            'curriculum' => $this->data['subordinates'],
            'ordering' => 0
        ];

        if (!Helper::addRange($range)) {
            Application::message('UPDATE_CURRICULUM_FAILED', Application::WARNING);
        }
    }
}