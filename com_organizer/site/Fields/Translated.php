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
 * Trait resolves language constants with the addition of the component prefix and languages helper.
 */
trait Translated
{
	/**
	 * Method to get the data to be passed to the layout for rendering.
	 *
	 * @return  array
	 */
	protected function getLayoutData(): array
	{
		if (!empty($this->element['label']))
		{
			$labelConstant          = 'ORGANIZER_' . $this->element['label'];
			$descriptionConstant    = $labelConstant . '_DESC';
			$this->element['label'] = Helpers\Languages::_($labelConstant);
			$this->description      = Helpers\Languages::_($descriptionConstant);
		}

		return parent::getLayoutData();
	}
}