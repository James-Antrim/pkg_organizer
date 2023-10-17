<?php
/**
 * @package     Organizer
 * @extension   com_organizer
 * @author      James Antrim, <james.antrim@nm.thm.de>
 * @copyright   2020 TH Mittelhessen
 * @license     GNU GPL v.3
 * @link        www.thm.de
 */

use Joomla\Registry\Registry;
use THM\Organizer\Adapters\{Application, Text};

// Receive overridable options
$options = empty($options) ? [] : $options;

if (is_array($options)) {
    $options = new Registry($options);
}

$filters    = $this->filterForm->getGroup('filter');
$mobile     = Application::mobile();
$filterText = $mobile ? "<span class=\"icon-filter\"></span>" : Text::_('ORGANIZER_SEARCH_TOOLS') . ' <span class="caret"></span>';
$resetText  = $mobile ? "<span class=\"icon-undo-2\"></span>" : Text::_('ORGANIZER_RESET');

$searchButton     = $options->get('searchButton', true);
$showFilterButton = empty($filters['filter_search']) ? (bool) count($filters) : count($filters) > 1;

?>

<?php if (!empty($filters['filter_search'])) : ?>
    <?php if ($searchButton) : ?>
        <label for="filter_search" class="element-invisible">
            <?php echo Text::_('ORGANIZER_SEARCH'); ?>
        </label>
        <div class="btn-wrapper input-append">
            <?php echo $filters['filter_search']->input; ?>
            <?php if ($filters['filter_search']->description) : ?>
                <?php JHtmlBootstrap::tooltip('#filter_search',
                    ['title' => Text::_($filters['filter_search']->description)]); ?>
            <?php endif; ?>
            <button type="submit" class="btn hasTooltip"
                    title="<?php echo Text::tooltip('ORGANIZER_SEARCH'); ?>"
                    aria-label="<?php echo Text::_('ORGANIZER_SEARCH'); ?>">
                <span class="icon-search" aria-hidden="true"></span>
            </button>
        </div>
    <?php endif; ?>
<?php endif; ?>
<?php if ($showFilterButton) : ?>
    <div class="btn-wrapper hidden-phone">
        <button type="button" class="btn hasTooltip js-stools-btn-filter"
                title="<?php echo Text::tooltip('ORGANIZER_SEARCH_TOOLS_DESC'); ?>"
                aria-label="<?php echo Text::_('ORGANIZER_SEARCH_TOOLS'); ?>">
            <?php echo $filterText ?>
        </button>
    </div>
<?php endif; ?>
<?php if (!empty($filters['filter_search']) or $showFilterButton) : ?>
    <div class="btn-wrapper">
        <button type="button" class="btn hasTooltip js-stools-btn-clear"
                title="<?php echo Text::tooltip('ORGANIZER_RESET'); ?>"
                aria-label="<?php echo Text::_('ORGANIZER_RESET'); ?>">
            <?php echo $resetText; ?>
        </button>
    </div>
<?php endif;
