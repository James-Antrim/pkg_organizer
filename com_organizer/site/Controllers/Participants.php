<?php
/**
 * @package     Organizer
 * @extension   com_organizer
 * @author      James Antrim, <james.antrim@nm.thm.de>
 * @copyright   2020 TH Mittelhessen
 * @license     GNU GPL v.3
 * @link        www.thm.de
 */

namespace THM\Organizer\Controllers;

use THM\Organizer\Adapters\Database as DB;

/** @inheritDoc */
class Participants extends ListController
{
    use Participated;

    protected string $item = 'Participant';

//	private function anonymize()
//	{
//		/**
//		 * Anonymize user:
//		 * name: 'Anonymous User'
//		 * username: 'x-<id>'
//		 * password: ''
//		 * email: x.<id>@xx.xx
//		 * block: 1
//		 * sendEmail: 0
//		 * registerDate: '0000-00-00 00:00:00'
//		 * lastvisitDate: '0000-00-00 00:00:00'
//		 * activation: ''
//		 * params: '{}'
//		 * lastResetTime: '0000-00-00 00:00:00'
//		 * resetCount: 0
//		 * otpKey: ''
//		 * otep: ''
//		 * requireReset: 0
//		 * authProvider: ''
//		 */
//
//		/**
//		 * Anonymize participant:
//		 *
//		 */
//	}

    /**
     * Updates all instance participation numbers.
     * @return void
     */
    public function update(): void
    {
        $this->checkToken();
        $this->authorize();

        $query = DB::query();
        $query->select('DISTINCT ' . DB::qn('instanceID'))->from(DB::qn('#__organizer_instance_participants'));
        DB::set($query);

        $instanceIDs = DB::integers();
        $relevant    = count($instanceIDs);
        $updated     = 0;

        foreach ($instanceIDs as $instanceID) {
            if ($this->updateIPNumbers($instanceID)) {
                $updated++;
            }
        }

        $this->farewell($relevant, $updated);
    }
}
