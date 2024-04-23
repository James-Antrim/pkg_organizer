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

use SimpleXMLElement;
use THM\Organizer\Adapters\Application;
use THM\Organizer\{Adapters\Input, Tables, Tables\Pools as Table};

/**
 * @inheritDoc
 */
class Pool extends CurriculumResource implements Stubby
{
    protected string $list = 'Pools';

    /**
     * @inheritDoc
     */
    public function import(int $resourceID): bool
    {
        /**
         * This resource is completely inadequately maintained for actual documentation purposes, and is instead used for internal
         * metric validation. The actual data used for basic temporary modeling is delivered with its superordinate program.
         */
        Application::error(501);
        return false;
    }

    /**
     * Prepares the data to be saved.
     * @return array
     */
    protected function prepareData(): array
    {
        $data = parent::prepareData();

        /**
         * External references are not in the table and as such won't be automatically prepared. Subordinates are picked up
         * individually during further processing.
         * @see Ranges::addSubordinate(), Ranges::subordinates()
         */
        $data['programIDs']      = Input::getIntArray('programIDs');
        $data['organizationIDs'] = Input::getIntArray('organizationIDs');
        $data['superordinates']  = Input::getIntArray('superordinates');
        $data['subordinates']  = $this->subordinates();

        $this->validate($data, ['abbreviation_de', 'abbreviation_en', 'fullName_de', 'fullName_en', 'organizationIDs']);

        return $data;
    }

    /**
     * @inheritDoc
     */
    public function postProcess(array $data): void
    {
        if (!$this->updateAssociations('poolID', $data['id'], $data['organizationIDs'])) {
            Application::message('UPDATE_ASSOCIATION_FAILED', Application::WARNING);
        }

        $this->updateSuperOrdinates($data);
    }

    /**
     * @inheritDoc
     */
    public function processStub(SimpleXMLElement $XMLObject, int $organizationID, int $parentID): bool
    {
        if (!$lsfID = empty($XMLObject->pordid) ? (string) $XMLObject->modulid : (string) $XMLObject->pordid) {
            return false;
        }

        $blocked = !empty($XMLObject->sperrmh) and strtolower((string) $XMLObject->sperrmh) === 'x';
        $noChildren = !isset($XMLObject->modulliste->modul);
        $validTitle = $this->validTitle($XMLObject);

        $pool = new Table();

        if (!$pool->load(['lsfID' => $lsfID])) {
            // There isn't one and shouldn't be one
            if ($blocked or !$validTitle or $noChildren) {
                return true;
            }

            $pool->lsfID = $lsfID;
            $this->setNames($pool, $XMLObject);

            if (!$pool->store()) {
                return false;
            }
        }
        // There is one and shouldn't be one
        elseif ($blocked or !$validTitle or $noChildren) {
            return $this->delete($pool->id);
        }

        $this->checkAssociation($organizationID, 'poolID', $pool->id);

        $curriculum = new Tables\Curricula();
        $this->checkCurriculum($curriculum, $parentID, 'poolID', $pool->id);

        return $this->processCollection($XMLObject->modulliste->modul, $organizationID, $curriculum->id);
    }
}