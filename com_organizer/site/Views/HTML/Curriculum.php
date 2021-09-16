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

/**
 * Class loads curriculum information into the display context.
 */
class Curriculum extends ItemView
{
	private $baggage = [
		'/Audio([a-zß-ÿ])/'              => 'Audio&shy;$1',
		'/Berufs([a-zß-ÿ])/'             => 'Berufs&shy;$1',
		'/Betriebs([a-zß-ÿ])/'           => 'Betriebs&shy;$1',
		'/Energie([a-zß-ÿ])/'            => 'Energie&shy;$1',
		'/Event([a-zß-ÿ])/'              => 'Event&shy;$1',
		'/Inter([a-zß-ÿ])/'              => 'Inter&shy;$1',
		'/Multi([a-zß-ÿ])/'              => 'Multi&shy;$1',
		'/Kommunikations([a-zß-ÿ])/'     => 'Kommuni&shy;kations&shy;$1',
		'/Kommunikation/'                => 'Kommuni&shy;kation',
		'/Sicherheits([a-zß-ÿ])/'        => 'Sicherheits&shy;$1',
		'/Text([a-zß-ÿ])/'               => 'Text&shy;$1',
		'/Unternehmens([a-zß-ÿ])/'       => 'Unter&shy;nehmens&shy;$1',
		'/Veranstaltungs([a-zß-ÿ])/'     => 'Veran&shy;staltungs&shy;$1',
		'/Wahl([a-zß-ÿ])/'               => 'Wahl&shy;$1',
		'/([a-zß-ÿ])führung($| )/'       => '$1&shy;führung$2',
		'/([a-zß-ÿ])gestaltung($| )/'    => '$1&shy;gestaltung$2',
		'/([a-zß-ÿ])isierung($| )/'      => '$1&shy;isierung$2',
		'/([a-zß-ÿ])kunde($| )/'         => '$1&shy;kunde$2',
		'/([a-zß-ÿ])lehre($| )/'         => '$1&shy;lehre$2',
		'/([a-zß-ÿ])leitung($| )/'       => '$1&shy;leitung$2',
		'/([a-zß-ÿ])management($| )/'    => '$1&shy;management$2',
		'/([a-zß-ÿ])module($| )/'        => '$1&shy;module$2',
		'/([a-zß-ÿ])modul($| )/'         => '$1&shy;modul$2',
		'/([a-zß-ÿ])planung($| )/'       => '$1&shy;planung$2',
		'/([a-zß-ÿ])produktion($| )/'    => '$1&shy;produktion$2',
		'/([a-zß-ÿ])schaftliches($| |)/' => '$1&shy;schaftliches$2',
		'/([a-zß-ÿ])technik($| )/'       => '$1&shy;technik$2',
		'/([a-zß-ÿ])technologie($| )/'   => '$1&shy;technologie$2',
		'/([a-zß-ÿ])technology($| )/'    => '$1&shy;technology$2',
		'/([a-zß-ÿ])wesen($| )/'         => '$1&shy;wesen$2',
		'/([a-zß-ÿ])wesens($| )/'        => '$1&shy;wesens$2',
	];

	protected $layout = 'curriculum';

	public $fields = [];

	/**
	 * Filters out invalid and true empty values. (0 is allowed.)
	 *
	 * @return void modifies the item
	 */
	protected function filterAttributes()
	{
		// Nothing filtered
	}

	/**
	 * @inheritDoc
	 */
	protected function modifyDocument()
	{
		parent::modifyDocument();

		Adapters\Document::addStyleSheet(Uri::root() . 'components/com_organizer/css/curriculum.css');
	}

