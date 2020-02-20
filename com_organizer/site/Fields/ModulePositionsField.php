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

\JLoader::register('ModulesHelper', JPATH_ADMINISTRATOR . '/components/com_modules/Helpers/modules.php');

/**
 * Class creates a select box for module positions.
 */
class ModulePositionsField extends OptionsField
{
	protected $type = 'ModulePositions';

	/**
	 * Method to get the field options.
	 *
	 * @return array  The field option objects.
	 */
	protected function getOptions()
	{
		$clientId = Helpers\Input::getInt('client_id');
		$options  = ModulesHelper::getPositions($clientId);

		return array_merge(parent::getOptions(), $options);
	}
}
