<?php
/**
 * @package     Organizer
 * @extension   com_organizer
 * @author      James Antrim, <james.antrim@nm.thm.de>
 * @copyright   2020 TH Mittelhessen
 * @license     GNU GPL v.2
 * @link        www.thm.de
 */

namespace THM\Organizer\Views\HTML;

use Joomla\CMS\Uri\Uri;
use THM\Organizer\Adapters\Document;
use THM\Organizer\Helpers\Languages;

/**
 * Class loads filtered events into the display context.
 */
class Screen extends BaseView
{
    protected $layout = 'upcoming_instances';

    public $model;

    /**
     * Loads persistent data into the view context
     *
     * @param string $tpl the name of the template to load
     *
     * @return void
     */
    public function display($tpl = null)
    {
        //https://www.thm.de/dev/organizer/?option=com_organizer&view=screen&tmpl=component&room=A20.2.11&layout=upcoming_instances
        //https://www.thm.de/dev/organizer/?option=com_organizer&view=screen&tmpl=component&room=A20.2.11&layout=current_instances
        //https://www.thm.de/dev/organizer/?option=com_organizer&view=screen&tmpl=component&room=A20.2.11&layout=file
        $this->model = $this->getModel();

        $this->setLayout($this->model->layout);

        Document::addStyleSheet(Uri::root() . 'components/com_organizer/css/screen.css');
        Document::addStyleSheet(Uri::root() . 'media/jui/css/icomoon.css');

        parent::display($tpl);
    }


    /**
     * Resolves any links/link parameters to links with icons or texts.
     *
     * @param string $comment the comment to process
     *
     * @return string
     */
    public function processComment(string $comment): string
    {
        $moodle1  = '/(((https?):\/\/)moodle.thm.de\/course\/view.php\?id=(\d+))/';
        $moodle2  = '/moodle=(\d+)/';
        $moodle3  = '/(((https?):\/\/)moodle\.thm\.de\/course\/index\.php\\?categoryid=(\\d+))/';
        $netAcad  = '/(((https?):\/\/)\d+.netacad.com\/courses\/\d+)/';
        $panopto1 = '/(((https?):\/\/)panopto.thm.de\/Panopto\/Pages\/Viewer.aspx\?id=[\d\w\-]+)/';
        $panopto2 = '/panopto=([\d\w\-]+)/';
        $pilos    = '/(((https?):\/\/)(\d+|roxy).pilos-thm.de\/(b\/)?[\d\w]{3}-[\d\w]{3}-[\d\w]{3})/';

        if ($this->mobile) {
            $link = '<a href="URL" target="_blank"><span class="icon-moodle"></span></a>';

            // Moodle Course
            $url      = 'https://moodle.thm.de/course/view.php?id=PID';
            $template = str_replace('PID', '$4', str_replace('URL', $url, $link));
            $comment  = preg_replace($moodle1, $template, $comment);
            $template = str_replace('PID', '$1', str_replace('URL', $url, $link));
            $comment  = preg_replace($moodle2, $template, $comment);

            // Moodle Category
            $url      = 'https://moodle.thm.de/course/index.php?categoryid=PID';
            $template = str_replace('PID', '$4', str_replace('URL', $url, $link));
            $comment  = preg_replace($moodle3, $template, $comment);

            $template = '<a href="$1" target="_blank"><span class="icon-cisco"></span></a>';
            $comment  = preg_replace($netAcad, $template, $comment);

            $url      = 'https://panopto.thm.de/Panopto/Pages/Viewer.aspx?id=PID';
            $link     = "<a href=\"$url\" target=\"_blank\"><span class=\"icon-panopto\"></span></a>";
            $template = str_replace('PID', '$4', $link);
            $comment  = preg_replace($panopto1, $template, $comment);
            $template = str_replace('PID', '$1', $link);
            $comment  = preg_replace($panopto2, $template, $comment);

            $template = '<a href="$1" target="_blank"><span class="icon-pilos"></span></a>';
        } else {
            // Moodle Course
            $text     = Languages::_('ORGANIZER_MOODLE_COURSE') . ': CID';
            $template = str_replace('CID', '$4', $text);
            $comment  = preg_replace($moodle1, $template, $comment);
            $template = str_replace('CID', '$1', $text);
            $comment  = preg_replace($moodle2, $template, $comment);

            // Moodle Category
            $text     = Languages::_('ORGANIZER_MOODLE_CATEGORY') . ': CID';
            $template = str_replace('CID', '$4', $text);
            $comment  = preg_replace($moodle3, $template, $comment);

            $template = Languages::_('ORGANIZER_NETACAD_COURSE') . ': $1';
            $comment  = preg_replace($netAcad, $template, $comment);

            $text     = Languages::_('ORGANIZER_PANOPTO_PAGE') . ': PID';
            $template = str_replace('PID', '$4', $text);
            $comment  = preg_replace($panopto1, $template, $comment);
            $template = str_replace('PID', '$1', $text);
            $comment  = preg_replace($panopto2, $template, $comment);

            $template = Languages::_('ORGANIZER_PILOS_ID') . ': $1';
        }

        return preg_replace($pilos, $template, $comment);
    }
}
