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

use THM\Organizer\Adapters\{Application, HTML, Input, Text, Toolbar};
use Joomla\Registry\Registry;
use THM\Organizer\Helpers\{Can, Persons, Pools, Programs, Routing};

/**
 * Class loads persistent information a filtered set of subjects into the display context.
 */
class Subjects extends ListView
{
    use Documented;

    private const ALL = 0, COORDINATES = 1, TEACHES = 2;

    private bool $documentAccess = false;

    private Registry $params;

    /**
     * @inheritdoc
     */
    public function __construct($config = [])
    {
        parent::__construct($config);
        $this->params = Input::getParams();
    }

    /**
     * @inheritdoc
     */
    protected function addToolBar(bool $delete = true): void
    {
        $resourceName = '';
        if (!Application::backend()) {
            if ($personID = $this->state->get('calledPersonID', 0)) {
                $resourceName = Persons::getDefaultName($personID);
                $resourceName .= ": " . Text::_('ORGANIZER_SUBJECTS');
            }
            else {
                if ($programID = Input::getInt('programID')) {
                    $resourceName = Programs::getName($programID);
                }
                if ($poolID = $this->state->get('calledPoolID', 0)) {
                    $poolName     = Pools::getFullName($poolID);
                    $resourceName .= empty($resourceName) ? $poolName : ", $poolName";
                }
            }
        }

        $this->setTitle('ORGANIZER_SUBJECTS', $resourceName);
        $toolbar = Toolbar::getInstance();
        if ($this->documentAccess) {
            $toolbar->appendButton('Standard', 'new', Text::_('ORGANIZER_ADD'), 'subjects.add', false);
            $toolbar->appendButton('Standard', 'edit', Text::_('ORGANIZER_EDIT'), 'subjects.edit', true);
            $toolbar->appendButton(
                'Standard',
                'upload',
                Text::_('ORGANIZER_IMPORT_LSF'),
                'subjects.import',
                true
            );
            $toolbar->appendButton(
                'Confirm',
                Text::_('ORGANIZER_DELETE_CONFIRM'),
                'delete',
                Text::_('ORGANIZER_DELETE'),
                'subjects.delete',
                true
            );
        }
    }

    /**
     * @inheritdoc
     */
    protected function authorize(): void
    {
        if (!Application::backend()) {
            return;
        }

        if (!$this->documentAccess = (bool) Can::documentTheseOrganizations()) {
            Application::error(403);
        }
    }

    /**
     * @inheritdoc
     */
    protected function completeItems(): void
    {
        $index           = 0;
        $structuredItems = [];

        $attributes = [];
        if (!Application::backend()) {
            $attributes['target'] = '_blank';
        }

        $calledPersonID = (int) $this->state->get('calledPersonID', 0);

        foreach ($this->items as $subject) {
            $access   = Can::document('subject', (int) $subject->id);
            $checkbox = $access ? HTML::checkBox($index, $subject->id) : '';
            $thisLink = (Application::backend() and $access) ?
                Routing::getViewURL('SubjectEdit', $subject->id) : Routing::getViewURL('SubjectItem', $subject->id);

            $structuredItems[$index] = [];

            if (Application::backend() or $this->documentAccess) {
                $structuredItems[$index]['checkbox'] = $checkbox;
            }

            $structuredItems[$index]['name'] = HTML::link($thisLink, $subject->name, $attributes);
            $structuredItems[$index]['code'] = HTML::link($thisLink, $subject->code, $attributes);

            if (!$calledPersonID) {
                $structuredItems[$index]['persons'] = $this->getPersonDisplay($subject);
            }

            $structuredItems[$index]['creditPoints'] = empty($subject->creditPoints) ? '' : $subject->creditPoints;

            $index++;
        }

        $this->items = $structuredItems;
    }

    /** @inheritdoc */
    public function display($tpl = null): void
    {
        $this->addDisclaimer();
        parent::display($tpl);
    }

    /**
     * @inheritdoc
     */
    public function initializeColumns(): void
    {
        $direction = $this->state->get('list.direction');
        $ordering  = $this->state->get('list.ordering');
        $headers   = [];

        if (Application::backend() or $this->documentAccess) {
            $headers['checkbox'] = (Application::backend() and $this->documentAccess) ?
                HTML::checkAll() : '';
        }

        $headers['name'] = HTML::sort('NAME', 'name', $direction, $ordering);
        $headers['code'] = HTML::sort('MODULE_CODE', 'code', $direction, $ordering);

        if (!$this->state->get('calledPersonID', 0)) {
            if ($role = (int) Input::getParams()->get('role') and $role === self::COORDINATES) {
                $personsText = Text::_('COORDINATORS');
            }
            else {
                $personsText = Text::_('TEACHERS');
            }
            $headers['persons'] = $personsText;
        }

        $headers['creditPoints'] = Text::_('CREDIT_POINTS');

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
        $role  = (int) Input::getParams()->get('role');

        if (count($subject->persons) > 3) {
            return $role === self::COORDINATES ?
                Text::_('ORGANIZER_COORDINATORS_PLACEHOLDER') :
                Text::_('ORGANIZER_TEACHERS_PLACEHOLDER');
        }

        foreach ($subject->persons as $person) {
            $name = $this->getPersonText($person);

            if ($role === self::ALL) {
                $roles = [];
                if (isset($person['role'][self::COORDINATES])) {
                    $roles[] = Text::_('ORGANIZER_SUBJECT_COORDINATOR_ABBR');
                }
                if (isset($person['role'][self::TEACHES])) {
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
