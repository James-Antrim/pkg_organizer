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

/**
 * Trait for fields whose output should be suppressed if no options beyond those defined in the manifest were found.
 */
trait Dependent
{
    protected array $options = [];

    /** @inheritDoc */
    protected function getInput(): string
    {
        $this->options = $this->getOptions();

        /** @noinspection PhpMultipleClassDeclarationsInspection */
        if (count($this->options) === count(parent::getOptions())) {
            return '';
        }

        /** @noinspection PhpMultipleClassDeclarationsInspection */
        return parent::getInput();
    }

    /** @inheritDoc */
    protected function getLabel(): string
    {
        if (!$this->getInput()) {
            return '';
        }

        /** @noinspection PhpMultipleClassDeclarationsInspection */
        return parent::getLabel();
    }
}