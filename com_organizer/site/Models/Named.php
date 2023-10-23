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
     * The form context. (com_organizer.<model><.menuID>)
     * @var string $context
     */
    protected $context;

    /**
     * The name of the called class.
     * @var string $name
     */
    protected $name;

    /**
     * Sets the form context to prevent bleeding.
     * @return void
     */
    public function setContext(): void
    {
        if (empty($this->context)) {
            $this->context = strtolower($this->option . '.' . $this->getName());

            // Make sure the filters from different instances of the same model don't bleed
            if ($menuItem = Application::getMenuItem() and $menuID = $menuItem->id) {
                $this->context .= '.' . $menuID;
            }
        }
    }

    /**
     * Method to get the model name.
     * @return  string  the name of the model
     */
    public function getName(): string
    {
        if (empty($this->name) or empty($this->option)) {
            $this->name   = Application::getClass($this);
            $this->option = 'com_organizer';
        }

        return $this->name;
    }
}