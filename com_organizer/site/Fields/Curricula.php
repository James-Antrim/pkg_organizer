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
use THM\Organizer\Adapters\{Database as DB, Document, Form, HTML, Input, Text};
use THM\Organizer\Helpers\Programs;

/** @inheritDoc */
class Curricula extends ListField
{
    /** @inheritDoc */
    public function getOptions(): array
    {
        $options = [HTML::option(-1, Text::_('NO_PROGRAMS'))];

        if (!$subjectID = Input::id()) {
            return $options;
        }

        /** @var Form $form */
        $form     = $this->form;
        $resource = $form->view();

        $curriculumParameters = [
            'id' => $subjectID,
            'token' => Session::getFormToken(),
            'type' => $resource,

            // Root causes problems with the session in the administrator context.
            'url' => Uri::base(),
        ];

        Document::scriptLocalizations('curriculumParameters', $curriculumParameters);
        Document::script('curricula');
        Document::script('multiple');

        $query = DB::query();
        $query->select(DB::qn(['c.id', 'p.id'], ['curriculumID', 'programID']))
            ->from(DB::qn('#__organizer_programs', 'p'))
            ->innerJoin(DB::qn('#__organizer_curricula', 'c'), DB::qc('c.programID', 'p.id'));
        DB::set($query);

        foreach (DB::arrays() as $entry) {
            if (Programs::documentable($entry['programID'])) {
                $unique           = Programs::name($entry['programID']) . ' (ID: ' . $entry['programID'] . ')';
                $options[$unique] = HTML::option($entry['programID'], $unique);
            }
        }

        ksort($options);

        return $options;
    }
}
