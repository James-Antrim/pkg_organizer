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
use Organizer\Helpers;
use Organizer\Helpers\Languages;

/**
 * Class loads the schedule form into the display context.
 */
class ScheduleItem extends BaseView
{
	/**
	 * format for displaying dates
	 *
	 * @var string
	 */
	protected $dateFormat;

	/**
	 * default time grid, loaded first
	 *
	 * @var object
	 */
	public $grids;

	/**
	 * the organization for this schedule, chosen in menu options
	 *
	 * @var string
	 */
	protected $params;

	/**
	 * The time period in days in which removed events should get displayed.
	 *
	 * @var string
	 */
	protected $delta;

	/**
	 * Filter to indicate intern emails
	 *
	 * @var string
	 */
	protected $emailFilter;

	/**
	 * mobile device or not
	 *
	 * @var bool
	 */
	protected $isMobile = false;

	/**
	 * Contains the current language tag
	 *
	 * @var string
	 */
	protected $tag = 'de';

	/**
	 * Method to display the template
	 *
	 * @param   null  $tpl  template
	 *
	 * @return void
	 */
	public function display($tpl = null)
	{
		$compParams        = Helpers\Input::getParams();
		$this->dateFormat  = $compParams->get('dateFormat', 'd.m.Y');
		$this->emailFilter = $compParams->get('emailFilter', '');
		$this->grids       = Helpers\Grids::getResources();
		$this->isMobile    = Helpers\OrganizerHelper::isSmartphone();
		$this->params      = $this->getModel()->params;
		$this->tag         = Languages::getTag();

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
		$this->addScriptOptions();

		Adapters\Document::addScript(Uri::root() . 'components/com_organizer/js/schedule.js');
		Adapters\Document::addStyleSheet(Uri::root() . 'components/com_organizer/css/schedule_item.css');
		Adapters\Document::addStyleSheet(Uri::root() . 'media/jui/css/icomoon.css');

		Helpers\HTML::_('formbehavior.chosen', 'select');
	}

	/**
	 * Generates required params for Javascript and adds them to the document
	 *
	 * @return void
	 */
	private function addScriptOptions()
	{
		$user = Helpers\Users::getUser();
		$root = Uri::root();

		$variables = [
			'SEMESTER_MODE'   => 1,
			'BLOCK_MODE'      => 2,
			'INSTANCE_MODE'   => 3,
			'ajaxBase'        => $root . 'index.php?option=com_organizer&format=json&organizationIDs=',
			'dateFormat'      => $this->dateFormat,
			'exportBase'      => $root . 'index.php?option=com_organizer&view=schedule_export',
			'isMobile'        => $this->isMobile,
			'menuID'          => Helpers\Input::getItemid(),
			'subjectItemBase' => $root . 'index.php?option=com_organizer&view=subject_item&id=1',
			'username'        => $user->id ? $user->username : ''
		];

		if ($user->email)
		{
			$domain = substr($user->email, strpos($user->email, '@'));
			if (empty($this->emailFilter) or strpos($domain, $this->emailFilter) !== false)
			{
				// Joomla documented the wrong type for registerDate which is a string
				/** @noinspection PhpToStringImplementationInspection */
				$variables['auth']     = urlencode(password_hash($user->email . $user->registerDate, PASSWORD_BCRYPT));
				$variables['userID']   = $user->id;
				$variables['username'] = $user->username;
			}
		}

		if (empty($variables['userID']))
		{
			$variables['userID']   = 0;
			$variables['auth']     = '';
			$variables['username'] = '';
		}

		$variables['grids'] = [];
		foreach ($this->grids as $grid)
		{
			$gridID     = $grid['id'];
			$gridString = Helpers\Grids::getGrid($gridID);

			// Set a default until when/if the real default is iterated
			$this->params['defaultGrid'] = empty($this->params['defaultGrid']) ?
				$gridString : $this->params['defaultGrid'];
			$variables['grids'][$gridID] = ['id' => $gridID, 'grid' => $gridString];

			if ($grid['defaultGrid'])
			{
				$this->params['defaultGrid'] = $gridString;
			}
		}

		Adapters\Document::addScriptOptions('variables', array_merge($variables, $this->params));

		Languages::script('APRIL');
		Languages::script('AUGUST');
		Languages::script('DECEMBER');
		Languages::script('FEBRUARY');
		Languages::script('FRI');
		Languages::script('JANUARY');
		Languages::script('JULY');
		Languages::script('JUNE');
		Languages::script('MARCH');
		Languages::script('MAY');
		Languages::script('MON');
		Languages::script('NOVEMBER');
		Languages::script('OCTOBER');
		Languages::script('SAT');
		Languages::script('SEPTEMBER');
		Languages::script('ORGANIZER_SPEAKER');
		Languages::script('ORGANIZER_SPEAKERS');
		Languages::script('SUN');
		Languages::script('ORGANIZER_SUPERVISOR');
		Languages::script('ORGANIZER_SUPERVISORS');
		Languages::script('ORGANIZER_GENERATE_LINK');
		Languages::script('ORGANIZER_LUNCHTIME');
		Languages::script('ORGANIZER_MY_SCHEDULE');
		Languages::script('ORGANIZER_SELECT_CATEGORY');
		Languages::script('ORGANIZER_SELECT_GROUP');
		Languages::script('ORGANIZER_SELECT_ROOM');
		Languages::script('ORGANIZER_SELECT_ROOMTYPE');
		Languages::script('ORGANIZER_SELECT_PERSON');
		Languages::script('ORGANIZER_TEACHER');
		Languages::script('ORGANIZER_TEACHERS');
		Languages::script('ORGANIZER_TIME');
		Languages::script('ORGANIZER_TUTOR');
		Languages::script('ORGANIZER_TUTORS');
		Languages::script('THU');
		Languages::script('TUE');
		Languages::script('WED');
	}
}
