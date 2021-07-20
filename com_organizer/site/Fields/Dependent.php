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

/**
 * Trait for fields whose output should be suppressed if no options beyond those defined in the manifest were found.
 */
trait Dependent
{
	/**
	 * Suppresses field display when there are no options available because of context dependencies.
	 *
	 * @return  string  The field input markup.
	 */
	protected function getInput(): string
	{
		$this->options = $this->getOptions();

		if (count($this->options) === count($this->getDefaultOptions()))
		{
			return '';
		}

		return $this->getBaseInput();
	}

	/**
	 * Checks whether the field input would be displayed.
	 *
	 * @return bool
	 */
	public function hasInput(): bool
	{
		return (bool) self::getInput();
	}
}