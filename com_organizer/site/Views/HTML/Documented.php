<?php
/**
 * @package     Organizer
 * @extension   pkg_organizer
 * @author      James Antrim, <james.antrim@nm.thm.de>
 * @copyright   2023 TH Mittelhessen
 * @license     GNU GPL v.3
 * @link        www.thm.de
 */


namespace THM\Organizer\Views\HTML;

use THM\Organizer\Adapters\{Application, Document, HTML, Text};

/**
 * Adds a disclaimer as requested by the legal team.
 */
trait Documented
{
    public string $disclaimer = '';

    public function addDisclaimer(): void
    {
        if (Application::backend()) {
            return;
        }

        Document::style('disclaimer');

        $attributes = ['target' => '_blank'];

        $eCampusLink = HTML::link(
            'https://ecampus.thm.de/service/pages/cs/sys/portal/subMenu.faces?navigationPosition=studiesOffered',
            Text::_('DISCLAIMER_HIS_TITLE'),
            $attributes
        );
        $ambLink = HTML::link(
            'https://www.thm.de/amb/pruefungsordnungen',
            Text::_('DISCLAIMER_AMB_TITLE'),
            $attributes
        );
        $poLink  = HTML::link(
            'https://www.thm.de/amb/pruefungsordnungen.html',
            Text::_('DISCLAIMER_PO_TITLE'),
            $attributes
        );

        $disclaimer = '<div class="disclaimer">';
        $disclaimer .= '<h4>' . Text::_('DISCLAIMER_LEGAL') . '</h4>';
        $disclaimer .= '<ul>';
        $disclaimer .= '<li>' . Text::sprintf('DISCLAIMER_HIS_TEXT', $eCampusLink) . '</li>';
        $disclaimer .= '<li>' . Text::sprintf('DISCLAIMER_AMB_TEXT', $ambLink) . '</li>';
        $disclaimer .= '<li>' . Text::sprintf('DISCLAIMER_PO_TEXT', $poLink) . '</li>';
        $disclaimer .= '</ul>';
        $disclaimer .= '</div>';

        $this->disclaimer = $disclaimer;
    }
}