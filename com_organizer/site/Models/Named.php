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

use THM\Organizer\Adapters\Application;

/**
 * Class standardizes the getName function across classes.
 */
trait Named
{
    /**
     * The name of the called class
     */
    protected $name;

    /**
     * Method to get the model name.
     * @return  string  the name of the model
     */
    public function getName(): string
    {
        if (empty($this->name)) {
            $this->name = Application::getClass($this);
        }

        return $this->name;
    }
}