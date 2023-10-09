<?php
/**
 * @package     Organizer
 * @extension   com_organizer
 * @author      James Antrim, <james.antrim@nm.thm.de>
 * @copyright   2020 TH Mittelhessen
 * @license     GNU GPL v.3
 * @link        www.thm.de
 */

namespace Organizer\Fields;

use Organizer\Helpers;

/**
 * Field to load a list of possible item count limits
 */
class LimitboxField extends OptionsField
{
    /**
     * The form field type.
     * @var    string
     */
    public $type = 'Limitbox';

    /**
     * Default options
     * @var  array
     */
    protected $defaultLimits = [5, 10, 15, 20, 25, 30, 50, 100, 200, 500];

    /**
     * Method to get the options to populate to populate list
     * @return  array  The field option objects.
     */
    protected function getOptions(): array
    {
        if (empty($this->options)) {
            $this->options = parent::getOptions();

            $options = [];
            $limits  = $this->defaultLimits;

            // Limits manually specified
            if (isset($this->element['limits'])) {
                $limits = explode(',', $this->element['limits']);
            }

            // User wants to add custom limits
            if (isset($this->element['append'])) {
                $limits = array_unique(array_merge($limits, explode(',', $this->element['append'])));
            }

            // User wants to remove some default limits
            if (isset($this->element['remove'])) {
                $limits = array_diff($limits, explode(',', $this->element['remove']));
            }

            // Order the options
            asort($limits);

            // Add an option to show all?
            $showAll = (!isset($this->element['showall']) or (string) $this->element['showall'] === 'true');

            if ($showAll) {
                $limits[] = 0;
            }

            if (!empty($limits)) {
                foreach ($limits as $value) {
                    $options[] = (object) [
                        'value' => $value,
                        'text' => ($value != 0) ? Helpers\Languages::_('J' . $value) : Helpers\Languages::_('JALL'),
                    ];
                }

                $this->options = array_merge($this->options, $options);
            }
        }

        return $this->options;
    }
}
