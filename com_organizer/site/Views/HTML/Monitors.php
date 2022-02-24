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

use Organizer\Helpers;

/**
 * Class loads persistent information a filtered set of monitors into the display context.
 */
class Monitors extends ListView
{
	private const UPCOMING = 0, CURRENT = 1, MIXED = 2, CONTENT = 3;

	public $displayBehaviour = [];

	protected $rowStructure = [
		'checkbox'    => '',
		'name'        => 'link',
		'ip'          => 'link',
		'useDefaults' => 'value',
		'display'     => 'link',
		'content'     => 'link'
	];

	/**
	 * @inheritdoc
	 */
	public function __construct($config = [])
	{
		$this->displayBehaviour[self::UPCOMING] = Helpers\Languages::_('ORGANIZER_UPCOMING_INSTANCES');
		$this->displayBehaviour[self::CURRENT]  = Helpers\Languages::_('ORGANIZER_CURRENT_INSTANCES');
		$this->displayBehaviour[self::MIXED]    = Helpers\Languages::_('ORGANIZER_MIXED_PLAN');
		$this->displayBehaviour[self::CONTENT]  = Helpers\Languages::_('ORGANIZER_CONTENT_DISPLAY');

		parent::__construct($config);
	}

	/**
	 * @inheritdoc
	 */
	protected function authorize()
	{
		if (!Helpers\Can::manage('facilities'))
		{
			Helpers\OrganizerHelper::error(403);
		}
	}

	/**
	 * @inheritdoc
	 */
	public function setHeaders()
	{
		$ordering  = $this->state->get('list.ordering');
		$direction = $this->state->get('list.direction');
		$headers   = [
			'checkbox'    => Helpers\HTML::_('grid.checkall'),
			'name'        => Helpers\HTML::sort('ROOM', 'r.name', $direction, $ordering),
			'ip'          => Helpers\HTML::sort('IP', 'm.ip', $direction, $ordering),
			'useDefaults' => Helpers\HTML::sort('DEFAULT_SETTINGS', 'm.useDefaults', $direction, $ordering),
			'display'     => Helpers\Languages::_('ORGANIZER_DISPLAY_BEHAVIOUR'),
			'content'     => Helpers\HTML::sort('DISPLAY_CONTENT', 'm.content', $direction, $ordering)
		];

		$this->headers = $headers;
	}

	/**
	 * @inheritdoc
	 */
	protected function structureItems()
	{
		$link            = 'index.php?option=com_organizer&view=monitor_edit&id=';
		$index           = 0;
		$structuredItems = [];

		$params       = Helpers\Input::getParams();
		$displayParam = $params->get('display');
		$contentParam = $params->get('content');

		foreach ($this->items as $item)
		{
			if ($item->useDefaults)
			{
				$item->display = $this->displayBehaviour[$displayParam];
				$item->content = $contentParam;
			}
			else
			{
				$item->display = $this->displayBehaviour[$item->display];
			}

			$item->useDefaults = $this->getToggle(
				'monitor',
				$item->id,
				$item->useDefaults,
				'ORGANIZER_TOGGLE_COMPONENT_SETTINGS'
			);

			$structuredItems[$index] = $this->structureItem($index, $item, $link . $item->id);
			$index++;
		}

		$this->items = $structuredItems;
	}
}
