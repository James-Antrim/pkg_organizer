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

use Joomla\Database\{DatabaseQuery, QueryInterface};
use THM\Organizer\Adapters\{Application, Database as DB, Input, User};
use THM\Organizer\Helpers\{Can, Terms};
use THM\Organizer\Controllers\Participated;

/** @inheritDoc */
class Participants extends ListModel
{
    use Participated;

    protected string $defaultOrdering = 'fullName';

    protected $filter_fields = ['attended', 'duplicates', 'paid', 'programID'];

    /** @inheritDoc */
    public function __construct($config = [])
    {
        parent::__construct($config);

        if (!Application::backend()) {
            $this->defaultLimit = 0;
        }
    }

    /** @inheritDoc */
    protected function addAccess(QueryInterface $query): void
    {
        if (Can::administrate()) {
            $query->select(DB::quote(1) . ' AS ' . DB::qn('access'));
        }
        elseif ($userID = User::id()) {
            $query->select(DB::quote($userID) . ' = ' . DB::qn('u.id') . ' AS ' . DB::qn('access'));
        }
        else {
            $query->select(DB::quote(0) . ' AS ' . DB::qn('access'));
        }
    }

    /** @inheritDoc */
    protected function clean(): void
    {
        // The data shows around 500,000 entries per term. If debug is on a timeout will be produced.
        if (JDEBUG) {
            Application::message('ORGANIZER_DEBUG_ON', Application::WARNING);
            return;
        }

        // Store any remaining participation data in the referenced instances
        $query = DB::query();
        $query->select('DISTINCT ' . DB::qn('ip.instanceID'))
            ->from(DB::qn('#__organizer_instance_participants', 'ip'))
            ->innerJoin(DB::qn('#__organizer_instances', 'i'), DB::qc('i.id', 'ip.instanceID'))
            ->innerJoin(DB::qn('#__organizer_blocks', 'b'), DB::qc('b.id', 'i.blockID'))
            ->where(DB::qc('date', Terms::startDate(Terms::previousID()), '<', true));
        DB::set($query);

        $instanceIDs = DB::integers();

        foreach ($instanceIDs as $instanceID) {
            $this->updateIPNumbers($instanceID);
        }

        // Mostly the same query for deletion of the same participation entries.
        $query->clear('select')->clear('from');
        $query->delete(DB::qn('#__organizer_instance_participants', 'ip'));

        // Joomla does not currently allow explicit setting of the table to be deleted from when join is used.
        $query = (string) $query;
        $query = str_replace('DELETE ', 'DELETE ' . DB::qn('ip'), $query);

        DB::set($query);
        DB::execute();
    }

    /** @inheritDoc */
    protected function getListQuery(): DatabaseQuery
    {
        $query = DB::query();
        $tag   = Application::tag();
        $url   = 'index.php?option=com_organizer&view=participant&id=';

        $nameParts    = [DB::qn('pa.surname'), "', '", DB::qn('pa.forename')];
        $programParts = [DB::qn("pr.name_$tag"), "' ('", DB::qn('d.abbreviation'), "' '", DB::qn('pr.accredited'), "')'"];
        $select       = [
            'DISTINCT ' . DB::qn('pa.id'),
            DB::qn('pa') . '.*',
            DB::qn('u') . '.*',
            $query->concatenate($nameParts, '') . ' AS ' . DB::qn('fullName'),
            $query->concatenate($programParts, '') . ' AS ' . DB::qn('program'),
            $query->concatenate([DB::quote($url), DB::qn('u.id')], '') . ' AS ' . DB::qn('url')
        ];

        $query->select($select)
            ->from(DB::qn('#__organizer_participants', 'pa'))
            ->innerJoin(DB::qn('#__users', 'u'), DB::qc('u.id', 'pa.id'))
            ->leftJoin(DB::qn('#__organizer_programs', 'pr'), DB::qc('pr.id', 'pa.programID'))
            ->leftJoin(DB::qn('#__organizer_degrees', 'd'), DB::qc('d.id', 'pr.degreeID'));

        $this->addAccess($query);

        $this->filterSearch($query, ['pa.forename', 'pa.surname', 'pr.name_de', 'pr.name_en']);
        $this->filterValues($query, ['programID']);

        if ($this->state->get('filter.duplicates')) {
            $forename1 = DB::qn('pa.forename');
            $forename2 = DB::qn('pa2.forename');
            $likeFN1   = $query->concatenate(["'%'", 'TRIM(' . DB::qn('pa.forename') . ')', "'%'"], '');
            $likeFN2   = $query->concatenate(["'%'", 'TRIM(' . DB::qn('pa2.forename') . ')', "'%'"], '');
            $likeSN1   = $query->concatenate(["'%'", 'TRIM(' . DB::qn('pa.surname') . ')', "'%'"], '');
            $likeSN2   = $query->concatenate(["'%'", 'TRIM(' . DB::qn('pa2.surname') . ')', "'%'"], '');
            $surname1  = DB::qn('pa.surname');
            $surname2  = DB::qn('pa2.surname');

            $similarForenames = "($forename1 LIKE $likeFN2 OR $forename2 LIKE $likeFN1)";
            $similarSurnames  = "($surname1 LIKE $likeSN2 OR $surname2 LIKE $likeSN1)";
            $conditions       = "($similarForenames AND $similarSurnames)";
            $paid             = DB::qn('pa.id');
            $query->leftJoin(DB::qn('#__organizer_participants', 'pa2'), $conditions)
                ->where("$paid != " . DB::qn('pa2.id'))
                ->group($paid);

            if ($domain = Input::parameters()->get('emailFilter')) {
                $domain = DB::quote("%$domain");
                $email1 = DB::qn('u.email');
                $email2 = DB::qn('u2.email');

                $externalExists = "($email1 NOT LIKE $domain OR $email2 NOT LIKE $domain)";

                $query->leftJoin(DB::qn('#__users', 'u2'), DB::qc('u2.id', 'pa2.id'))->where($externalExists);

            }
        }

        $this->orderBy($query);

        return $query;
    }
}
