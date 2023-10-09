<?php
/**
 * @package     Organizer\Fields
 * @extension   com_organizer
 * @author      James Antrim, <james.antrim@nm.thm.de>
 * @copyright   2020 TH Mittelhessen
 * @license     GNU GPL v.3
 * @link        www.thm.de
 */

namespace Organizer\Fields;

use Joomla\CMS\Form\FormField;
use Organizer\Helpers;
use Organizer\Helpers\Languages;

/**
 * Class provides a field by which to add a participant to a given event related resource.
 */
class PageField extends FormField
{
    use Translated;

    /**
     * The form field type.
     * @var    string
     */
    protected $type = 'Page';

    /**
     * Method to get the field input markup for a generic list.
     * @return  string  The field input markup.
     */
    protected function getInput(): string
    {
        $app       = Helpers\OrganizerHelper::getApplication();
        $context   = 'com_organizer.instances';
        $interval  = $app->getUserStateFromRequest("$context.list.interval", 'list_interval', '', 'string');
        $interval  = Helpers\Input::getListItems()->get('interval', $interval);
        $intervals = ['day', 'week', 'month'];

        if (!in_array($interval, $intervals)) {
            return '';
        }

        $direction = $this->getAttribute('direction');
        $valid     = ['backward', 'forward'];

        if (!in_array($direction, $valid)) {
            return '';
        }

        $default = $app->getUserStateFromRequest("$context.list.date", 'list_date', '', 'string');
        $default = $default ?: date('Y-m-d');
        $date    = Helpers\Input::getListItems()->get('date', $default);
        $title   = '';
        $stamp   = strtotime($date);
        $target  = '';

        if ($direction === 'forward') {
            $icon = '<span class="icon-next" aria-hidden="true"></span>';

            switch ($interval) {
                case 'day':
                    $dow    = date('N', $stamp);
                    $bump   = $dow === '6' ? '+2 days' : '+1 day';
                    $target = strtotime($bump, $stamp);
                    $title  = Languages::_('ORGANIZER_NEXT_DAY');
                    break;
                case 'month':
                    $target = strtotime('+1 month', $stamp);
                    $title  = Languages::_('ORGANIZER_NEXT_MONTH');
                    break;
                case 'week':
                    $target = strtotime('+7 days', $stamp);
                    $title  = Languages::_('ORGANIZER_NEXT_WEEK');
                    break;
            }
        } else {
            $icon = '<span class="icon-previous" aria-hidden="true"></span>';

            switch ($interval) {
                case 'day':
                    $dow    = date('N', $stamp);
                    $bump   = $dow === '1' ? '-2 days' : '-1 day';
                    $target = strtotime($bump, $stamp);
                    $title  = Languages::_('ORGANIZER_PREVIOUS_DAY');
                    break;
                case 'month':
                    $target = strtotime('-1 month', $stamp);
                    $title  = Languages::_('ORGANIZER_PREVIOUS_MONTH');
                    break;
                case 'week':
                    $target = strtotime('-7 days', $stamp);
                    $title  = Languages::_('ORGANIZER_PREVIOUS_WEEK');
                    break;
            }
        }

        $target     = date('Y-m-d', $target);
        $attributes = [
            "aria-label=\"$title\"",
            'class="btn hasTooltip"',
            "onclick=\"document.getElementById('list_date').value='$target';this.form.submit();\"",
            "title=\"$title\"",
            'type="submit"'
        ];

        return '<button ' . implode(' ', $attributes) . '>' . $icon . '</button>';
    }
}