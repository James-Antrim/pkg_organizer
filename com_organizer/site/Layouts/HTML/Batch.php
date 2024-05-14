<?php
/**
 * @package     Organizer
 * @extension   com_organizer
 * @author      James Antrim, <james.antrim@nm.thm.de>
 * @copyright   2022 TH Mittelhessen
 * @license     GNU GPL v.3
 * @link        www.thm.de
 */

namespace THM\Organizer\Layouts\HTML;

use THM\Organizer\Adapters\{Application, Text};
use THM\Organizer\Views\HTML\ListView;

/**
 * Class renders elements of a modal element for batch processing.
 */
class Batch
{
    /**
     * Renders a batch form for a list view.
     *
     * @param   ListView  $view  the view being rendered
     */
    public static function render(ListView $view): void
    {
        $batch = $view->filterForm->getGroup('batch');
        ?>
        <div class="p-3">
            <?php foreach ($batch as $field) : ?>
                <div class="control-group">
                    <div class="control-label">
                        <?php echo $field->__get('label'); ?>
                    </div>
                    <div class="controls">
                        <?php echo $field->__get('input'); ?>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
        <div class="btn-toolbar p-3">
            <joomla-toolbar-button task="<?php echo Application::getClass($view); ?>.batch" class="ms-auto">
                <button type="button" class="btn btn-success"><?php echo Text::_('PROCESS'); ?></button>
            </joomla-toolbar-button>
        </div>
        <?php
    }
}