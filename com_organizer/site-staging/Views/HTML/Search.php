<?php
/**
 * @package     Organizer
 * @extension   com_organizer
 * @author      James Antrim, <james.antrim@nm.thm.de>
 * @copyright   2020 TH Mittelhessen
 * @license     GNU GPL v.3
 * @link        www.thm.de
 */

namespace Organizer\Views\HTML;

use Joomla\CMS\Uri\Uri;
use Organizer\Adapters;

/**
 * Class loads the query's results into the display context.
 */
class Search extends BaseView
{
	public $query;

	public $results;

	/**
	 * loads model data into view context
	 *
	 * @param   string  $tpl  the name of the template to be used
	 *
	 * @return void
	 */
	public function display($tpl = null)
	{
		$this->tag = Languages::getTag();
		// Use language_selection layout
		$this->query   = OrganizerHelper::getInput()->getString('search', '');
		$this->results = $this->getModel()->getResults();

		$this->modifyDocument();
		parent::display($tpl);
	}

	/**
	 * Modifies document variables and adds links to external files
	 *
	 * @return void
	 */
	protected function modifyDocument()
	{
		parent::modifyDocument();

		Adapters\Document::setTitle(Languages::_('ORGANIZER_SEARCH'));
		Adapters\Document::addStyleSheet(Uri::root() . 'components/com_organizer/css/search.css');
	}
}
