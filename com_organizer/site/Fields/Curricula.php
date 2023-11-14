<?php
/**
 * @package     Organizer
 * @extension   com_organizer
 * @author      James Antrim, <james.antrim@nm.thm.de>
 * @copyright   2020 TH Mittelhessen
 * @license     GNU GPL v.3
 * @link        www.thm.de
 */

namespace THM\Organizer\Fields;

use Joomla\CMS\Form\FormField;
use Joomla\CMS\Uri\Uri;
use THM\Organizer\Adapters\{Database, Document, HTML, Text};
use THM\Organizer\Helpers;
use stdClass;

/**
 * Class creates a select box for programs to filter the context for subordinate resources.
 */
class Curricula extends FormField
{
    use Translated;

    /**
     * @var  string
     */
    protected $type = 'Curricula';

    /**
     * Returns a select box where stored degree program can be chosen
     * @return string  the HTML for the select box
     */
    public function getInput(): string
    {
        $resourceID   = $this->form->getValue('id');
        $contextParts = explode('.', $this->form->getName());
        $resourceType = str_replace('edit', '', $contextParts[1]);

        $curriculumParameters = [
            'rootURL' => Uri::root(),
            'id'      => $resourceID,
            'type'    => $resourceType
        ];

        Document::scriptLocalizations('curriculumParameters', $curriculumParameters);

        $ranges = $resourceType === 'pool' ?
            Helpers\Pools::getRanges($resourceID) : Helpers\Subjects::getRanges($resourceID);

        $selectedPrograms = empty($ranges) ? [] : Helpers\Programs::getIDs($ranges);
        $options          = $this->getOptions();

        $defaultOptions = [HTML::option(-1, Text::_('NONE'))];
        $programs       = array_merge($defaultOptions, $options);
        $attributes     = ['multiple' => 'multiple', 'size' => '10'];

        return HTML::selectBox('curricula', $programs, $attributes, $selectedPrograms);
    }

    /**
     * Creates a list of programs to which the user has documentation access.
     * @return stdClass[] HTML options strings
     */
    private function getOptions(): array
    {
        $query = Helpers\Programs::getQuery();
        $query->innerJoin('#__organizer_curricula AS c ON c.programID = p.id')->order('name ASC');
        Database::setQuery($query);

        if (!$programs = Database::loadAssocList()) {
            return [];
        }

        $options = [];
        foreach ($programs as $program) {
            if (!Helpers\Can::document('program', (int) $program['id'])) {
                continue;
            }

            $options[] = HTML::option($program['id'], $program['name']);
        }

        return $options;
    }
}
