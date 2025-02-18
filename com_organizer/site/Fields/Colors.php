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

use stdClass;
use THM\Organizer\Adapters\{Application, Database as DB};
use THM\Organizer\Helpers\Colors as Helper;

/** @inheritDoc */
class Colors extends ColoredOptions
{
    /** @inheritDoc */
    protected function getOptions(): array
    {
        $options = parent::getOptions();

        $tag = Application::tag();

        $query = DB::query();
        $query->select(['DISTINCT ' . DB::qn('c.id', 'value'), DB::qn("c.name_$tag", 'text'), DB::qn('c.color')])
            ->from(DB::qn('#__organizer_colors', 'c'))
            ->order(DB::qn('text'));
        DB::set($query);

        if (!$colors = DB::arrays()) {
            return $options;
        }

        foreach ($colors as $color) {
            $option        = new stdClass();
            $option->text  = $color['text'];
            $option->value = $color['value'];

            $textColor     = Helper::textColor($color['color']);
            $option->style = "background-color:{$color['color']};color:$textColor;";
            $options[]     = $option;
        }

        return $options;
    }
}
