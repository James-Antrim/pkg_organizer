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
use Organizer\Adapters\Document;
use Organizer\Adapters\Toolbar;
use Organizer\Helpers;
use Organizer\Helpers\Languages;

/**
 * Class loads persistent information a filtered set of instances into the display context.
 */
class Export extends FormView
{
	protected $layout = 'export';

	/**
	 * The URL for direct access to the export.
	 *
	 * @var string
	 */
	public $url;

	/**
	 * @inheritdoc
	 */
	protected function addToolBar()
	{
		$this->setTitle('ORGANIZER_EXPORT_TITLE');
		$toolbar = Toolbar::getInstance();

		$fields = [
			'campusID'       => 0,
			'categoryID'     => 0,
			'groupID'        => 0,
			'my'             => 0,
			'organizationID' => 0,
			'personID'       => 0,
			'roomID'         => 0
		];
		$form   = ($task = Helpers\Input::getTask() and $task === 'export.reset') ? [] : Helpers\Input::getArray();

		foreach (array_keys($fields) as $field)
		{
			if (empty($form[$field]))
			{
				unset($fields[$field]);
				continue;
			}

			$fields[$field] = $form[$field];
		}

		// No selection has been made
		if (!$fields)
		{
			$this->url = '';
			$toolbar->appendButton(
				'Standard',
				'undo-2',
				Helpers\Languages::_('ORGANIZER_RESET'),
				'export.reset',
				false
			);

			return;
		}

		$url = Uri::base() . '?option=com_organizer&view=instances';

		$formats = ['ics', 'pdf.GridA3', 'pdf.GridA4', 'xls.Instances'];

		$format = (!empty($form['format']) and in_array($form['format'], $formats)) ? $form['format'] : 'pdf.GridA4';
		$format = explode('.', $format);
		$layout = empty($format[1]) ? '' : "&layout=$format[1]";
		$format = $format[0];
		$url    .= "&format=$format$layout";

		$authRequired = (!empty($fields['my']) or !empty($fields['personID']));

		if (!$username = Helpers\Users::getUserName() and $authRequired)
		{
			Helpers\OrganizerHelper::error(401);

			return;
		}

		// Resource links
		if (empty($fields['my']))
		{
			foreach ($fields as $field => $value)
			{
				$url .= "&$field=$value";
			}
		}
		// 'My' link
		else
		{
			$url .= "&my=1";
		}

		if ($authRequired)
		{
			$url .= "&username=$username&auth=" . Helpers\Users::getAuth();
		}

		if ($format !== 'ics')
		{
			$defaultDate = date('Y-m-d');
			$date        = empty($form['date']) ? $defaultDate : $form['date'];
			$date        = Helpers\Dates::standardizeDate($date);

			if ($specific = ($date !== $defaultDate))
			{
				$url .= "&date=$date";
			}

			$intervals = ['month', 'quarter', 'term', 'week'];
			$interval  = (empty($form['interval']) or !in_array($form['interval'], $intervals)) ?
				'week' : $form['interval'];
			$url       .= "&interval=$interval";

			$toolbar->appendButton('Link', "file-$format", Languages::_('ORGANIZER_DOWNLOAD'), $url, true);

			// The URL has a specific date => URL has no general application
			if ($specific)
			{
				$url = '';
			}
		}

		$this->url = $url;
		$toolbar->appendButton(
			'Standard',
			'undo-2',
			Helpers\Languages::_('ORGANIZER_RESET'),
			'export.reset',
			false
		);
	}

	/**
	 * @inheritDoc
	 */
	protected function modifyDocument()
	{
		parent::modifyDocument();

		$variables = [
			'ICS_URL' => Uri::base() . '?option=com_organizer&view=instances&format=ics'
		];

		$user = Helpers\Users::getUser();

		if ($user->id)
		{
			// Joomla documented the wrong type for registerDate which is a string
			/** @noinspection PhpToStringImplementationInspection */
			$variables['auth']     = urlencode(password_hash($user->email . $user->registerDate, PASSWORD_BCRYPT));
			$variables['username'] = $user->username;
		}

		Languages::script('ORGANIZER_GENERATE_LINK');
		Document::addScriptOptions('variables', $variables);
		Document::addScript(Uri::root() . 'components/com_organizer/js/ics.js');
	}
}