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

use Joomla\CMS\Form\Field\ListField;
use Joomla\CMS\{Session\Session, Uri\Uri};
use THM\Organizer\Adapters\{Application, Database as DB, Document, Form, HTML, Input, Text};
use THM\Organizer\Helpers\Programs;

/**
 * Class creates a select box for programs to filter the context for subordinate resources.
 */
class Curricula extends ListField
{
    /**
     * @inheritDoc
     */
    public function getOptions(): array
    {
        $options = [HTML::option(-1, Text::_('NO_PROGRAMS'))];

        if (!$subjectID = Input::getID()) {
            return $options;
        }

        /** @var Form $form */
        $form     = $this->form;
        $resource = $form->view();

        $curriculumParameters = [
            'id'    => $subjectID,
            'token' => Session::getFormToken(),
            'type'  => $resource,

            // Root causes problems with the session in the administrator context.
            'url'   => Uri::base(),
        ];

        Document::scriptLocalizations('curriculumParameters', $curriculumParameters);
        Document::script('curricula');
        Document::script('multiple');

        $query = DB::query();
        $tag   = Application::tag();

        $parts = [DB::qn("p.name_$tag"), "' ('", DB::qn('d.abbreviation'), "', '", DB::qn('p.accredited'), "')'"];

        $select = ['DISTINCT ' . DB::qn('p.id'), $query->concatenate($parts, '') . ' AS ' . DB::qn('name')];
        $query->select($select)
            ->from(DB::qn('#__organizer_programs', 'p'))
            ->innerJoin(DB::qn('#__organizer_degrees', 'd'), DB::qc('d.id', 'p.degreeID'))
            ->innerJoin(DB::qn('#__organizer_curricula', 'c'), DB::qc('c.programID', 'p.id'))
            ->order('name ASC');
        DB::set($query);


        foreach (DB::arrays() as $program) {
            if (Programs::documentable($program['id'])) {
                $options[] = HTML::option($program['id'], $program['name']);
            }
        }

        return $options;
    }
}
