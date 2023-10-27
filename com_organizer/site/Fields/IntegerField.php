<?php
/**
 * @package     Organizer
 * @extension   com_organizer
 * @author      James Antrim, <james.antrim@nm.thm.de>
 * @copyright   2020 TH Mittelhessen
 * @license     GNU GPL v.3
 * @link        www.thm.de
 */

namespace THM\Organizer\Fields;

use THM\Organizer\Adapters\{HTML, Text};

/**
 * Provides a select list of int with specified first, last and step values.
 */
class IntegerField extends OptionsField
{
    /**
     * The form field type.
     * @var    string
     */
    protected $type = 'Integer';

    /**
     * Method to get the field options.
     * @return  array  The field option objects.
     */
    protected function getOptions(): array
    {
        $options = [];

        // Initialize some field attributes.
        $first  = (int) $this->element['first'];
        $last   = (int) $this->element['last'];
        $prefix = $this->element['prefix'];
        $step   = (int) $this->element['step'];
        $unit   = $this->element['unit'];

        // Sanity checks.
        if ($step == 0) {
            // Step of 0 will create an endless loop.
            return $options;
        }
        elseif ($first < $last && $step < 0) {
            // A negative step will never reach the last number.
            return $options;
        }
        elseif ($first > $last && $step > 0) {
            // A position step will never reach the last number.
            return $options;
        }
        elseif ($step < 0) {
            // Build the options array backwards.
            for ($number = $first; $number >= $last; $number += $step) {
                $text = empty($prefix) ? '' : $prefix;
                $text .= $number;
                $text .= empty($unit) ? '' : ' ' . Text::_("ORGANIZER_$unit");

                $options[] = HTML::option($number, $text);
            }
        }
        else {
            // Build the options array.
            for ($number = $first; $number <= $last; $number += $step) {
                $text = empty($prefix) ? '' : $prefix;
                $text .= $number;
                $text .= empty($unit) ? '' : ' ' . Text::_("ORGANIZER_$unit");

                $options[] = HTML::option($number, $text);
            }
        }

        // Merge any additional options in the XML definition.
        return array_merge(parent::getOptions(), $options);
    }
}
