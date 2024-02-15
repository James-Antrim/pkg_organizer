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

use Joomla\CMS\Form\Field\ListField;
use Joomla\Database\ParameterType;
use THM\Organizer\Adapters\{Database as DB, Form, HTML, Input};
use THM\Organizer\Helpers\{Can, Organizations};

/**
 * Class creates a select box for organizations based on column values in resource tables.
 */
class OrganizationAssociations extends ListField
{
    // Resources associated by values in their respective tables => not in the associations table.
    private array $disassociated = ['Events' => 'event', 'FieldColors' => 'fieldcolor'];

    // Management views => organizations determined by authorization rather than associations.
    private array $managed = ['workload'];

    /**
     * Retrieves the organization ids associated with the resource.
     *
     * @param   string  $resource    the resource type
     * @param   int     $resourceID  the resource id
     *
     * @return int[] the ids of the organizations associated with the resource
     */
    private function associatedIDs(string $resource, int $resourceID): array
    {
        // These resources are mapped directly in resource tables.
        if ($tableClass = array_search($resource, $this->disassociated)) {
            $tableClass = "THM\\Organizer\\Tables\\$tableClass";
            $table      = new $tableClass();

            return ($table->load($resourceID) and !empty($table->organizationID)) ? [$table->organizationID] : [];
        }

        $query = DB::getQuery();
        $query->select('DISTINCT ' . DB::qn('organizationID'))
            ->from(DB::qn('#__organizer_associations'))
            ->where(DB::qn("{$resource}ID") . ' = :resourceID')
            ->bind(':resourceID', $resourceID, ParameterType::INTEGER);
        DB::setQuery($query);

        return DB::loadIntColumn();
    }

    /**
     * Retrieves the organization ids authorized for use by the user.
     *
     * @param   string  $resource  the resource type
     *
     * @return int[] the ids of the organizations associated with the resource
     */
    private function authorizedIDs(string $resource): array
    {
        return match ($resource) {
            'category', 'event', 'group' => Organizations::schedulableIDs(),
            'fieldcolor', 'pool', 'program', 'subject' => Organizations::documentableIDs(),
            'person' => Can::manage('persons') ? Organizations::getIDs() : [],
            'workload' => Can::manageTheseOrganizations(),
            default => [],
        };
    }

    /**
     * @inheritDoc
     */
    protected function getInput(): string
    {
        /**
         * This portion would normally be a part of the getOptions function, but available options influence the layout
         * data. Since the layout data would otherwise be beyond the scope of influence the code is not extracted in
         * this implementation.
         */

        /** @var Form $form */
        $form       = $this->form;
        $resource   = $form->view();
        $resourceID = Input::getSelectedID(Input::getID());

        $authorizedIDs = $this->authorizedIDs($resource);
        $disabled      = false;

        if (in_array($resource, $this->managed)) {
            $organizationIDs = $authorizedIDs;
        }
        else {
            $associatedIDs = $this->associatedIDs($resource, $resourceID);

            $this->value = $resource === 'fieldcolor' ? reset($associatedIDs) : $associatedIDs;

            $assocCount = count($associatedIDs);
            $authCount  = count($authorizedIDs);

            $exhausted = $assocCount >= $authCount;
            $subset    = count(array_intersect($authorizedIDs, $associatedIDs)) === $assocCount;

            if ($exhausted or !$subset) {
                $organizationIDs = $associatedIDs;
                $disabled        = true;
            }
            else {
                $organizationIDs = $authorizedIDs;
            }
        }

        $options = [];
        foreach ($organizationIDs as $organizationID) {
            $shortName           = Organizations::getShortName($organizationID);
            $options[$shortName] = HTML::option($organizationID, $shortName);
        }

        ksort($options);

        $data            = $this->getLayoutData();
        $data['options'] = $options;

        // Since overwrites are included anywhere and everywhere both object properties and layout data is modified.
        if (in_array($resource, $this->disassociated)) {
            $this->autofocus   = 'autofocus';
            $data['autofocus'] = 'autofocus';
        }
        else {
            $this->name   = $this->name . '[]';
            $data['name'] = $this->name . '[]';

            $count = count($options);

            if ($count > 1) {
                $this->multiple   = 'multiple';
                $data['multiple'] = 'multiple';
            }

            if ($disabled) {
                $this->disabled   = 'disabled';
                $data['disabled'] = 'disabled';
                $this->size       = $count;
                $data['size']     = $count;
            }
            elseif (!in_array($resource, $this->managed)) {
                $this->autofocus   = 'autofocus';
                $data['autofocus'] = 'autofocus';

                if ($count < 10) {
                    $this->size   = $count;
                    $data['size'] = $count;
                }
                else {
                    $this->size   = 10;
                    $data['size'] = 10;
                }
            }
        }

        return $this->getRenderer($this->layout)->render($data);
    }
}
