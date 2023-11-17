<?php
/**
 * @package     Organizer
 * @extension   com_organizer
 * @author      James Antrim, <james.antrim@nm.thm.de>
 * @copyright   2020 TH Mittelhessen
 * @license     GNU GPL v.3
 * @link        www.thm.de
 */

namespace THM\Organizer\Views\XLS;

use JetBrains\PhpStorm\NoReturn;

/**
 * Class instantiates and renders an XLS File with the room statistics.
 */
class RoomStatistics extends BaseView
{
    use PHPExcelDependent;

    /**
     * Sets context variables and renders the view.
     *
     * @param   string|null  $tpl  The name of the template file to parse; automatically searches through the template
     *                             paths.
     *
     * @return void
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    #[NoReturn] public function display(string $tpl = null): void
    {
        $model = $this->getModel();

        require_once __DIR__ . '/tmpl/document.php';
        $export = new \OrganizerTemplateRoom_Statistics_XLS($model);
        $export->render();
        ob_flush();
    }
}
