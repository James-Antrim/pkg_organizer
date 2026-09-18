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

use THM\Organizer\Adapters\{Application, Database as DB, Input};
use THM\Organizer\Controllers\Booked;
use THM\Organizer\Helpers\{Can, Instances as iHelper, Participation as Helper};
use THM\Organizer\Tables\{InstanceParticipants as Table};

/**
 * Class which manages stored course data.
 */
class InstanceParticipant extends BaseModel
{
    use Booked;

    /** @inheritDoc */
    protected function authorize(): void
    {
        $bookingID = 0;

        if (!$participationID = Input::id() or !$bookingID = Helper::bookingID($participationID)) {
            Application::error(400);
        }

        if (!Can::manage('booking', $bookingID)) {
            Application::error(403);
        }
    }

    /** @inheritDoc */
    public function getTable($name = '', $prefix = '', $options = []): Table
    {
        return new Table();
    }

    /** @inheritDoc */
    public function save(array $data = []): int
    {
        $this->authorize();

        $data = empty($data) ? Input::post() : $data;

        $table = new Table();
        if (!$table->load($data['id'])) {
            return false;
        }

        $table->instanceID = $data['instanceID'];
        $table->roomID     = $data['roomID'];
        $table->seat       = $data['seat'];

        $query = DB::query();
        $query->select(DB::qn('ip') . '.*')
            ->from(DB::qn('#__organizer_instance_participants', 'ip'))
            ->innerJoin(DB::qn('#__organizer_instances', 'i1'), DB::qc('i1.id', 'ip.instanceID'))
            ->innerJoin(DB::qn('#__organizer_bookings', 'b'), DB::qcs([['b.blockID', 'i1.blockID'], ['b.unitID', 'i1.unitID']]))
            ->innerJoin(DB::qn('#__organizer_instances', 'i2'), DB::qcs([['i2.blockID', 'b.blockID'], ['i2.unitID', 'b.unitID']]))
            ->where(DB::qcs([['i2.id', $table->instanceID], ['ip.participantID', $table->participantID]]));
        DB::set($query);

        $instanceIDs = [];
        foreach (DB::arrays() as $entry) {
            $instanceIDs[$entry['instanceID']] = $entry['instanceID'];
            $table->registered                 = $table->registered ?: !empty($entry['registered']);

            if ($entry['id'] !== $table->id) {
                $otherTable = new Table();

                if (!$otherTable->load($entry['id'])) {
                    continue;
                }

                $otherTable->delete();
            }
        }

        $table->store();

        foreach ($instanceIDs as $instanceID) {
            iHelper::updateNumbers($instanceID);
        }

        return $table->id;
    }
}