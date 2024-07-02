<?php
/**
 * @package     Organizer
 * @extension   pkg_organizer
 * @author      James Antrim, <james.antrim@nm.thm.de>
 * @copyright   2023 TH Mittelhessen
 * @license     GNU GPL v.3
 * @link        www.thm.de
 */


namespace THM\Organizer\Adapters;

use Joomla\CMS\User\User as Instance;
use Joomla\CMS\User\UserFactory;
use Joomla\CMS\User\UserFactoryInterface;

class User
{
    public const PUBLIC = 1, REGISTERED = 2, AUTHOR = 3, EDITOR = 4, PUBLISHER = 5, MANAGER = 6, ADMIN = 7, SUPER_ADMIN = 8;

    public const STANDARD_GROUPS = [
        self::ADMIN,
        self::AUTHOR,
        self::EDITOR,
        self::MANAGER,
        self::PUBLIC,
        self::PUBLISHER,
        self::REGISTERED,
        self::SUPER_ADMIN
    ];

    /**
     * Gets the id of the user, optionally by username.
     *
     * @param   string  $userName
     *
     * @return int
     */
    public static function id(string $userName = ''): int
    {
        return self::instance($userName)->id ?: 0;
    }

    /**
     * Gets a user object (specified or current).
     *
     * @param   int|string  $userID  the user identifier (id or name)
     *
     * @return Instance
     */
    public static function instance(int|string $userID = 0): Instance
    {
        /** @var UserFactory $userFactory */
        $userFactory = Application::getContainer()->get(UserFactoryInterface::class);

        // Get a specific user.
        if ($userID) {
            return is_int($userID) ? $userFactory->loadUserById($userID) : $userFactory->loadUserByUsername($userID);
        }

        $current = Application::getApplication()->getIdentity();

        // Enforce type consistency, by overwriting the potential null from getIdentity.
        return $current ?: $userFactory->loadUserById(0);
    }

    /**
     * Gets the name of the user.
     *
     * @param   int|string  $userID
     *
     * @return string
     */
    public static function name(int|string $userID = 0): string
    {
        return self::instance($userID)->name ?: '';
    }

    /**
     * Retrieves the name of the current user.
     * @return string the name of the user
     */
    public static function token(): string
    {
        $user = self::instance();

        if (!$user->email or !$user->registerDate) {
            return '';
        }

        // Joomla documented the wrong type for registerDate which is a string
        return urlencode(password_hash($user->email . $user->registerDate, PASSWORD_BCRYPT));
    }

    /**
     * Gets the account name of the user, optionally by id.
     *
     * @param   int  $userID  the id of
     *
     * @return string
     */
    public static function userName(int $userID = 0): string
    {
        return self::instance($userID)->username ?: '';
    }
}