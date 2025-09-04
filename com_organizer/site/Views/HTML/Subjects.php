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

use Joomla\Registry\Registry;
use stdClass;
use THM\Organizer\Adapters\{Application, HTML, Input, Text, Toolbar};
use THM\Organizer\Helpers\{Can, Organizations, Persons, Pools, Programs};
use THM\Organizer\Layouts\HTML\Row;

/**
 * Class loads persistent information a filtered set of subjects into the display context.
 */
class Subjects extends ListView
{
    use Documented;

    private bool $access = false;

    private Registry $params;

    /**
     * @inheritDoc
     */
    public function __construct($config = [])
    {
        parent::__construct($config);
        $this->params = Input::parameters();
    }

    /**
     * @inheritDoc
     */
    protected function addToolBar(bool $delete = true): void
    {
        $resourceName = '';
        if (!Application::backend()) {
            if ($personID = $this->state->get('calledPersonID', 0)) {
                $resourceName = Persons::defaultName($personID);
                $resourceName .= ": " . Text::_('ORGANIZER_SUBJECTS');
            }
            else {
                if ($programID = Input::integer('programID')) {
                    $resourceName = Programs::name($programID);
                }
                if ($poolID = $this->state->get('calledPoolID', 0)) {
                    $poolName     = Pools::getFullName($poolID);
                    $resourceName .= empty($resourceName) ? $poolName : ", $poolName";
                }
            }
        }

        $this->title('SUBJECTS', $resourceName);

        if ($this->access) {
            $this->addAdd();
            $toolbar = Toolbar::getInstance();
            $toolbar->standardButton('upload', Text::_('IMPORT_LSF'), 'Subjects.import')->icon('fa fa-upload')->listCheck(true);
            $this->addDelete();

            if (Application::backend() and Can::administrate()) {
                $toolbar = Toolbar::getInstance();
                $toolbar->preferences('com_organizer');
            }
        }
    }

    /**
     * @inheritDoc
     */
    protected function authorize(): void
    {
        $this->access = (bool) Organizations::documentableIDs();
    }

    /**
     * @inheritDoc
     */
    protected function completeItem(int $index, stdClass $item, array $options = []): void
    {
        if (!$options['backend']) {
            if (empty($options['personID'])) {
                $item->persons = $this->getPersonDisplay($item);
            }

            $item->creditPoints = $item->creditPoints ?: '';
        }
    }

    /**
     * @param   array  $options  *
     *
     * @inheritDoc
     */
    protected function completeItems(array $options = []): void
    {
        if (!$options['backend'] = Application::backend()) {
            if ($personID = (int) $this->state->get('calledPersonID', 0)) {
                $options['personID'] = $personID;
            }
        }

        parent::completeItems($options);
    }

    /** @inheritDoc */
    public function display($tpl = null): void
    {
        $this->addDisclaimer();
        parent::display($tpl);
    }

    /**
     * @inheritDoc
     */
    public function initializeColumns(): void
    {
        $direction = $this->state->get('list.direction');
        $ordering  = $this->state->get('list.ordering');

        $headers = [
            'check' => ['type' => 'check'],
            'name'  => [
                'link'       => Application::backend() ? Row::DIRECT : Row::TAB,
                'properties' => ['class' => 'w-10 d-md-table-cell', 'scope' => 'col'],
                'title'      => HTML::sort('NAME', 'name', $direction, $ordering),
                'type'       => 'text'
            ],
            'code'  => [
                'properties' => ['class' => 'w-5 d-md-table-cell', 'scope' => 'col'],
                'title'      => HTML::sort('MODULE_CODE', 'code', $direction, $ordering),
                'type'       => 'text'
            ],
        ];

        if (Application::backend()) {
            $headers['program'] = [
                'properties' => ['class' => 'w-10 d-md-table-cell', 'scope' => 'col'],
                'title'      => Text::_('PROGRAM'),
                'type'       => 'text'
            ];
        }
        else {
            if (!$this->state->get('calledPersonID', 0)) {

                if ($role = (int) Input::parameters()->get('role') and $role === Persons::COORDINATES) {
                    $personsText = Text::_('COORDINATORS');
                }
                else {
                    $personsText = Text::_('TEACHERS');
                }

                $headers['persons'] = [
                    'properties' => ['class' => 'w-10 d-md-table-cell', 'scope' => 'col'],
                    'title'      => $personsText,
                    'type'       => 'text'
                ];
            }

            $headers['creditPoints'] = [
                'properties' => ['class' => 'w-5 d-md-table-cell', 'scope' => 'col'],
                'title'      => Text::_('CREDIT_POINTS'),
                'type'       => 'text'
            ];
        }

        $this->headers = $headers;
    }

    /**
     * Retrieves the person texts and formats them according to their roles for the subject being iterated
     *
     * @param   object  $subject  the subject being iterated
     *
     * @return string
     */
    private function getPersonDisplay(object $subject): string
    {
        $names = [];
        $role  = (int) Input::parameters()->get('role');

        if (count($subject->persons) > 3) {
            return $role === Persons::COORDINATES ?
                Text::_('ORGANIZER_COORDINATORS_PLACEHOLDER') :
                Text::_('ORGANIZER_TEACHERS_PLACEHOLDER');
        }

        foreach ($subject->persons as $person) {
            $name = $this->getPersonText($person);

            if (!$role) {
                $roles = [];
                if (isset($person['role'][Persons::COORDINATES])) {
                    $roles[] = Text::_('ORGANIZER_SUBJECT_COORDINATOR_ABBR');
                }
                if (isset($person['role'][Persons::TEACHES])) {
                    $roles[] = Text::_('ORGANIZER_TEACHER_ABBR');
                }

                $name .= ' (' . implode(', ', $roles) . ')';
            }

            $names[] = $name;
        }

        return implode('<br>', $names);
    }

    /**
     * Generates the person text (surname(, forename)?( title)?) for the given person
     *
     * @param   array  $person  the subject person
     *
     * @return string
     */
    public function getPersonText(array $person): string
    {
        $showTitle = (bool) $this->params->get('showTitle');

        $text = $person['surname'];

        if (!empty($person['forename'])) {
            $text .= ", {$person['forename']}";
        }

        if ($showTitle and !empty($person['title'])) {
            $text .= " {$person['title']}";
        }

        return $text;
    }
}
