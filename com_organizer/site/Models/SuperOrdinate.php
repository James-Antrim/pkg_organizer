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

use Joomla\Database\ParameterType;
use THM\Organizer\Adapters\{Database as DB, Input};

/**
 * Provides functions for superordinate curriculum resources.
 */
trait SuperOrdinate
{
    /**
     * Gets the curriculum for a pool selected as a subordinate resource
     *
     * @param   int  $poolID  the resource id
     *
     * @return array[]  empty if no child data exists
     */
    protected function curriculum(int $poolID): array
    {
        // Subordinate structures are the same for every superordinate resource
        $query = DB::getQuery();
        $query->select(DB::qn('id'))
            ->from(DB::qn('#__organizer_curricula'))
            ->where(DB::qn('poolID') . ' = :poolID')->bind(':poolID', $poolID, ParameterType::INTEGER);
        DB::setQuery($query);

        if (!$firstID = DB::loadInt()) {
            return [];
        }

        $query = DB::getQuery();
        $query->select('*')
            ->from(DB::qn('#__organizer_curricula'))
            ->where(DB::qn('parentID') . ' = :firstID')->bind(':firstID', $firstID, ParameterType::INTEGER)
            ->order(DB::qn('lft'));
        DB::setQuery($query);

        if (!$subOrdinates = DB::loadAssocList()) {
            return $subOrdinates;
        }

        foreach ($subOrdinates as $key => $subOrdinate) {
            if ($subOrdinate['poolID']) {
                $subOrdinates[$key]['curriculum'] = $this->curriculum($subOrdinate['poolID']);
            }
        }

        return $subOrdinates;
    }

    /**
     * Builds the resource's curriculum using the subordinate resources contained in the form.
     * @return array[]  an array containing the resource's subordinate resources
     */
    private function subordinates(): array
    {
        $index        = 1;
        $subOrdinates = [];

        while (Input::getInt("sub{$index}Order")) {
            $ordering      = Input::getInt("sub{$index}Order");
            $aggregateInfo = Input::getCMD("sub$index");

            if (!empty($aggregateInfo)) {
                $resourceID   = substr($aggregateInfo, 0, strlen($aggregateInfo) - 1);
                $resourceType = strpos($aggregateInfo, 'p') ? 'pool' : 'subject';

                if ($resourceType == 'subject') {
                    $subOrdinates[$ordering]['poolID']    = null;
                    $subOrdinates[$ordering]['subjectID'] = $resourceID;
                    $subOrdinates[$ordering]['ordering']  = $ordering;
                }

                if ($resourceType == 'pool') {
                    $subOrdinates[$ordering]['poolID']     = $resourceID;
                    $subOrdinates[$ordering]['subjectID']  = null;
                    $subOrdinates[$ordering]['ordering']   = $ordering;
                    $subOrdinates[$ordering]['curriculum'] = $this->curriculum($resourceID);
                }
            }

            $index++;
        }

        return $subOrdinates;
    }
}