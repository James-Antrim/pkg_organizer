<?php
/**
 * @package     Organizer
 * @extension   pkg_organizer
 * @author      James Antrim, <james.antrim@nm.thm.de>
 * @copyright   2023 TH Mittelhessen
 * @license     GNU GPL v.3
 * @link        www.thm.de
 */

namespace THM\Organizer\Helpers;

use THM\Organizer\Adapters\{Application, Text};
use THM\Organizer\Tables\Coded as Table;

trait Coded
{
    /**
     * Attempts to resolve a given code to an id or id to code.
     *
     * @param int|string $value the id of the resource
     *
     * @return int|string|null
     */
    public static function code(int|string $value): int|null|string
    {
        $table = self::table();

        if (is_int($value) and $table->load($value)) {
            /** @var Table $table */
            return $table->code;
        }


        if (is_string($value)) {
            if ($table->load(['code' => $value])) {
                return $table->id;
            }
            else {
                $identifier = Application::uqClass(self::class);
                echo "<pre>Type $identifier Value $value does not exist.</pre>";
                Application::message(Text::sprintf('IDENTIFIER_UNKNOWN', $identifier, $value));
            }
        }

        return null;
    }
}