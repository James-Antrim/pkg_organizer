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

use Joomla\Database\{DatabaseDriver, DatabaseInterface};
use THM\Organizer\Adapters\Application;

/**
 * @inheritDoc
 */
class Participants extends Table
{
    /**
     * The physical address of the resource.
     * VARCHAR(60) NOT NULL DEFAULT ''
     * @var string
     * @noinspection PhpMissingFieldTypeInspection
     */
    public $address;

    /**
     * The city in which the resource is located.
     * VARCHAR(60) NOT NULL
     * @var string
     * @noinspection PhpMissingFieldTypeInspection
     */
    public $city;

    /**
     * The person's first and middle names.
     * VARCHAR(255) NOT NULL DEFAULT ''
     * @var string
     * @noinspection PhpMissingFieldTypeInspection
     */
    public $forename;

    /**
     * A flag displaying whether the user wishes to receive emails regarding schedule changes.
     * TINYINT(1) UNSIGNED NOT NULL DEFAULT 0
     * @var bool
     * @noinspection PhpMissingFieldTypeInspection
     */
    public $notify;

    /**
     * The id of the program entry referenced.
     * INT(11) UNSIGNED DEFAULT NULL
     * @var int|null
     */
    public int|null $programID;

    /**
     * The person's surnames.
     * VARCHAR(255) NOT NULL DEFAULT ''
     * @var string
     * @noinspection PhpMissingFieldTypeInspection
     */
    public $surname;

    /**
     * The person's telephone number.
     * VARCHAR(60) NOT NULL DEFAULT ''
     * @var string
     * @noinspection PhpMissingFieldTypeInspection
     */
    public $telephone;

    /**
     * The ZIP code of the resource.
     * VARCHAR(60) NOT NULL DEFAULT ''
     * @var string
     * @noinspection PhpMissingFieldTypeInspection
     */
    public $zipCode;

    /**
     * @inheritDoc
     */
    public function __construct(DatabaseInterface $dbo = null)
    {
        $dbo = $dbo ?? Application::getDB();

        /** @var DatabaseDriver $dbo */
        parent::__construct('#__organizer_participants', 'id', $dbo);
    }

    /**
     * @inheritDoc
     */
    public function check(): bool
    {
        if (!$this->programID) {
            $this->programID = null;
        }

        return true;
    }
}
