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

use THM\Organizer\Views\HTML\ListView;

/**
 * Creates the HTML 'list' bar element, where list formatting elements and filters which should always be displayed are
 * located.
 */
class ListBar
{
    /**
     * Renders the list bar.
     *
     * @param   ListView  $view
     */
    public static function render(ListView $view): void
    {
        if (empty($view->filterForm)) {
            return;
        }

        if (!$list = $view->filterForm->getGroup('list')) {
            return;
        }

        ?>
        <div class="ordering-select">
            <?php foreach ($list as $field) : ?>
                <div class="js-stools-field-list">
                    <span class="visually-hidden"><?php echo $field->__get('label'); ?></span>
                    <?php echo $field->__get('input'); ?>
                </div>
            <?php endforeach; ?>
        </div>
        <?php
    }
}