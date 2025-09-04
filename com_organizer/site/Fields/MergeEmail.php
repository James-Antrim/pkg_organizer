<?php
/**
 * @package     Organizer
 * @extension   com_organizer
 * @author      James Antrim, <james.antrim@nm.thm.de>
 * @copyright   2020 TH Mittelhessen
 * @license     GNU GPL v.3
 * @link        www.thm.de
 */

namespace THM\Organizer\Fields;

use THM\Organizer\Adapters\{Database as DB, Input};

/**
 * Class creates a generalized select box for selection of a single column value among those already selected.
 */
class MergeEmail extends MergeValues
{
    use Mergeable;

    /**
     * Gets the saved values for the selected resource IDs.
     * @return array
     */
    protected function getValues(): array
    {
        $query = DB::query();
        $query->select(['DISTINCT ' . DB::qn('email', 'value'), DB::qn('email', 'text')])
            ->from(DB::qn('#__users'))
            ->whereIn(DB::qn('id'), $this->selectedIDs)
            ->order(DB::qn('value') . ' ASC');
        DB::set($query);

        if (!$addresses = DB::column()) {
            return [];
        }

        // Prefilter domain matches if configured
        if ($domain = Input::parameters()->get('emailFilter')) {
            foreach ($addresses as $address) {
                if (strpos($address, $domain)) {
                    return [$address];
                }
            }
        }

        return $addresses;
    }
}
