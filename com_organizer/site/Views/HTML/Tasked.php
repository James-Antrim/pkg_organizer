<?php
/**
 * @package     Organizer
 * @extension   com_organizer
 * @author      James Antrim, <james.antrim@nm.thm.de>
 * @copyright   2023 TH Mittelhessen
 * @license     GNU GPL v.3
 * @link        www.thm.de
 */

namespace THM\Organizer\Views\HTML;

use THM\Organizer\Helpers\Can;

/**
 * Handles code common for HTML output of outstanding tasks in the view context.
 */
trait Tasked
{
    /** @var array the open items. */
    public array $toDo = [];

    /**
     * Creates a standardized output for development tasks in the view context.
     *
     * @return void
     */
    public function renderTasks(): void
    {
        if (Can::administrate() and $this->toDo) {
            echo '<h6>Tasks:</h6>';
            echo '<ul>';
            foreach ($this->toDo as $toDo) {
                echo "<li>$toDo</li>";
            }
            echo '</ul>';
        }
    }
}