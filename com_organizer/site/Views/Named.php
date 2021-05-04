<?php
/**
 * @package     Organizer
 * @extension   com_organizer
 * @author      James Antrim, <james.antrim@nm.thm.de>
 * @copyright   2020 TH Mittelhessen
 * @license     GNU GPL v.3
 * @link        www.thm.de
 */

namespace Organizer\Views;

use Organizer\Helpers;

trait Named
{
    /**
     * The name of the view
     *
     * @var    string
     */
    protected $_name = null;

    /**
     * Method to get the object name. Original overwrite to avoid Joomla thrown exception. Currently also used for
     * non-HTML hierarchy views.
     *
     * @return  string  The name of the model
     */
    public function getName(): string
    {
        if (empty($this->_name)) {
            $this->_name = Helpers\OrganizerHelper::getClass($this);
        }

        return $this->_name;
    }
}