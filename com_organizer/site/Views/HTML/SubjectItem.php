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

use THM\Organizer\Adapters\Text;

/**
 * Class loads the subject into the display context.
 */
class SubjectItem extends ItemView
{
    use Documented;

    /** @inheritdoc */
    public function display($tpl = null): void
    {
        $this->addDisclaimer();
        parent::display($tpl);
    }

    /**
     * Renders a number of stars appropriate to the value
     *
     * @param   int|null  $value  the value of the star attribute
     *
     * @return void outputs HTML
     */
    public function renderStarValue(?int $value): void
    {
        if (is_null($value)) {
            return;
        }

        $option = 'ORGANIZER_';
        switch ($value) {
            case 3:
                $stars = '<span class="icon-featured"></span>';
                $stars .= '<span class="icon-featured"></span>';
                $stars .= '<span class="icon-featured"></span>';
                $aria  = Text::_($option . 'THREE_STARS');
                break;
            case 2:
                $stars = '<span class="icon-featured"></span>';
                $stars .= '<span class="icon-featured"></span>';
                $stars .= '<span class="icon-unfeatured"></span>';
                $aria  = Text::_($option . 'TWO_STARS');
                break;
            case 1:
                $stars = '<span class="icon-featured"></span>';
                $stars .= '<span class="icon-unfeatured"></span>';
                $stars .= '<span class="icon-unfeatured"></span>';
                $aria  = Text::_($option . 'ONE_STAR');
                break;
            case 0:
            default:
                $stars = '<span class="icon-unfeatured"></span>';
                $stars .= '<span class="icon-unfeatured"></span>';
                $stars .= '<span class="icon-unfeatured"></span>';
                $aria  = Text::_($option . 'NO_STARS');
                break;
        }

        echo '<span aria-label="' . $aria . '">' . $stars . '</span>';
    }
}
