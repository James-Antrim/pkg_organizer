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

use THM\Organizer\Adapters\{Application, Input};
use THM\Organizer\Helpers\Pools as Helper;

/** @inheritDoc */
class Pool extends CurriculumResource
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
        $data['programIDs'] = Input::resourceIDs('programIDs');
        $data['organizationIDs'] = Input::resourceIDs('organizationIDs');
        $data['superordinates'] = Input::resourceIDs('superordinates');
        $data['subordinates'] = Helper::subordinates();

        $this->validate($data, ['abbreviation_de', 'abbreviation_en', 'fullName_de', 'fullName_en', 'organizationIDs']);

        return $data;
    }

    /** @inheritDoc */
    public function postProcess(): void
    {
        Helper::updateSuperOrdinates($this->data);
    }
}