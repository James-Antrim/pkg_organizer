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

use Organizer\Adapters\Database;
use Organizer\Helpers;
use stdClass;

/**
 * Class creates a select box for predefined colors.
 */
class ColorsField extends ColoredOptionsField
{
    /**
     * Type
     * @var    String
     */
    protected $type = 'Colors';

    /**
     * Method to get the field options.
     * @return  array  The field option objects.
     */
    protected function getOptions(): array
    {
        $options = parent::getOptions();

        $tag = Helpers\Languages::getTag();

        $query = Database::getQuery();
        $query->selectX(['DISTINCT c.id AS value', "c.name_$tag AS text", 'c.color'], 'colors AS c')
            ->order('text');
        Database::setQuery($query);

        if (!$colors = Database::loadAssocList()) {
            return $options;
        }

        foreach ($colors as $color) {
            $option        = new stdClass();
            $option->text  = $color['text'];
            $option->value = $color['value'];

            $textColor     = Helpers\Colors::getDynamicTextColor($color['color']);
            $option->style = "background-color:{$color['color']};color:$textColor;";
            $options[]     = $option;
        }

        return $options;
    }
}
