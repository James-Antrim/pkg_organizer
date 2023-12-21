<?php
/**
 * @package     Organizer
 * @extension   com_organizer
 * @author      James Antrim, <james.antrim@nm.thm.de>
 * @copyright   2020 TH Mittelhessen
 * @license     GNU GPL v.3
 * @link        www.thm.de
 */

namespace THM\Organizer\Tables;

use Joomla\CMS\Access\Rules;
use Joomla\CMS\Table\{Asset, Table as JTable};
use Joomla\Database\{DatabaseDriver, DatabaseInterface, ParameterType};
use THM\Organizer\Adapters\{Application, Database as DB};

/**
 * Models the organizer_organizations table.
 */
class Organizations extends Table
{
    use Activated;
    use Aliased;
    use Incremented;

    /**
     * The resource's German abbreviation.
     * VARCHAR(25) NOT NULL
     * @var string
     * @noinspection PhpMissingFieldTypeInspection
     */
    public $abbreviation_de;

    /**
     * The resource's English abbreviation.
     * VARCHAR(25) NOT NULL
     * @var string
     * @noinspection PhpMissingFieldTypeInspection
     */
    public $abbreviation_en;

    /**
     * A flag which displays whether the planning for the organization directly is allowed.
     * TINYINT(1) UNSIGNED NOT NULL
     * @var bool
     * @noinspection PhpMissingFieldTypeInspection
     */
    public $allowScheduling;

    /**
     * The id used by Joomla as a reference to its assets table.
     * INT(11) NOT NULL
     * @var int|null
     */
    public int|null $asset_id = null;

    /**
     * The id of the user entry referenced.
     * INT(11) DEFAULT NULL
     * @var int|null
     */
    public int|null $contactID;

    /**
     * The email address to be used for contacting participants
     * VARCHAR(100) DEFAULT NULL
     * @var null|string
     */
    public null|string $contactEmail;

    /**
     * The resource's German full name.
     * VARCHAR(200) NOT NULL
     * @var string
     * @noinspection PhpMissingFieldTypeInspection
     */
    public $fullName_de;

    /**
     * The resource's English full name.
     * VARCHAR(200) NOT NULL
     * @var string
     * @noinspection PhpMissingFieldTypeInspection
     */
    public $fullName_en;

    /**
     * The resource's German name.
     * VARCHAR(150) NOT NULL
     * @var string
     * @noinspection PhpMissingFieldTypeInspection
     */
    public $name_de;

    /**
     * The resource's English name.
     * VARCHAR(150) NOT NULL
     * @var string
     * @noinspection PhpMissingFieldTypeInspection
     */
    public $name_en;

    /**
     * The resource's German shortened name.
     * VARCHAR(50) NOT NULL
     * @var string
     * @noinspection PhpMissingFieldTypeInspection
     */
    public $shortName_de;

    /**
     * The resource's English shortened name.
     * VARCHAR(50) NOT NULL
     * @var string
     * @noinspection PhpMissingFieldTypeInspection
     */
    public $shortName_en;

    /**
     * The base URL for the organization's homepage.
     * VARCHAR(50) NOT NULL
     * @var string
     * @noinspection PhpMissingFieldTypeInspection
     * @noinspection PhpPropertyNamingConventionInspection
     */
    public $URL;

    /**
     * @inheritDoc
     */
    public function __construct(DatabaseInterface $dbo = null)
    {
        $dbo = $dbo ?? Application::getDB();

        /** @var DatabaseDriver $dbo */
        parent::__construct('#__organizer_organizations', 'id', $dbo);
    }

    /**
     * @inheritDoc
     */
    protected function _getAssetTitle(): string
    {
        return $this->shortName_en;
    }

    /**
     * @inheritDoc
     */
    protected function _getAssetName(): string
    {
        $key = $this->_tbl_key;

        return 'com_organizer.organization.' . (int) $this->$key;
    }

    /**
     * @inheritDoc
     */
    protected function _getAssetParentId(JTable $table = null, $id = null): int
    {
        $asset = new Asset(Application::getDB());
        $asset->loadByName('com_organizer');

        return $asset->id;
    }

    /**
     * @inheritDoc
     */
    public function bind($src, $ignore = ''): bool
    {
        if (isset($src['rules']) and is_array($src['rules'])) {
            $this->cleanRules($src['rules']);
            $rules = new Rules($src['rules']);
            $this->setRules($rules);
        }

        return parent::bind($src, $ignore);
    }

    /**
     * @inheritDoc
     */
    public function check(): bool
    {
        if (!$this->alias) {
            $this->alias = null;
        }

        if (!$this->contactID) {
            $this->contactID = null;
        }

        return true;
    }

    /**
     * Removes inherited groups before Joomla erroneously sets the value to 0. Joomla must have something similar, but I
     * don't have time to look for it.
     *
     * @param   array &$rules  the rules from the form
     *
     * @return void  unsets group indexes with a truly empty value
     */
    private function cleanRules(array &$rules): void
    {
        foreach ($rules as $rule => $groups) {
            foreach ($groups as $group => $value) {
                if (empty($value) and $value !== 0) {
                    unset($rules[$rule][$group]);
                }
            }
        }
    }

    /**
     * @inheritDoc
     */
    public function store($updateNulls = true): bool
    {
        $currentAssetId = 0;

        if ($this->asset_id) {
            $currentAssetId = $this->asset_id;
        }

        unset($this->asset_id);

        // If a primary key exists update the object, otherwise insert it.
        if ($this->hasPrimaryKey()) {
            $result = $this->_db->updateObject($this->_tbl, $this, $this->_tbl_keys, $updateNulls);
        }
        else {
            $result = $this->_db->insertObject($this->_tbl, $this, $this->_tbl_keys[0]);
        }

        $this->_unlock();
        $asset = new Asset($this->getDbo());
        $name  = $this->_getAssetName();
        $asset->loadByName($name);
        $this->asset_id = $asset->id;

        $parentId = $this->_getAssetParentId();

        // New asset or new structuring for existing asset
        if (!$this->asset_id or $asset->parent_id != $parentId) {
            $asset->setLocation($parentId, 'last-child');
        }

        $asset->name      = $name;
        $asset->parent_id = $parentId;
        $asset->title     = $this->_getAssetTitle();

        if ($this->_rules instanceof Rules) {
            $asset->rules = (string) $this->_rules;
        }

        // Try to create/update the asset.
        if (!$asset->check() or !$asset->store()) {
            return false;
        }

        // Create an asset_id or heal one that is corrupted.
        if (!$this->asset_id or $currentAssetId !== $this->asset_id) {
            $this->asset_id = $asset->id;

            $query = DB::getQuery();
            $query->update(DB::qn('#__organizer_organizations'))
                ->set(DB::qn('asset_id') . ' = :assetID')
                ->bind(':assetID', $this->asset_id, ParameterType::INTEGER)
                ->where(DB::qn('id') . ' = :tableID')
                ->bind(':tableID', $this->id, ParameterType::INTEGER);
            DB::setQuery($query);

            if (!DB::execute()) {
                return false;
            }
        }

        return $result;
    }
}
