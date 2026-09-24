<?php
/**
 * @package     Organizer
 * @extension   com_organizer
 * @author      James Antrim, <james.antrim@nm.thm.de>
 * @copyright   2026 TH Mittelhessen
 * @license     GNU GPL v.3
 * @link        www.thm.de
 */

namespace THM\Organizer\Views;

use Joomla\CMS\MVC\Model\BaseDatabaseModel;

trait Modeled
{
    public BaseDatabaseModel $model;

    /** @inheritDoc */
    public function getModel($name = null): BaseDatabaseModel
    {
        return $this->model;
    }

    /**
     * Sets the model.
     *
     * @param BaseDatabaseModel $model The model to add to the view.
     *
     * @return  BaseDatabaseModel  The added model.
     */
    public function setModel(BaseDatabaseModel $model): BaseDatabaseModel
    {
        $this->model = $model;

        return $model;
    }

}