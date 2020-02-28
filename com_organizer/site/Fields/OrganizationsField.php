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

use Joomla\CMS\Factory;
use Joomla\CMS\Uri\Uri;
use Organizer\Helpers;

/**
 * Class creates a select box for organizations.
 */
class OrganizationsField extends OptionsField
{
	/**
	 * @var  string
	 */
	protected $type = 'Organizations';

	/**
	 * Returns an array of options
	 *
	 * @return array  the organization options
	 */
	protected function getOptions()
	{
		$options       = parent::getOptions();
		$organizations = Helpers\Organizations::getOptions(true, $this->getAttribute('access', ''));

		return count($organizations) > 1 ? array_merge($options, $organizations) : $organizations;
	}
}
