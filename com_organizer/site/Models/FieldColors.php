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

use Joomla\CMS\Form\Form;
use Joomla\Database\DatabaseQuery;
use THM\Organizer\Adapters\{Application, Database as DB};
use THM\Organizer\Helpers\Can;

/**
 * Class retrieves information for a filtered set of fields (of expertise).
 */
class FieldColors extends ListModel
{
    protected string $defaultOrdering = 'field';

    protected $filter_fields = ['colorID' => 'colorID', 'organizationID' => 'organizationID'];

    /**
     * @inheritDoc
     */
    protected function filterFilterForm(Form $form): void
    {
        if (count(Can::documentTheseOrganizations()) === 1) {
            $form->removeField('organizationID', 'filter');
            unset($this->filter_fields['organizationID']);
        }
    }

    /**
     * @inheritDoc
     */
    protected function getListQuery(): DatabaseQuery
    {
        $tag   = Application::getTag();
        $query = DB::getQuery();
        $url   = 'index.php?option=com_organizer&view=FieldColor&id=';

        $aliased = DB::qn(["c.name_$tag", "f.name_$tag", "o.shortName_$tag"], ['color', 'field', 'organization']);
        $select  = [
            'DISTINCT ' . DB::qn('fc.id'),
            DB::qn('fc') . '.*',
            DB::quote(1) . ' AS ' . DB::qn('access'),
            $query->concatenate([DB::quote($url), DB::qn('fc.id')], '') . ' AS ' . DB::qn('url'),
        ];

        $query->select(array_merge($select, $aliased))
            ->from(DB::qn('#__organizer_field_colors', 'fc'))
            ->innerJoin(DB::qn('#__organizer_colors', 'c'), DB::qc('c.id', 'fc.colorID'))
            ->innerJoin(DB::qn('#__organizer_fields', 'f'), DB::qc('f.id', 'fc.fieldID'))
            ->innerJoin(DB::qn('#__organizer_organizations', 'o'), DB::qc('o.id', 'fc.organizationID'));

        // Explicitly set via request
        $this->filterByKey($query, 'c.id', 'colorID');
        $this->filterByKey($query, 'f.id', 'fieldID');

        // Explicitly set via request or implicitly set by authorization
        if ($organizationID = $this->state->get('filter.organizationID')) {
            $query->where("organizationID = $organizationID");
        }

        $this->orderBy($query);

        return $query;
    }

    /**
     * @inheritDoc
     */
    protected function populateState($ordering = null, $direction = null): void
    {
        parent::populateState($ordering, $direction);

        $documentable = Can::documentTheseOrganizations();

        if (count($documentable) === 1) {
            $this->setState('filter.organizationID', $documentable[0]);
        }
    }
}
