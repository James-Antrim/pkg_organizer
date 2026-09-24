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

use Joomla\CMS\Document\Document;
use Joomla\CMS\MVC\{Controller\BaseController, Model\BaseDatabaseModel, View\ViewInterface};

/**
 * Adds modelling as explicitly required by the ViewInterface and the document property as implicitly required by BaseController
 * @see BaseController::display(), BaseController::prepareViewModel(), ViewInterface
 */
trait Modeled
{
    /** @see BaseController::display() */
    public Document $document;

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