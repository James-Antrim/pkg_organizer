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

trait Dependent
{
	/**
	 * Suppresses field display when there are no options available because of context dependencies.
	 *
	 * @return  string  The field input markup.
	 */
	protected function getInput()
	{
		$this->options = (array) $this->getOptions();
		$parentOptions = parent::getOptions();

		if (count($this->options) === count($parentOptions))
		{
			return '';
		}

		return parent::getInput();
	}

}