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
 * Class creates a select box for (degree) programs.
 */
class ProgramsField extends OptionsField
{
	/**
	 * @var  string
	 */
	protected $type = 'Programs';

	/**
	 * Method to get the field options.
	 *
	 * @return  array  The field option objects.
	 */
	protected function getOptions(): array
	{
		$options = parent::getOptions();

		$participantEdit = (strtolower(str_replace('_', '', Helpers\Input::getView())) === 'participantedit');
		if ($participantEdit and Helpers\Can::administrate())
		{
			$options[] = Helpers\HTML::_('select.option', -1, Helpers\Languages::_('ORGANIZER_UNKNOWN'));
		}

		$access   = $this->adminContext ? $this->getAttribute('access', '') : '';
		$programs = Helpers\Programs::getOptions($access);

		return array_merge($options, $programs);
	}
}
