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
use THM\Organizer\Adapters\{Application, Input, Text, Toolbar, User};
use THM\Organizer\Helpers;

/**
 * Class loads persistent information a filtered set of instances into the display context.
 */
class Export extends FormView
{
    use Abstracted;
    use Tasked;

    protected string $layout = 'export';

    /**
     * The URL for direct access to the export.
     * @var string
     */
    public string $url;

    /** @inheritDoc */
    protected function addToolBar(array $buttons = [], string $constant = ''): void
    {
        $this->toDo[] = 'The category options don\'t reflect the selected organizations.';
        $this->toDo[] = 'Migrate toolbar.';
        $this->toDo[] = 'Add the subscription button as a distinct toolbar.';
        $this->toDo[] = 'Finish model migration: viewable persons';

        $this->title('EXPORT_TITLE');
        $toolbar = Toolbar::getInstance();

        $fields = [
            'categoryID'     => 0,
            'groupID'        => 0,
            'my'             => 0,
            'methodID'       => 0,
            'organizationID' => 0,
            'personID'       => 0,
            'roleID'         => 0,
            'roomID'         => 0
        ];

        $form = ($task = Input::getTask() and $task === 'export.reset') ? [] : Input::getFormItems();

        foreach (array_keys($fields) as $field) {
            if (empty($form[$field])) {
                unset($fields[$field]);
                continue;
            }

            $fields[$field] = $form[$field];
        }

        // No selection has been made
        if (!$fields) {
            $this->url = '';
            $toolbar->standardButton('reset', Text::_('RESET'), 'Export.reset')->icon('fa fa-undo');

            return;
        }

        $url = Uri::base() . '?option=com_organizer&view=instances';

        $instances = ['organization', 'person'];
        $instances = (!empty($form['instances']) and in_array($form['instances'], $instances)) ?
            $form['instances'] : 'organization';
        $url       .= $instances === 'organization' ? '' : "&instances=$instances";

        $formats = ['ics', 'pdf.GridA3', 'pdf.GridA4', 'xls.Instances'];
        $format  = (!empty($form['format']) and in_array($form['format'], $formats)) ? $form['format'] : 'pdf.GridA4';
        $format  = explode('.', $format);
        $layout  = empty($format[1]) ? '' : "&layout=$format[1]";
        $format  = $format[0];
        $layout  .= ($format === 'pdf' and !empty($form['separate'])) ? '&separate=1' : '';
        $url     .= "&format=$format$layout";

        $authRequired = (!empty($fields['my']) or !empty($fields['personID']));

        if (!$username = User::userName() and $authRequired) {
            Application::error(401);
        }

        // Resource links
        if (empty($fields['my'])) {
            foreach ($fields as $field => $value) {
                $url .= "&$field=$value";
            }
        } // 'My' link
        else {
            $url .= "&my=1";
        }

        if ($authRequired) {
            $url .= "&username=$username&auth=" . User::token();
        }

        if ($format !== 'ics') {
            $defaultDate = date('Y-m-d');
            $date        = empty($form['date']) ? $defaultDate : $form['date'];
            $date        = Helpers\Dates::standardize($date);

            if ($specific = ($date !== $defaultDate)) {
                $url .= "&date=$date";
            }

            $intervals = ['month', 'quarter', 'term', 'week'];
            $interval  = (empty($form['interval']) or !in_array($form['interval'], $intervals)) ?
                'week' : $form['interval'];
            $url       .= "&interval=$interval";

            $toolbar->appendButton('Link', "file-$format", Text::_('ORGANIZER_DOWNLOAD'), $url, true);

            // The URL has a specific date => URL has no general application
            if ($specific) {
                $url = '';
            }
        }

        $this->url = $url;
        $toolbar->appendButton(
            'Standard',
            'undo-2',
            Text::_('ORGANIZER_RESET'),
            'export.reset',
            false
        );
    }
}