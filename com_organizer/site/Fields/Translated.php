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
        $label = $this->element['label'] ?? '';
        if ($label and $this->isKey($label)) {
            $label = str_starts_with($label, 'ORGANIZER_') ? $label : "ORGANIZER_$label";

            $tip = $this->element['description'] ?? "{$label}_DESC";
            $tip = str_starts_with($tip, 'ORGANIZER_') ? $tip : "ORGANIZER_$tip";

            $this->element['label'] = Text::_($label);
            $this->description      = Text::_($tip);
        }

        // TODO remove automatic description supplementation

        /** @noinspection PhpMultipleClassDeclarationsInspection */
        return parent::getLayoutData();
    }

    /**
     * Checks whether the given string is a localization key.
     *
     * @param   string  $string
     *
     * @return bool
     */
    private function isKey(string $string): bool
    {
        preg_match('/^[A-Z_]+$/', $string, $matches);
        return !empty($matches);
    }
}