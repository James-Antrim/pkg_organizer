<?php
/**
 * @package     Organizer
 * @extension   com_organizer
 * @author      James Antrim, <james.antrim@nm.thm.de>
 * @copyright   2020 TH Mittelhessen
 * @license     GNU GPL v.3
 * @link        www.thm.de
 */

namespace THM\Organizer\Views\HTML;

use THM\Organizer\Adapters\{Input, Text, Toolbar};

/**
 * Import classes provide a form for uploading files.
 */
abstract class ImportView extends FormView
{
    use Abstracted;

    /** @inheritDoc */
    protected function addToolbar(array $buttons = [], string $constant = ''): void
    {
        Input::set('hidemainmenu', true);
        $controller = $this->getName();
        $key        = str_replace('Import', 'IMPORT_', $controller);
        $this->title(strtoupper($key));

        $toolbar = Toolbar::instance();
        $toolbar->save($controller . '.import', Text::_('IMPORT'))->icon('fa fa-upload');
        $toolbar->cancel("$controller.cancel");
    }
}