	/**
	 * Creates the HTML for a panel item.
	 *
	 * @param   array  $item  the date for the panel item to create
	 *
	 * @return string the HTML for the panel item
	 */
	private function getPanelItem(array $item): string
	{
		$itemTemplate = '<div class="item ITEMCLASS">ITEMCONTENT</div>';
		$itemClass    = 'item-blank';
		$itemContent  = '';

		if (!empty($item) and !empty($item['name']))
		{
			$bgColor = '#ffffff';
			if (!empty($item['bgColor']) and !empty($item['field']))
			{
				$this->fields[$item['bgColor']] = $item['field'];
				$bgColor                        = $item['bgColor'];
			}

			$itemContent .= '<div class="item-color" style="background-color: ' . $bgColor . '"></div>';
			$itemContent .= '<div class="item-body">';

			$additionalLinks = '';
			$linkAttributes  = ['target' => '_blank'];

			if ($item['subjectID'])
			{
				$crp = empty($item['creditPoints']) ? '' : "{$item['creditPoints']} CrP";
				$url = "?option=com_organizer&view=subject_item&id={$item['subjectID']}";

				$docAttibutes = $linkAttributes + ['title' => Helpers\Languages::_('ORGANIZER_SUBJECT_ITEM')];
				//$gridAttributes = $linkAttributes + ['title' => Helpers\Languages::_('ORGANIZER_SCHEDULE')];

				$documentLink = Helpers\HTML::link($url, '<span class="icon-file-2"></span>', $docAttibutes);

				/*$scheduleUrl = "?option=com_organizer&view=schedule_item&subjectIDs={$item['subjectID']}";

				$scheduleLink = Helpers\HTML::link(
					$scheduleUrl,
					'<span class="icon-info-calender"></span>',
					$gridAttributes
				);*/

				$additionalLinks .= $documentLink/* . $scheduleLink*/
				;

				$itemClass = 'item-subject';
			}
			else
			{
				$crp = Helpers\Pools::getCrPText($item);
				$url = '?option=com_organizer&view=subjects';
				$url .= "&programID={$this->item['programID']}&poolID={$item['poolID']}";

				$itemClass = 'item-pool';
			}

			$this->unpack($item['name']);

			$title       = Helpers\HTML::link($url, $item['name'], $linkAttributes);
			$itemContent .= '<div class="item-title">' . $title . '</div>';
			$itemContent .= $crp ? '<div class="item-crp">' . $crp . '</div>' : '';
			$itemContent .= $additionalLinks ? '<div class="item-tools">' . $additionalLinks . '</div>' : '';

			$itemContent .= '</div>';
		}

		$item = str_replace('ITEMCLASS', $itemClass, $itemTemplate);

		return str_replace('ITEMCONTENT', $itemContent, $item);
	}

	/**
	 * Renders the panel resolving the colors to the corresponding competences.
	 * @return void
	 */
	public function renderLegend()
	{
		?>
        <div class="legend">
            <div class="panel-head">
                <div class="panel-title"><?php echo Helpers\Languages::_('ORGANIZER_LEGEND'); ?></div>
            </div>
			<?php foreach ($this->fields as $hex => $field) : ?>
                <div class="legend-item">
                    <div class="item-color" style="background-color: <?php echo $hex; ?>;"></div>
                    <div class="item-title"><?php echo $field; ?></div>
                </div>
			<?php endforeach; ?>
        </div>
		<?php
	}

	/**
	 * Outputs the pool information in the form of a panel
	 *
	 * @param   array  $pool  the pool to be displayed
	 *
	 * @return void displays HTML
	 */
	public function renderPanel(array $pool)
	{
		$crpText = Helpers\Pools::getCrPText($pool);
		?>
        <div class="panel">
            <div class="panel-head">
                <div class="panel-title"><?php echo $pool['name']; ?></div>
                <div class="panel-crp"><?php echo $crpText; ?></div>
            </div>
            <div class="panel-body">
				<?php $this->renderPanelBody($pool['curriculum']); ?>
            </div>
        </div>
		<?php
	}

	/**
	 * Displays the body of the panel while iterating through child items
	 *
	 * @param   array  $curriculum  the subordinate elements to the pool modeled by the panel
	 *
	 * @return  void displays the panel body
	 */
	private function renderPanelBody(array $curriculum)
	{
		$maxOrdering = 0;
		$items       = [];
		foreach ($curriculum as $subOrdinate)
		{
			$items[$subOrdinate['ordering']] = $this->getPanelItem($subOrdinate);

			$maxOrdering = $maxOrdering > $subOrdinate['ordering'] ? $maxOrdering : $subOrdinate['ordering'];
		}

		$trailingBlanks = 5 - $maxOrdering % 5;
		if ($trailingBlanks < 5)
		{
			$maxOrdering += $trailingBlanks;
		}

		for ($current = 1; $current <= $maxOrdering; $current++)
		{
			if ($current % 5 === 1)
			{
				echo '<div class="panel-row">';
			}
			echo empty($items[$current]) ? $this->getPanelItem([]) : $items[$current];
			if ($current % 5 === 0)
			{
				echo '</div>';
			}
		}
	}

	/**
	 * @param   string  $text
	 *
	 * @return void
	 */
	private function unpack(string &$text)
	{
		foreach ($this->baggage as $pattern => $replace)
		{
			$text = preg_replace($pattern, $replace, $text);
		}
	}
}
