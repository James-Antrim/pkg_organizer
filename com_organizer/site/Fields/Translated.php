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

use THM\Organizer\Adapters\Text;

/**
 * Trait resolves language constants with the addition of the component prefix and languages helper.
 */
trait Translated
{
    /**
     * Gets the field's protected type attribute
     * @return string
     */
    public function getType(): string
    {
        return $this->type;
    }

    /**
     * Method to get the data to be passed to the layout for rendering.
     * @return  array
     */
    protected function getLayoutData(): array
    {
        if (!empty($this->element['label'])) {
            $label = $this->element['label'];
            $label = strpos($label, 'ORGANIZER_') === 0 ? $label : "ORGANIZER_$label";

            $tip = $this->element['description'] ?? "{$label}_DESC";
            $tip = strpos($tip, 'ORGANIZER_') === 0 ? $tip : "ORGANIZER_$tip";
            $tip = strpos($tip, '_DESC') === strlen($tip) - 5 ? $tip : "{$tip}_DESC";

            $this->element['label'] = Text::_($label);
            $this->description      = Text::_($tip);
        }

        /** @noinspection PhpMultipleClassDeclarationsInspection */
        return parent::getLayoutData();
    }
}