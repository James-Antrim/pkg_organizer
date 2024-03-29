<?php
/**
 * @package     Organizer
 * @extension   com_organizer
 * @author      James Antrim, <james.antrim@nm.thm.de>
 * @copyright   2020 TH Mittelhessen
 * @license     GNU GPL v.3
 * @link        www.thm.de
 */

namespace THM\Organizer\Views\HTML;

use Joomla\CMS\Uri\Uri;
use THM\Organizer\Adapters\{HTML, Text};
use THM\Organizer\Helpers\Pools;

/**
 * Loads curriculum information into the display context.
 */
class Curriculum extends ItemView
{
    use Documented;

    protected string $layout = 'curriculum';

    public array $fields = [];

    /** @inheritDoc */
    public function display($tpl = null): void
    {
        $this->addDisclaimer();
        parent::display($tpl);
    }

    /**
     * Filters out invalid and true empty values. (0 is allowed.)
     * @return void modifies the item
     */
    protected function filterAttributes(): void
    {
        // Nothing filtered
    }

    /**
     * @inheritDoc
     */
    protected function modifyDocument(): void
    {
        parent::modifyDocument();

        //Document::style('curriculum');
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
        $base         = Uri::base() . '?option=com_organizer&view=';
        $itemTemplate = '<div class="item ITEMCLASS">ITEMCONTENT</div>';
        $itemClass    = 'item-blank';
        $itemContent  = '';

        if (!empty($item) and !empty($item['name'])) {
            $bgColor = '#ffffff';
            if (!empty($item['bgColor']) and !empty($item['field'])) {
                $this->fields[$item['bgColor']] = $item['field'];
                $bgColor                        = $item['bgColor'];
            }

            $itemContent .= '<div class="item-color" style="background-color: ' . $bgColor . '"></div>';
            $itemContent .= '<div class="item-body">';

            $links      = '';
            $attributes = ['target' => '_blank'];

            if ($item['subjectID']) {
                $contextID = $item['id'] . '-' . $item['subjectID'];
                $crp       = empty($item['creditPoints']) ? '' : "{$item['creditPoints']} CrP";
                $url       = $base . "SubjectItem&id={$item['subjectID']}";

                $links .= HTML::tip(HTML::icon('fa fa-book'), "subject-link-$contextID", 'SUBJECT_ITEM', [], $url, true);

                if (!empty($item['eventID'])) {
                    $iUrl = $base . "Instances&eventID={$item['eventID']}&layout=";

                    $icon  = HTML::icon('fa fa-calendar');
                    $links .= HTML::tip($icon, "schedule-link-$contextID", 'SCHEDULE', [], $iUrl . 'grid', true);

                    $icon  = HTML::icon('fa fa-list');
                    $links .= HTML::tip($icon, "instances-link-$contextID", 'INSTANCES', [], $iUrl . 'list', true);
                }

                $itemClass = 'item-subject';
            }
            else {
                $crp = Pools::crpText($item);
                $url = $base . 'Subjects';
                $url .= "&programID={$this->item['programID']}&poolID={$item['poolID']}";

                $itemClass = 'item-pool';
            }

            Text::unpack($item['name']);

            $title       = HTML::link($url, $item['name'], $attributes);
            $itemContent .= '<div class="item-title">' . $title . '</div>';
            $itemContent .= $crp ? '<div class="item-crp">' . $crp . '</div>' : '';
            $itemContent .= $links ? '<div class="item-tools">' . $links . '</div>' : '';

            $itemContent .= '</div>';
        }

        $item = str_replace('ITEMCLASS', $itemClass, $itemTemplate);

        return str_replace('ITEMCONTENT', $itemContent, $item);
    }

    /**
     * Renders the panel resolving the colors to the corresponding competences.
     * @return void
     */
    public function renderLegend(): void
    {
        ?>
        <div class="legend">
            <div class="panel-head">
                <div class="panel-title"><?php echo Text::_('ORGANIZER_LEGEND'); ?></div>
            </div>
            <?php foreach ($this->fields as $hex => $field) : ?>
                <?php Text::unpack($field); ?>
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
    public function renderPanel(array $pool): void
    {
        $crpText = Pools::crpText($pool);
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
    private function renderPanelBody(array $curriculum): void
    {
        $maxOrdering = 0;
        $items       = [];
        foreach ($curriculum as $subOrdinate) {
            $items[$subOrdinate['ordering']] = $this->getPanelItem($subOrdinate);

            $maxOrdering = max($maxOrdering, $subOrdinate['ordering']);
        }

        $trailingBlanks = 5 - $maxOrdering % 5;
        if ($trailingBlanks < 5) {
            $maxOrdering += $trailingBlanks;
        }

        for ($current = 1; $current <= $maxOrdering; $current++) {
            if ($current % 5 === 1) {
                echo '<div class="panel-row">';
            }
            echo empty($items[$current]) ? $this->getPanelItem([]) : $items[$current];
            if ($current % 5 === 0) {
                echo '</div>';
            }
        }
    }
}
