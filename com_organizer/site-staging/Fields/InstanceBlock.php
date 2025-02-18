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

use Joomla\CMS\Form\Field\ListField;
use THM\Organizer\{Adapters\HTML, Helpers, Tables\Blocks};

/** @inheritDoc */
class InstanceBlock extends ListField
{
    /** @inheritDoc */
    protected function getOptions(): array
    {
        $form    = $this->form;
        $options = parent::getOptions();

        // Basic conditions not met
        if (!$date = $form->getValue('date') or !$gridID = $form->getValue('gridID')) {
            return $options;
        }

        if (!$grid = Helpers\Grids::getGrid($gridID)) {
            return $options;
        }

        $grid = json_decode($grid, true);

        if (!is_array($grid) or !array_key_exists('periods', $grid) or !$periods = $grid['periods']) {
            return $options;
        }

        $today = $date === date('Y-m-d');
        $now   = date('H:i');

        foreach ($periods as $period) {
            $endTime = Helpers\Dates::formatTime($period['endTime']);

            if ($today and $endTime < $now) {
                continue;
            }

            $startTime = Helpers\Dates::formatTime($period['startTime']);
            $keys      = ['date' => $date, 'endTime' => $endTime, 'startTime' => $startTime];

            $block = new Blocks();

            if (!$block->load($keys)) {
                $keys['dow'] = date('w', strtotime($date));
                $block->save($keys);
            }

            $options[] = HTML::option($block->id, "$startTime - $endTime");
        }

        return $options;
    }
}
