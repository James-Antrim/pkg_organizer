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

use THM\Organizer\Adapters\Toolbar;
use THM\Organizer\Helpers;
use THM\Organizer\Helpers\Routing;

/**
 * Class loads persistent information a filtered set of subjects into the display context.
 */
class Subjects extends ListView
{
    private const ALL = 0, COORDINATES = 1, TEACHES = 2;

    private $documentAccess = false;

    private $params;

    /**
     * @inheritdoc
     */
    public function __construct($config = [])
    {
        parent::__construct($config);
        $this->params = Helpers\Input::getParams();
    }

    /**
     * @inheritdoc
     */
    protected function addToolBar(bool $delete = true)
    {
        $resourceName = '';
        if (!$this->adminContext) {
            if ($personID = $this->state->get('calledPersonID', 0)) {
                $resourceName = Helpers\Persons::getDefaultName($personID);
                $resourceName .= ": " . Helpers\Languages::_('ORGANIZER_SUBJECTS');
            } else {
                if ($programID = Helpers\Input::getInt('programID')) {
                    $resourceName = Helpers\Programs::getName($programID);
                }
                if ($poolID = $this->state->get('calledPoolID', 0)) {
                    $poolName     = Helpers\Pools::getFullName($poolID);
                    $resourceName .= empty($resourceName) ? $poolName : ", $poolName";
                }
            }
        }

        $this->setTitle('ORGANIZER_SUBJECTS', $resourceName);
        $toolbar = Toolbar::getInstance();
        if ($this->documentAccess) {
            $toolbar->appendButton('Standard', 'new', Helpers\Languages::_('ORGANIZER_ADD'), 'subjects.add', false);
            $toolbar->appendButton('Standard', 'edit', Helpers\Languages::_('ORGANIZER_EDIT'), 'subjects.edit', true);
            $toolbar->appendButton(
                'Standard',
                'upload',
                Helpers\Languages::_('ORGANIZER_IMPORT_LSF'),
                'subjects.import',
                true
            );
            $toolbar->appendButton(
                'Confirm',
                Helpers\Languages::_('ORGANIZER_DELETE_CONFIRM'),
                'delete',
                Helpers\Languages::_('ORGANIZER_DELETE'),
                'subjects.delete',
                true
            );
        }
    }

    /**
     * @inheritdoc
     */
    protected function authorize()
    {
        if (!$this->adminContext) {
            return;
        }

        if (!$this->documentAccess = Helpers\Can::documentTheseOrganizations()) {
            Helpers\OrganizerHelper::error(403);
        }
    }

    /**
     * @inheritdoc
     */
    public function setHeaders()
    {
        $direction = $this->state->get('list.direction');
        $ordering  = $this->state->get('list.ordering');
        $headers   = [];

        if ($this->adminContext or $this->documentAccess) {
            $headers['checkbox'] = ($this->adminContext and $this->documentAccess) ?
                Helpers\HTML::_('grid.checkall') : '';
        }

        $headers['name'] = Helpers\HTML::sort('NAME', 'name', $direction, $ordering);
        $headers['code'] = Helpers\HTML::sort('MODULE_CODE', 'code', $direction, $ordering);

        if (!$this->state->get('calledPersonID', 0)) {
            if ($role = (int) Helpers\Input::getParams()->get('role') and $role === self::COORDINATES) {
                $personsText = Helpers\Languages::_('ORGANIZER_COORDINATORS');
            } else {
                $personsText = Helpers\Languages::_('ORGANIZER_TEACHERS');
            }
            $headers['persons'] = $personsText;
        }

        $headers['creditPoints'] = Helpers\Languages::_('ORGANIZER_CREDIT_POINTS');

        $this->headers = $headers;
    }

    /**
     * Retrieves the person texts and formats them according to their roles for the subject being iterated
     *
     * @param object $subject the subject being iterated
     *
     * @return string
     */
    private function getPersonDisplay(object $subject): string
    {
        $names = [];
        $role  = (int) Helpers\Input::getParams()->get('role');

        if (count($subject->persons) > 3) {
            return $role === self::COORDINATES ?
                Helpers\Languages::_('ORGANIZER_COORDINATORS_PLACEHOLDER') :
                Helpers\Languages::_('ORGANIZER_TEACHERS_PLACEHOLDER');
        }

        foreach ($subject->persons as $person) {
            $name = $this->getPersonText($person);

            if ($role === self::ALL) {
                $roles = [];
                if (isset($person['role'][self::COORDINATES])) {
                    $roles[] = Helpers\Languages::_('ORGANIZER_SUBJECT_COORDINATOR_ABBR');
                }
                if (isset($person['role'][self::TEACHES])) {
                    $roles[] = Helpers\Languages::_('ORGANIZER_TEACHER_ABBR');
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
     * @param array $person the subject person
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

    /**
     * @inheritdoc
     */
    protected function structureItems()
    {
        $index           = 0;
        $structuredItems = [];

        $attributes = [];
        if (!$this->adminContext) {
            $attributes['target'] = '_blank';
        }

        $calledPersonID = (int) $this->state->get('calledPersonID', 0);

        foreach ($this->items as $subject) {
            $access   = Helpers\Can::document('subject', (int) $subject->id);
            $checkbox = $access ? Helpers\HTML::_('grid.id', $index, $subject->id) : '';
            $thisLink = ($this->adminContext and $access) ?
                Routing::getViewURL('SubjectEdit', $subject->id) : Routing::getViewURL('SubjectItem', $subject->id);

            $structuredItems[$index] = [];

            if ($this->adminContext or $this->documentAccess) {
                $structuredItems[$index]['checkbox'] = $checkbox;
            }

            $structuredItems[$index]['name'] = Helpers\HTML::_('link', $thisLink, $subject->name, $attributes);
            $structuredItems[$index]['code'] = Helpers\HTML::_('link', $thisLink, $subject->code, $attributes);

            if (!$calledPersonID) {
                $structuredItems[$index]['persons'] = $this->getPersonDisplay($subject);
            }

            $structuredItems[$index]['creditPoints'] = empty($subject->creditPoints) ? '' : $subject->creditPoints;

            $index++;
        }

        $this->items = $structuredItems;
    }
}
