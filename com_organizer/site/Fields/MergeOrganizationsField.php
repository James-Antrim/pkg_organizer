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

use THM\Organizer\Adapters\{Application, Database, HTML, Input};
use stdClass;

/**
 * Class creates a generalized select box for selection of a single id column value among those already selected.
 */
class MergeOrganizationsField extends OptionsField
{
    /**
     * @var  string
     */
    protected $type = 'MergeOrganizations';

    /**
     * Returns a select box where resource attributes can be selected
     * @return stdClass[] the options for the select box
     */
    protected function getOptions(): array
    {
        $selectedIDs    = Input::getSelectedIDs();
        $resource       = str_replace('_merge', '', Input::getView());
        $validResources = ['category', 'person'];
        $invalid        = (empty($selectedIDs) or empty($resource) or !in_array($resource, $validResources));
        if ($invalid) {
            return [];
        }

        $query      = Database::getQuery(true);
        $table      = $resource === 'category' ? 'categories' : 'persons';
        $textColumn = 'shortName_' . Application::getTag();
        $query->select("DISTINCT o.id AS value, o.$textColumn AS text")
            ->from("#__organizer_organizations AS o")
            ->innerJoin("#__organizer_associations AS a ON a.organizationID = o.id")
            ->innerJoin("#__organizer_$table AS res ON res.id = a.{$resource}ID")
            ->where("res.id IN ( '" . implode("', '", $selectedIDs) . "' )")
            ->order('text ASC');
        Database::setQuery($query);

        if (!$valuePairs = Database::loadAssocList()) {
            return [];
        }

        $options = [];
        $values  = [];
        foreach ($valuePairs as $valuePair) {
            $options[]                   = HTML::option($valuePair['value'], $valuePair['text']);
            $values[$valuePair['value']] = $valuePair['value'];
        }

        $this->value = $values;

        return empty($options) ? [] : $options;
    }
}
