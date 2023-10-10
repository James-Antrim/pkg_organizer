<?php
/**
 * @package     Organizer
 * @extension   com_organizer
 * @author      James Antrim, <james.antrim@nm.thm.de>
 * @copyright   2020 TH Mittelhessen
 * @license     GNU GPL v.2
 * @link        www.thm.de
 */

namespace THM\Organizer\Models;

use Joomla\Utilities\ArrayHelper;
use THM\Organizer\Adapters\Input;
use THM\Organizer\Helpers;
use THM\Organizer\Tables\Curricula;

trait SubOrdinate
{
    /**
     * Adds ranges for the resource to the given superordinate ranges.
     *
     * @param array $data           the resource data from the form
     * @param array $superOrdinates the valid superordinate ranges to which to create/validate ranges within
     *
     * @return bool
     */
    private function addNew(array $data, array $superOrdinates): bool
    {
        $existingPool = ($this->resource === 'pool' and Input::getTask() !== 'pools.save2copy');
        $ranges       = $this->getRanges($data['id']);
        $resourceID   = $this->resource . 'ID';

        $range = [
            $resourceID => $data['id'],
            'curriculum' => $existingPool ? $this->getSubOrdinates() : []
        ];

        foreach ($superOrdinates as $super) {
            $range['parentID'] = $super['id'];

            foreach ($ranges as $index => $existing) {
                // There is an existing direct subordinate relationship
                if ($existing['parentID'] === $super['id']) {
                    // Prevent further iteration of an established relationship
                    unset($ranges[$index]);

                    // Update subordinate curricula entries as necessary
                    foreach ($range['curriculum'] as $subOrdinate) {
                        $subOrdinate['parentID'] = $existing['id'];

                        $this->addRange($subOrdinate);
                    }

                    continue 2;
                }
            }

            $range['ordering'] = $this->getOrdering($super['id'], $data['id']);

            if (!$this->addRange($range)) {
                return false;
            }
        }

        return true;
    }

    /**
     * Performs checks to ensure that a superordinate item has been selected as a precursor to the rest of the
     * curriculum processing.
     *
     * @param array $data the form data
     *
     * @return array[] the applicable superordinate ranges
     */
    private function getSuperOrdinates(array $data): array
    {
        // No need to check superordinates if no program context was selected
        if (empty($data['curricula'])) {
            $this->deleteRanges($data['id']);

            return [];
        }

        $data['curricula'] = ArrayHelper::toInteger($data['curricula']);

        // Program context is an explicit none
        if (in_array(self::NONE, $data['curricula'])) {
            $this->deleteRanges($data['id']);

            return [];
        }

        // No superordinate was selected or the superordinate is an explicit none
        if (empty($data['superordinates']) or in_array(self::NONE, $data['superordinates'])) {
            $this->deleteRanges($data['id']);

            return [];
        }

        // Retrieve the program context ranges for sanity checks on pool ranges
        $programRanges = [];
        foreach ($data['curricula'] as $programID) {
            if ($ranges = Helpers\Programs::getRanges($programID)) {
                $programRanges[$programID] = $ranges[0];
            }
        }

        $superOrdinateRanges = [];
        foreach ($data['superordinates'] as $superOrdinateID) {
            $table = new Curricula();

            // Non-existent or invalid entry
            if (!$table->load($superOrdinateID) or $table->subjectID) {
                continue;
            }

            // Requested superordinate is the program context root
            if ($programID = $table->programID) {
                // Subjects may not be directly associated with programs
                if ($this->resource === 'subject') {
                    continue;
                }

                foreach ($programRanges as $programRange) {
                    if ($programRange['programID'] === $programID) {
                        $superOrdinateRanges[$programRange['id']] = $programRange;
                    }
                }

                continue;
            }

            foreach (Helpers\Pools::getRanges($table->poolID) as $poolRange) {
                foreach ($programRanges as $programRange) {
                    // Pool range is a valid subset of the program context range
                    if ($poolRange['lft'] > $programRange['lft'] and $poolRange['rgt'] < $programRange['rgt']) {
                        $superOrdinateRanges[$poolRange['id']] = $poolRange;
                    }
                }
            }
        }

        return $superOrdinateRanges;
    }

    /**
     * Removes resource ranges not subordinate to the given superordinate elements.
     *
     * @param int   $resourceID     the resource id
     * @param array $superOrdinates the valid superordinate ranges
     *
     * @return void removes deprecated ranges from the database
     */
    private function removeDeprecated(int $resourceID, array $superOrdinates)
    {
        $superIDs = array_keys($superOrdinates);

        foreach ($this->getRanges($resourceID) as $range) {
            if (in_array($range['parentID'], $superIDs)) {
                continue;
            }

            // Remove unrequested existing relationship
            $this->deleteRange($range['id']);
        }
    }
}