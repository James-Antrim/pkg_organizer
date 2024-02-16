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

use THM\Organizer\Adapters\{Application, HTML};
use THM\Organizer\Views\HTML\ListView;

/**
 * Creates the HTML element with list filtering, formatting and search control elements.
 */
class ListTools
{
    /**
     * Renders list headers.
     *
     * @param   ListView  $view  the view being displayed
     */
    public static function render(ListView $view): void
    {
        if (empty($view->filterForm)) {
            return;
        }

        // Checks if the filters button should exist.
        $filters     = $view->filterForm->getGroup('filter');
        $filterCount = count($filters);

        $searchExists = isset($filters['filter_search']);
        $filtersExist = $searchExists ? $filterCount > 1 : (bool) $filterCount;

        $state   = $view->get('state');
        $options = [
            'activeDirection' => $view->escape($state->get('list.direction')),
            'activeOrder'     => $view->escape($state->get('list.ordering')),
            'defaultLimit'    => Application::getApplication()->get('list_limit', 50),
            'orderSelector'   => '#list_fullordering',
            'searchSelector'  => '#filter_search'
        ];

        $class = ($filtersExist and !empty($view->activeFilters)) ? ' js-stools-container-filters-visible' : '';
        HTML::_('searchtools.form', '#adminForm', $options);
        ?>
        <div class="js-stools" role="search">
            <div class="js-stools-container-bar">
                <div class="btn-toolbar">
                    <?php SearchBar::render($view); ?>
                    <?php ListBar::render($view); ?>
                </div>
            </div>
            <div class="js-stools-container-filters clearfix<?php echo $class; ?>">
                <?php if ($filtersExist) : ?>
                    <?php FilterBar::render($view); ?>
                <?php endif; ?>
            </div>
        </div>
        <?php
    }

}