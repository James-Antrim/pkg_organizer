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

use Joomla\CMS\Router\Route;
use Joomla\CMS\Uri\Uri;
use THM\Organizer\Adapters\{Document, HTML};

/**
 * Class loads the resource form into display context. Specific resource determined by extending class.
 */
abstract class ItemView extends BaseView
{
    protected string $layout = 'item';

    public $form = null;

    public $item = null;

    /**
     * Method to generate buttons for user interaction
     * @return void
     */
    protected function addToolBar()
    {
        // On demand abstract function.
    }

    /**
     * @inheritdoc
     */
    public function display($tpl = null): void
    {
        $this->item = $this->get('Item');

        $this->addToolBar();
        $this->setSubtitle();
        $this->setSupplement();
        $this->modifyDocument();

        $defaultConstant = 'ORGANIZER_' . strtoupper(str_replace('Item', '', $this->getName()));
        $itemName        = is_array($this->item['name']) ? $this->item['name']['value'] : $this->item['name'];
        $this->setTitle($defaultConstant, $itemName);
        unset($this->item['name']);

        // This has to be after the title has been set so that it isn't prematurely removed.
        $this->filterAttributes();
        parent::display($tpl);
    }

    /**
     * Filters out invalid and true empty values. (0 is allowed.)
     * @return void modifies the item
     */
    protected function filterAttributes(): void
    {
        foreach ($this->item as $key => $attribute) {
            // Invalid for HTML Output
            if (!is_array($attribute)
                or !array_key_exists('value', $attribute)
                or !array_key_exists('label', $attribute)
                or $attribute['value'] === null
                or $attribute['value'] === ''
            ) {
                unset($this->item[$key]);
            }
        }
    }

    /**
     * Modifies document variables and adds links to external files
     * @return void
     */
    protected function modifyDocument(): void
    {
        parent::modifyDocument();

        Document::addStyleSheet(Uri::root() . 'components/com_organizer/css/item.css');
    }

    /**
     * Recursively outputs an array of items as a list.
     *
     * @param   array  $items  the items to be displayed.
     *
     * @return void outputs the items as a html list
     */
    public function renderListValue(array $items, string $url, array $urlAttribs): void
    {
        echo '<ul>';
        foreach ($items as $index => $item) {
            echo '<li>';
            if (is_array($item)) {
                echo $index;
                $this->renderListValue($item, $url, $urlAttribs);
            }
            else {
                echo empty($url) ? $item : HTML::link(Route::_($url . $index), $item, $urlAttribs);
            }
            echo '</li>';
        }
        echo '</ul>';
    }
}
