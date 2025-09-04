<?php
/**
 * @package     Organizer
 * @extension   com_organizer
 * @author      James Antrim, <james.antrim@nm.thm.de>
 * @copyright   2020 TH Mittelhessen
 * @license     GNU GPL v.3
 * @link        www.thm.de
 */

namespace THM\Organizer\Controllers;

use THM\Organizer\Adapters\{Application, Input};
use THM\Organizer\Helpers\Grids as Helper;
use THM\Organizer\Tables\Grids as Table;

/** @inheritDoc */
class Grids extends ListController
{
    protected string $item = 'Grid';

    /**
     * Toggles the selected grid to be the new default.
     * @return void
     */
    public function default(): void
    {
        $this->authorize();

        $selected = Input::selectedID();
        $table    = new Table();

        // Entry not found or already set to default
        if (!Helper::resetDefault()) {
            $message = 'TABLE_DEFAULT_NOT_RESET';
            $type    = Application::ERROR;
        }
        elseif (!$table->load($selected)) {
            $message = '404';
            $type    = Application::WARNING;
        }
        elseif ($table->isDefault) {
            $message = 'IS_CURRENT_DEFAULT';
            $type    = Application::WARNING;
        }
        else {
            $table->isDefault = 1;
            if ($table->store()) {
                $message = 'DEFAULT_SET';
                $type    = Application::MESSAGE;
            }
            else {
                $message = 'TABLE_DEFAULT_NOT_SET';
                $type    = Application::ERROR;
            }
        }

        Application::message($message, $type);
        $this->setRedirect("$this->baseURL&view=" . strtolower(Application::uqClass($this)));
    }
}
