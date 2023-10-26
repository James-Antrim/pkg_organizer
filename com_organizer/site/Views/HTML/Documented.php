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


use Joomla\CMS\Uri\Uri;
use THM\Organizer\Adapters\Application;
use THM\Organizer\Adapters\Document;
use THM\Organizer\Adapters\Text;

trait Documented
{
    public string $disclaimer = '';

    public function addDisclaimer(): void
    {
        if (Application::backend()) {
            return;
        }

        Document::addStyleSheet(Uri::root() . 'components/com_organizer/css/disclaimer.css');

        $attributes = ['target' => '_blank'];

        $lsfLink = Helpers\HTML::link(
            'https://studien-sb-service.th-mittelhessen.de/docu/online.html',
            Text::_('ORGANIZER_DISCLAIMER_LSF_TITLE'),
            $attributes
        );
        $ambLink = Helpers\HTML::link(
            'https://www.thm.de/amb/pruefungsordnungen',
            Text::_('ORGANIZER_DISCLAIMER_AMB_TITLE'),
            $attributes
        );
        $poLink  = Helpers\HTML::link(
            'https://www.thm.de/site/studium/sie-studieren/pruefungsordnung.html',
            Text::_('ORGANIZER_DISCLAIMER_PO_TITLE'),
            $attributes
        );

        $disclaimer = '<div class="disclaimer">';
        $disclaimer .= '<h4>' . Text::_('ORGANIZER_DISCLAIMER_LEGAL') . '</h4>';
        $disclaimer .= '<ul>';
        $disclaimer .= '<li>' . Text::sprintf('ORGANIZER_DISCLAIMER_LSF_TEXT', $lsfLink) . '</li>';
        $disclaimer .= '<li>' . Text::sprintf('ORGANIZER_DISCLAIMER_AMB_TEXT', $ambLink) . '</li>';
        $disclaimer .= '<li>' . Text::sprintf('ORGANIZER_DISCLAIMER_PO_TEXT', $poLink) . '</li>';
        $disclaimer .= '</ul>';
        $disclaimer .= '</div>';

        $this->disclaimer = $disclaimer;
    }
}