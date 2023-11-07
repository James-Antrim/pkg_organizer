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
use THM\Organizer\Views\HTML\ListView;

/**
 * Creates the HTML search bar element.
 */
class SearchBar
{
    /**
     * Renders the search bar.
     *
     * @param   ListView  $view
     */
    public static function render(ListView $view): void
    {
        if (empty($view->filterForm)) {
            return;
        }

        $filters      = $view->filterForm->getGroup('filter');
        $searchExists = isset($filters['filter_search']);
        $filtersExist = $searchExists ? count($filters) > 1 : (bool) $filters;

        if (isset($filters['filter_search'])) {
            $search = $view->filterForm->getGroup('filter')['filter_search'];

            ?>
            <div class="filter-search-bar btn-group">
                <div class="input-group">
                    <?php echo $search->__get('input'); ?>
                    <?php if ($search->__get('description')) : ?>
                        <div role="tooltip"
                             id="<?php echo ($search->__get('id') ?: $search->__get('name')) . '-desc'; ?>"
                             class="filter-search-bar__description">
                            <?php echo htmlspecialchars(Text::_($search->__get('description')), ENT_COMPAT); ?>
                        </div>
                    <?php endif; ?>
                    <span class="filter-search-bar__label visually-hidden"><?php echo $search->__get('label'); ?></span>
                    <button type="submit" class="filter-search-bar__button btn btn-primary"
                            aria-label="<?php echo Text::_('JSEARCH_FILTER_SUBMIT'); ?>">
                        <span class="filter-search-bar__button-icon icon-search" aria-hidden="true"></span>
                    </button>
                </div>
            </div>
            <?php
        }
        ?>
        <div class="filter-search-actions btn-group">
            <?php if ($filtersExist) : ?>
                <button type="button" class="filter-search-actions__button btn btn-primary js-stools-btn-filter">
                    <?php echo Text::_('JFILTER_OPTIONS'); ?>
                    <span class="icon-angle-down" aria-hidden="true"></span>
                </button>
                <button type="button" class="filter-search-actions__button btn btn-primary js-stools-btn-clear">
                    <?php echo Text::_('JSEARCH_FILTER_CLEAR'); ?>
                </button>
            <?php endif; ?>
        </div>
        <?php
    }
}