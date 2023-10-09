<?php
/**
 * @package     Organizer
 * @extension   com_organizer
 * @author      James Antrim, <james.antrim@nm.thm.de>
 * @copyright   2020 TH Mittelhessen
 * @license     GNU GPL v.3
 * @link        www.thm.de
 */

use Organizer\Helpers;

// Load the form filters
$filters = $this->filterForm->getGroup('filter');
?>
<?php if ($filters) : ?>
    <?php foreach ($filters as $fieldName => $field) : ?>
        <?php $showON = JFormHelper::parseShowOnConditions($field->showon, $field->formControl, $field->group); ?>
        <?php if ($fieldName !== 'filter_search') : ?>
            <?php $dataShowOn = ''; ?>
            <?php if ($field->showon) : ?>
                <?php Helpers\HTML::_('bootstrap.framework'); ?>
                <?php Helpers\HTML::_('script', 'jui/cms.js', ['version' => 'auto', 'relative' => true]); ?>
                <?php $dataShowOn = " data-showon='" . json_encode($showON, JSON_UNESCAPED_UNICODE) . "'"; ?>
            <?php endif; ?>
            <div class="js-stools-field-filter"<?php echo $dataShowOn; ?>>
                <?php echo $field->input; ?>
            </div>
        <?php endif; ?>
    <?php endforeach; ?>
<?php endif; ?>
