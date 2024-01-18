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

use SimpleXMLElement;
use THM\Organizer\Adapters\{Application, Input};
use THM\Organizer\Helpers\{Documentable, Organizations};
use THM\Organizer\Tables\{Associations, Curricula, Pools as Table};

/**
 * Class which manages stored (subject) pool data.
 */
class Pool extends CurriculumResource
{
    use SubOrdinate;
    use SuperOrdinate;

    protected string $helper = 'Pools';

    protected string $resource = 'pool';

    /**
     * @inheritDoc
     * @return Table
     */
    public function getTable($name = '', $prefix = '', $options = []): Table
    {
        return new Table();
    }

    /**
     * @inheritDoc
     */
    public function importSingle(int $resourceID): bool
    {
        // There is no legitimate call to this method.
        return false;
    }

    /**
     * Creates a pool entry if none exists and calls
     *
     * @param   SimpleXMLElement  $XMLObject       a SimpleXML object containing rudimentary subject data
     * @param   int               $organizationID  the id of the organization to which this data belongs
     * @param   int               $parentID        the id of the parent entry
     *
     * @return bool  true on success, otherwise false
     */
    public function processResource(SimpleXMLElement $XMLObject, int $organizationID, int $parentID): bool
    {
        $lsfID = empty($XMLObject->pordid) ? (string) $XMLObject->modulid : (string) $XMLObject->pordid;
        if (empty($lsfID)) {
            return false;
        }

        $blocked = !empty($XMLObject->sperrmh) and strtolower((string) $XMLObject->sperrmh) === 'x';
        $noChildren = !isset($XMLObject->modulliste->modul);
        $validTitle = $this->validTitle($XMLObject);

        $pool = new Table();

        if (!$pool->load(['lsfID' => $lsfID])) {
            // There isn't one and shouldn't be one
            if ($blocked or !$validTitle or $noChildren) {
                return true;
            }

            $pool->lsfID = $lsfID;
            $this->setNameAttributes($pool, $XMLObject);

            if (!$pool->store()) {
                return false;
            }
        }
        elseif ($blocked or !$validTitle or $noChildren) {
            return $this->deleteSingle($pool->id);
        }

        $curricula = new Curricula();

        if (!$curricula->load(['parentID' => $parentID, 'poolID' => $pool->id])) {
            $range             = [];
            $range['parentID'] = $parentID;
            $range['poolID']   = $pool->id;

            $range['ordering'] = $this->ordering($parentID, $pool->id);
            if (!$this->shiftUp($parentID, $range['ordering'])) {
                return false;
            }

            if (!$this->addRange($range)) {
                return false;
            }

            $curricula->load(['parentID' => $parentID, 'poolID' => $pool->id]);
        }

        $association = new Associations();
        if (!$association->load(['organizationID' => $organizationID, 'poolID' => $pool->id])) {
            $association->save(['organizationID' => $organizationID, 'poolID' => $pool->id]);
        }

        return $this->processCollection($XMLObject->modulliste->modul, $organizationID, $curricula->id);
    }

    /**
     * @inheritDoc
     */
    public function save(array $data = []): int
    {
        $data = empty($data) ? Input::getFormItems() : $data;

        if (empty($data['id'])) {
            if (!Organizations::documentableIDs()) {
                Application::error(403);
            }
        }
        elseif (is_numeric($data['id'])) {
            /** @var Documentable $helper */
            $helper = "THM\\Organizer\\Helpers\\$this->helper";
            if (!$helper::documentable((int) $data['id'])) {
                Application::error(403);
            }
        }
        else {
            return false;
        }

        $table = new Table();

        if (!$table->save($data)) {
            return false;
        }

        $data['id'] = $table->id;

        if (!empty($data['organizationIDs']) and !$this->updateAssociations($data['id'], $data['organizationIDs'])) {
            return false;
        }

        $superOrdinates = $this->getSuperOrdinates($data);

        if (!$this->addNew($data, $superOrdinates)) {
            return false;
        }

        $this->removeDeprecated($table->id, $superOrdinates);

        return $table->id ?: 0;
    }
}
