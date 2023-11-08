<?php
/**
 * @package     Organizer
 * @extension   com_organizer
 * @author      James Antrim, <james.antrim@nm.thm.de>
 * @copyright   2020 TH Mittelhessen
 * @license     GNU GPL v.3
 * @link        www.thm.de
 */

namespace THM\Organizer\Views;

use THM\Organizer\Adapters\Application;

/**
 * Handles getting and setting of the global view property $_name
 */
trait Named
{
    /**
     * @var string The name of the view class
     * @noinspection PhpPropertyNamingConventionInspection
     */
    protected $_name;

    /**
     * Method to get the object name. Original overwrite to avoid Joomla thrown exception. Currently also used for
     * non-HTML hierarchy views.
     * @return  string  The name of the model
     */
    public function getName(): string
    {
        if (empty($this->_name)) {
            $this->_name = Application::getClass($this);
        }

        return $this->_name;
    }
}