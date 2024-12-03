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

use THM\Organizer\Adapters\{Application, Document, HTML, Input, Text, Toolbar};
use THM\Organizer\Helpers\Subjects as Helper;

/** @inheritDoc */
class Subject extends FormView
{
    use Documented;

    /**
     * @inheritdoc
     */
    protected string $layout = 'subject';

    /**
     * @inheritDoc
     */
    protected function addToolBar(array $buttons = [], string $constant = ''): void
    {
        $subjectID = empty($this->item->id) ? 0 : $this->item->id;
        if ($this->layout === 'edit') {
            $this->title(empty($subjectID) ? Text::_('ADD_SUBJECT') : Text::_('EDIT_SUBJECT'));
            $toolbar   = Toolbar::getInstance();
            $saveGroup = $toolbar->dropdownButton('save-group');
            $saveBar   = $saveGroup->getChildToolbar();
            $saveBar->apply('subject.apply');
            $saveBar->apply('subject.applyImport', Text::_('APPLY_AND_IMPORT'))->icon('fa fa-file-import');
            $saveBar->save('subject.save');
            $saveBar->save('subject.saveImport', Text::_('SAVE_AND_IMPORT'))->icon('fa fa-file-import');
            $toolbar->cancel("subject.cancel");
        }
        elseif ($this->item->id and $subject = Helper::name($subjectID, true)) {
            $this->addDisclaimer();
            $this->title($subject);
        }
        // Subject layout for non-existent / invalid subject
        else {
            Application::error(404);
        }
    }

    /** @inheritDoc */
    protected function authorize(): void
    {
        if ($this->layout === 'edit' and !Helper::documentable(Input::getID())) {
            Application::error(403);
        }
    }

    /**
     * @inheritDoc
     */
    protected function modifyDocument(): void
    {
        $this->toDo[] = 'Front-end toolbar:';
        $this->toDo[] = '- PDF export (subject)';
        $this->toDo[] = '- Edit (subject)';
        $this->toDo[] = '- Close closes tab (subject)';

        if ($this->layout === 'subject') {
            Document::style('item');
        }
    }

    /**
     * Creates a standardized output for resource attributes.
     *
     * @param   string      $label     the label for the subjects as an attribute
     * @param   array|null  $programs  the programs mapped to the subjects in context
     *
     * @return void
     */
    public function renderSubjects(string $label, array|null $programs): void
    {
        if (!$programs) {
            return;
        }

        $url = 'index.php?option=com_organizer&view=subject&id=';
        ?>
        <div class="attribute">
            <div class="label"><?php echo Text::_($label); ?></div>
            <div class=\"value\">
                <ul>
                    <?php foreach ($programs as $program => $subjects): ?>
                        <li><?php echo $program; ?>
                            <ul>
                                <?php foreach ($subjects as $id => $name): ?>
                                    <li><?php echo HTML::link($url . $id, $name); ?></li>
                                <?php endforeach; ?>
                            </ul>
                        </li>
                    <?php endforeach; ?>
                </ul>
            </div>
        </div>
        <?php
    }
}