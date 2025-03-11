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

use Joomla\CMS\{Form\Form, Uri\Uri};
use THM\Organizer\Adapters\{Application, Input, Text, Toolbar, User};
use THM\Organizer\Models\{Conditions, Export as Model};

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
    public string $url = '';

    /** @inheritDoc */
    protected function addToolBar(array $buttons = [], string $constant = ''): void
    {
        $this->title('EXPORT_TITLE');

        // No selections were made or the form was reset.
        if (!Input::getFormItems() or Input::getTask() === 'reset') {
            return;
        }

        /** @var Form $form */
        $form      = $this->getForm();
        $my        = !empty($data['my']);
        $resources = ['categoryIDs', 'groupIDs', 'organizationIDs', 'personIDs', 'roomIDs'];
        $selection = false;

        foreach ($resources as $resource) {
            if (!empty($form->getValue($resource, null, []))) {
                $selection = true;
                break;
            }
        }

        $toolbar = Toolbar::getInstance();

        if ($my or $selection) {
            $url = Uri::base() . '?option=com_organizer&view=instances';

            if ($instances = $form->getValue('instances', null, '') and $instances === Conditions::PERSON) {
                $url .= '&instances=' . Conditions::PERSON;
            }

            $format = $form->getValue('exportFormat');
            $format = in_array($format, Model::EXPORT_FORMATS) ? $format : Model::EXPORT_FORMATS['default'];
            $pieces = explode('.', $format);

            if (count($pieces) === 3) {
                $formatURL = "&format=pdf&layout=$pieces[1]&size=$pieces[2]";
                $formatURL .= $form->getValue('separate', null, false) ? '&separate=1' : '';
            }
            else {
                $formatURL = "&format=xls&layout=$pieces[1]";
            }

            $credentials = false;
            $userName    = User::userName();

            if ($my) {
                if (!$userName) {
                    Application::error(401);
                }

                $credentials = true;

                $url .= '&my=1';
            }
            else {

                // Hierarchical structure and sanity checks were performed in the model
                if ($groupIDs = $form->getValue('groupIDs', null, [])) {
                    $url .= '&groupIDs=' . implode(',', $groupIDs);
                }
                elseif ($categoryIDs = $form->getValue('categoryIDs', null, [])) {
                    $url .= '&categoryIDs=' . implode(',', $categoryIDs);
                }
                elseif ($organizationIDs = $form->getValue('organizationIDs', null, [])) {
                    $url .= '&organizationIDs=' . implode(',', $organizationIDs);
                }

                if ($methodIDs = $form->getValue('methodIDs', null, [])) {
                    $url .= '&methodIDs=' . implode(',', $methodIDs);
                }

                if ($personIDs = $form->getValue('personIDs', null, [])) {
                    $url         .= '&personIDs=' . implode(',', $personIDs);
                    $credentials = true;
                }

                if ($roomIDs = $form->getValue('roomIDs', null, [])) {
                    $url .= '&roomIDs=' . implode(',', $roomIDs);
                }
            }

            // Finish the subscription URL
            $this->url = $url . '&format=ics';

            if ($credentials) {
                $this->url .= "&username=$userName&auth=" . User::token();
            }

            // Finish the button URL
            $url .= $formatURL;
            $url .= '&date=' . $form->getValue('date');
            $url .= '&interval=' . $form->getValue('interval');

            $toolbar->linkButton('download', Text::_('DOWNLOAD'))->url($url)->icon('fa fa-download');
        }

        $toolbar->standardButton('reset', Text::_('RESET'), 'export.reset')->icon('fa fa-undo');
    }
}