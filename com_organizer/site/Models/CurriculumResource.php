<?php
/**
 * @package     Organizer\Models
 * @subpackage
 * @copyright   A copyright
 * @license     A "Slug" license name e.g. GPL2
 */

namespace THM\Organizer\Models;

use Joomla\CMS\Table\Table;
use SimpleXMLElement;
use THM\Organizer\Adapters\{Application, Input};
use THM\Organizer\Helpers\{Curricula as Helper, Documentable, Organizations};

/**
 * Class provides functions to use managing resources in a nested curriculum structure.
 */
abstract class CurriculumResource extends BaseModel
{
    protected const NONE = -1, POOL = 'K', SUBJECT = 'M';

    protected string $helper;

    protected string $resource;

    /**
     * @inheritDoc
     */
    protected function authorize(): void
    {
        /** @var Documentable $helper */
        $helper = "THM\\Organizer\\Helpers\\$this->helper";
        if (($id = Input::getID() and !$helper::documentable($id)) or !Organizations::documentableIDs()) {
            Application::error(403);
        }
    }

    /**
     * Method to import data associated with resources from LSF
     * @return bool true on success, otherwise false
     */
    public function import(): bool
    {
        foreach (Input::getSelectedIDs() as $resourceID) {
            if (!$this->importSingle($resourceID)) {
                return false;
            }
        }

        return true;
    }

    /**
     * Iterates a collection of resources subordinate to the calling resource. Creating structure and data elements as
     * needed.
     *
     * @param   SimpleXMLElement  $collection      the SimpleXML node containing the collection of subordinate elements
     * @param   int               $organizationID  the id of the organization with which the resources are associated
     * @param   int               $parentID        the id of the curriculum entry for the parent element.
     *
     * @return bool true on success, otherwise false
     */
    protected function processCollection(SimpleXMLElement $collection, int $organizationID, int $parentID): bool
    {
        $pool    = new Pool();
        $subject = new Subject();

        foreach ($collection as $subOrdinate) {
            $type = (string) $subOrdinate->pordtyp;

            if ($type === self::POOL) {
                if ($pool->processStub($subOrdinate, $organizationID, $parentID)) {
                    continue;
                }

                return false;
            }

            if ($type === self::SUBJECT) {
                if ($subject->processStub($subOrdinate, $organizationID, $parentID)) {
                    continue;
                }

                return false;
            }
        }

        return true;
    }

    /**
     * Gets the mapped curricula ranges for the given resource
     *
     * @param   int  $resourceID  the resource id
     *
     * @return array[] the resource ranges
     */
    protected function ranges(int $resourceID): array
    {
        /** @var Helper $helper */
        $helper = "THM\\Organizer\\Helpers\\$this->helper";

        return $helper::rows($resourceID);
    }

    /**
     * Method to get a table object, load it if necessary.
     *
     * @param   string  $name     The table name. Optional.
     * @param   string  $prefix   The class prefix. Optional.
     * @param   array   $options  Configuration array for model. Optional.
     *
     * @return Table A Table object
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     * @noinspection PhpMissingReturnTypeInspection polymorphic return value
     */
    public function getTable($name = '', $prefix = '', $options = []): Table
    {
        $table = "THM\\Organizer\\Tables\\$this->helper";

        return new $table();
    }
}