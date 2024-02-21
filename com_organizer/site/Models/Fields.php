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

use Joomla\Database\{DatabaseQuery, ParameterType};
use THM\Organizer\Adapters\{Application, Database as DB, Input};

/**
 * Class retrieves information for a filtered set of fields (of expertise).
 */
class Fields extends ListModel
{
    protected string $defaultOrdering = 'name';

    protected $filter_fields = ['colorID', 'organizationID'];

    /**
     * @inheritDoc
     */
    protected function getListQuery(): DatabaseQuery
    {
        $query = DB::getQuery();
        $tag   = Application::getTag();
        $url   = 'index.php?option=com_organizer&view=Field&id=';

        $select = [
            'DISTINCT ' . DB::qn('f.id'),
            DB::quote(1) . ' AS ' . DB::qn('access'),
            DB::qn('code'),
            DB::qn("f.name_$tag", 'name'),
            $query->concatenate([DB::quote($url), DB::qn('f.id')], '') . ' AS ' . DB::qn('url')
        ];

        $query->select($select)->from(DB::qn('#__organizer_fields', 'f'));

        $this->filterSearch($query, ['f.name_de', 'f.name_en', 'code']);

        $color        = Input::getFilterID('colorID');
        $organization = Input::getFilterID('organizationID');
        if ($color or $organization) {
            $fc  = DB::qn('#__organizer_field_colors', 'fc');
            $fcc = DB::qn('fc.fieldID') . ' = ' . DB::qn('f.id');
            if ($color === self::NONE or $organization === self::NONE) {
                $query->leftJoin($fc, $fcc);
            }
            else {
                $query->innerJoin($fc, $fcc);
            }

            if ($color) {
                $colorID = DB::qn('colorID');
                if ($color === self::NONE) {
                    $query->where("$colorID IS NULL");
                }
                else {
                    $query->where("$colorID = :colorID")->bind(':colorID', $color, ParameterType::INTEGER);
                }
            }

            if ($organization) {
                $organizationID = DB::qn('organizationID');
                if ($color === self::NONE) {
                    $query->where("$organizationID IS NULL");
                }
                else {
                    $query->where("$organizationID = :orgID")->bind(':orgID', $organization, ParameterType::INTEGER);
                }
            }
        }

        $this->orderBy($query);

        return $query;
    }
}
