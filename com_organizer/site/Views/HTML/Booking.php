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

use Joomla\CMS\Factory;
use Joomla\CMS\Toolbar\Toolbar;
use Joomla\CMS\Uri\Uri;
use Organizer\Helpers;
use Organizer\Helpers\Languages;
use Organizer\Tables;

/**
 * Class loads persistent information a filtered set of course participants into the display context.
 */
class Booking extends Participants
{
	protected $rowStructure = [
		'checkbox' => '',
		'fullName' => 'value',
		'event'    => 'value',
		'complete' => 'value'
	];

	/**
	 * Method to generate buttons for user interaction
	 *
	 * @return void
	 */
	protected function addToolBar()
	{
		$bookingID = Helpers\Input::getID();
		$booking   = new Tables\Bookings();
		$booking->load($bookingID);
		$title = Languages::_('ORGANIZER_PARTICIPANTS') . " $booking->code";

		Helpers\HTML::setTitle($title, 'users');

		$toolbar = Toolbar::getInstance();

		$script      = "onclick=\"jQuery('#modal-notes').modal('show'); return true;\"";
		$batchButton = "<button id=\"booking-notes\" data-toggle=\"modal\" class=\"btn btn-small\" $script>";

		$title       = Languages::_('ORGANIZER_NOTES');
		$batchButton .= '<span class="icon-pencil-2" title="' . $title . '"></span>' . " $title";

		$batchButton .= '</button>';

		$toolbar->appendButton('Custom', $batchButton, 'batch');
	}

	/**
	 * Function determines whether the user may access the view.
	 *
	 * @return void
	 */
	protected function authorize()
	{
		if (!$bookingID = Helpers\Input::getID())
		{
			Helpers\OrganizerHelper::error(400);
		}

		if (!Helpers\Can::manage('booking', $bookingID))
		{
			Helpers\OrganizerHelper::error(403);
		}
	}

	/**
	 * Method to create a list output
	 *
	 * @param   string  $tpl  The name of the template file to parse; automatically searches through the template paths.
	 *
	 * @return void
	 * @noinspection PhpDocSignatureInspection
	 */
	public function display($tpl = null)
	{
		// Set batch template path
		$this->batch = ['item_notes'];

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

		Factory::getDocument()->addStyleSheet(Uri::root() . 'components/com_organizer/css/modal.css');
	}

	/**
	 * Function to set the object's headers property
	 *
	 * @return void sets the object headers property
	 */
	protected function setHeaders()
	{
		$ordering  = $this->state->get('list.ordering');
		$direction = $this->state->get('list.direction');
		$headers   = [
			'checkbox' => Helpers\HTML::_('grid.checkall'),
			'fullName' => Helpers\HTML::sort('NAME', 'fullName', $direction, $ordering),
			'event'    => Helpers\HTML::sort('EVENT', 'event', $direction, $ordering),
			'complete' => Languages::_('ORGANIZER_PROFILE_COMPLETE')
		];

		$this->headers = $headers;
	}

	/**
	 * Creates a subtitle element from the term name and the start and end dates of the course.
	 *
	 * @return void modifies the course
	 */
	protected function setSubtitle()
	{
		$bookingID = Helpers\Input::getID();
		$subTitle  = Helpers\Bookings::getNames($bookingID);

		$subTitle[] = Helpers\Bookings::getDateTimeDisplay($bookingID);

		$this->subtitle = '<h6 class="sub-title">' . implode('<br>', $subTitle) . '</h6>';
	}

	/**
	 * Processes the items in a manner specific to the view, so that a generalized  output in the layout can occur.
	 *
	 * @return void processes the class items property
	 */
	protected function structureItems()
	{
		$index = 0;
		$link  = 'index.php?option=com_organizer&view=participant_edit&id=';

		$structuredItems = [];

		foreach ($this->items as $item)
		{
			$item->fullName = $item->forename ? $item->fullName : $item->surname;

			if ($item->complete)
			{
				$label = Languages::_('ORGANIZER_PROFILE_COMPLETE');
				$icon  = 'checked';
			}
			else
			{
				$label = Languages::_('ORGANIZER_PROFILE_INCOMPLETE');
				$icon  = 'unchecked';
			}

			$item->complete = Helpers\HTML::icon("checkbox-$icon", $label, true);

			$structuredItems[$index] = $this->structureItem($index, $item, $link . $item->id);
			$index++;
		}

		$this->items = $structuredItems;
	}
}
