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

use Joomla\CMS\Language\Text;
use THM\Organizer\Views\HTML\{GridView, ListView};

/**
 * Class renders a message pertaining to an empty result set for the list view.
 */
class EmptyList
{
    /**
     * Renders a notice for an empty result set.
     */
    public static function render(GridView|ListView $view): void
    {
        ?>
        <div class="alert alert-info">
            <span class="fa fa-info-circle" aria-hidden="true"></span>
            <span class="visually-hidden"><?php echo Text::_('INFO'); ?></span>
            <?php echo $view->empty ?: Text::_('JGLOBAL_NO_MATCHING_RESULTS'); ?>
        </div>
        <?php
    }
}