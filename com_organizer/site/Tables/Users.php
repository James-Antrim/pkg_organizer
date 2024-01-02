<?php
/**
 * @package     Organizer
 * @extension   com_organizer
 * @author      James Antrim, <james.antrim@nm.thm.de>
 * @copyright   2023 TH Mittelhessen
 * @license     GNU GPL v.2
 * @link        www.thm.de
 */

namespace THM\Organizer\Tables;

use Joomla\CMS\Table\User;
use Joomla\Database\DatabaseDriver;
use Joomla\Database\DatabaseInterface;
use THM\Organizer\Adapters\Application;

/**
 * Class extends the user table for the purposes of documentation and code completion. Since this class inherits from user
 * typing is only available
 */
class Users extends User
{
    /**
     * A password hash used during the reset process. Any non-empty value indicates a reset has been performed at some point.
     * VARCHAR(100) NOT NULL DEFAULT ''
     * @var string
     */
    public string $activation;

    /**
     * VARCHAR(100) NOT NULL DEFAULT ''
     * Name of used authentication plugin
     * @var string
     */
    public string $authProvider;

    /**
     * Whether the user is barred from logging into the site.
     * TINYINT(4) NOT NULL DEFAULT 0
     * @var int
     */
    public int $block;

    /**
     * VARCHAR(100) NOT NULL DEFAULT ''
     * Magic property in parent.
     * @var string
     */
    public string $email;

    /**
     * INT(11) NOT NULL
     * Magic property in parent. Typing would cause a problem here, but table keys are ignored during the reset function.
     * @var int
     */
    public int $id;

    /**
     * DATETIME
     * Magic property in parent.
     * @var null|string
     */
    public null|string $lastResetTime;

    /**
     * DATETIME
     * Magic property in parent.
     * @var null|string
     */
    public null|string $lastvisitDate;

    /**
     * VARCHAR(400) NOT NULL DEFAULT ''
     * @var string
     */
    public string $name;

    /**
     * VARCHAR(1000) DEFAULT ''
     * One time emergency passwords
     * Magic property in parent.
     * @var string
     */
    public string $otep;

    /**
     * VARCHAR(1000) DEFAULT ''
     * Two-factor authentication encrypted keys
     * Magic property in parent.
     * @var string
     */
    public string $otpKey;

    /**
     * MEDIUMTEXT NOT NULL
     * JSON String. Typing would propably cause problems here because it is both NOT NULL and implicitly DEFAULT NULL
     * @var null|string
     */
    public null|string $params;

    /**
     * VARCHAR(100) NOT NULL DEFAULT ''
     * @var string
     */
    public string $password;

    /**
     * DATETIME NOT NULL
     * Magic property in parent. Typing would propably cause problems here because it is both NOT NULL and DEFAULT NULL.
     * @var string
     */
    public $registerDate;

    /**
     * TINYINT(4) DEFAULT 0
     * Require user to reset password on next login
     * @var int
     */
    public int $requireReset;

    /**
     * INT(11) NOT NULL DEFAULT 0
     * Count of password resets since lastResetTime
     * @var int
     */
    public int $resetCount;

    /**
     * TINYINT(4) DEFAULT 0
     * Magic property in parent. A boolean flag wrapped in a tiny int that is nullable... they were out of consistency.
     * @var int
     */
    public int $sendEmail;

    /**
     * VARCHAR(150) NOT NULL DEFAULT ''
     * @var string
     */
    public string $username;

    /**
     * @inheritDoc
     */
    public function __construct(DatabaseInterface $dbo = null)
    {
        $dbo = $dbo ?? Application::getDB();

        /** @var DatabaseDriver $dbo */
        parent::__construct($dbo);
    }
}