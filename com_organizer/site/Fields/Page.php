<?php
/**
 * @package     Organizer\Fields
 * @extension   com_organizer
 * @author      James Antrim, <james.antrim@nm.thm.de>
 * @copyright   2020 TH Mittelhessen
 * @license     GNU GPL v.3
 * @link        www.thm.de
 */

namespace THM\Organizer\Fields;

use Joomla\CMS\Form\FormField;
use THM\Organizer\Adapters\{Application, Input, Text};

/**
 * Class provides a field by which to add a participant to a given event related resource.
 */
class Page extends FormField
{
    use Translated;

    /**
     * Method to get the field input markup for a generic list.
     * @return  string  The field input markup.
     */
    protected function getInput(): string
    {
        $context   = 'com_organizer.instances';
        $interval  = Application::getUserRequestState("$context.list.interval", 'list_interval', '', 'string');
        $interval  = Input::getListItems()->get('interval', $interval);
        $intervals = ['day', 'week', 'month'];

        if (!in_array($interval, $intervals)) {
            return '';
        }

        $direction = $this->getAttribute('direction');
        $valid     = ['backward', 'forward'];

        if (!in_array($direction, $valid)) {
            return '';
        }

        $default = Application::getUserRequestState("$context.list.date", 'list_date', '', 'string');
        $default = $default ?: date('Y-m-d');
        $date    = Input::getListItems()->get('date', $default);
        $title   = '';
        $stamp   = strtotime($date);
        $target  = '';

        if ($direction === 'forward') {
            $icon = '<span class="fa fa-step-forward" aria-hidden="true"></span>';

            switch ($interval) {
                case 'day':
                    $dow    = date('N', $stamp);
                    $bump   = $dow === '6' ? '+2 days' : '+1 day';
                    $target = strtotime($bump, $stamp);
                    $title  = Text::_('NEXT_DAY');
                    break;
                case 'month':
                    $target = strtotime('+1 month', $stamp);
                    $title  = Text::_('NEXT_MONTH');
                    break;
                case 'week':
                    $target = strtotime('+7 days', $stamp);
                    $title  = Text::_('NEXT_WEEK');
                    break;
            }
        }
        else {
            $icon = '<span class="fa fa-step-backward" aria-hidden="true"></span>';

            switch ($interval) {
                case 'day':
                    $dow    = date('N', $stamp);
                    $bump   = $dow === '1' ? '-2 days' : '-1 day';
                    $target = strtotime($bump, $stamp);
                    $title  = Text::_('PREVIOUS_DAY');
                    break;
                case 'month':
                    $target = strtotime('-1 month', $stamp);
                    $title  = Text::_('PREVIOUS_MONTH');
                    break;
                case 'week':
                    $target = strtotime('-7 days', $stamp);
                    $title  = Text::_('PREVIOUS_WEEK');
                    break;
            }
        }

        $target     = date('Y-m-d', $target);
        $attributes = [
            "aria-label=\"$title\"",
            'class="btn btn-primary hasTooltip"',
            "onclick=\"document.getElementById('list_date').value='$target';this.form.submit();\"",
            "title=\"$title\"",
            'type="submit"'
        ];

        return '<button ' . implode(' ', $attributes) . '>' . $icon . '</button>';
    }
}