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

/** @inheritDoc */
class Pool extends CurriculumResource implements Subordinate
{
    /** @inheritDoc */
    public function import(int $resourceID = 0): bool
    {
        /**
         * This resource is completely inadequately maintained for actual documentation purposes, and is instead used for internal
         * metric validation. The actual data used for basic temporary modeling is delivered with its superordinate program.
         */
        Application::error(501);
        return false;
    }

    /** @inheritDoc */
    protected function prepareData(): array
    {
        $data = parent::prepareData();

        /**
         * External references are not in the table and as such won't be automatically prepared. Subordinates are picked up
         * individually during further processing.
         * @see Helper::addSubordinate(), Helper::subordinates()
         */
        $data['programIDs']      = Input::resourceIDs('programIDs');
        $data['organizationIDs'] = Input::resourceIDs('organizationIDs');
        $data['superordinates']  = Input::resourceIDs('superordinates');
        $data['subordinates']    = Helper::subordinates();

        $this->validate($data, ['abbreviation_de', 'abbreviation_en', 'fullName_de', 'fullName_en', 'organizationIDs']);

        return $data;
    }

    /** @inheritDoc */
    public function postProcess(): void
    {
        Helper::updateSuperOrdinates($this->data);
    }

    /** @inheritDoc */
    public function subordinate(stdClass $XMLObject, int $organizationID, int $parentID, int $programCID): bool
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

            $pool->hi1ID = $lsfID;
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