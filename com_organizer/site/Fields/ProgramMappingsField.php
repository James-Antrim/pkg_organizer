<?php
/**
 * @package     Organizer
 * @extension   com_organizer
 * @author      James Antrim, <james.antrim@nm.thm.de>
 * @copyright   2020 TH Mittelhessen
 * @license     GNU GPL v.3
 * @link        www.thm.de
 */

namespace Organizer\Fields;

use Joomla\CMS\Factory;
use Joomla\CMS\Form\FormField;
use Joomla\CMS\Uri\Uri;
use Organizer\Helpers;

/**
 * Class creates a select box for (degree) program mappings.
 */
class ProgramMappingsField extends FormField
{
	use Translated;

	/**
	 * @var  string
	 */
	protected $type = 'ProgramMappings';

	/**
	 * Adds the javascript to the page necessary to refresh the parent pool options
	 *
	 * @param   int     $resourceID    the resource's id
	 * @param   string  $resourceType  the resource's type
	 *
	 * @return void
	 */
	private function addScript($resourceID, $resourceType)
	{
		?>
        <script type="text/javascript" charset="utf-8">
            jQuery(document).ready(function () {
                jQuery('#jformprogramID').change(function () {
                    const programInput = jQuery('#jformprogramID'),
                        parentInput = jQuery('#jformparentID'),
                        oldSelectedParents = parentInput.val();
                    let selectedPrograms = programInput.val(),
                        poolUrl;

                    if (selectedPrograms === null)
                    {
                        selectedPrograms = '';
                    }
                    else if (Array.isArray(selectedPrograms))
                    {
                        selectedPrograms = selectedPrograms.join(',');
                    }

                    if (selectedPrograms.includes('-1') !== false)
                    {
                        programInput.find('option').removeAttr('selected');
                        return false;
                    }

                    poolUrl = '<?php echo Uri::root(); ?>index.php?option=com_organizer';
                    poolUrl += '&view=pools&format=json&task=getParentOptions';
                    poolUrl += "&id=<?php echo $resourceID; ?>";
                    poolUrl += "&type=<?php echo $resourceType; ?>";
                    poolUrl += '&programIDs=' + selectedPrograms;

                    jQuery.get(poolUrl, function (options) {
                        parentInput.html(options);
                        const newSelectedParents = parentInput.val();
                        let selectedParents = [];
                        if (newSelectedParents !== null && newSelectedParents.length)
                        {
                            if (oldSelectedParents !== null && oldSelectedParents.length)
                            {
                                selectedParents = jQuery.merge(newSelectedParents, oldSelectedParents);
                            }
                            else
                            {
                                selectedParents = newSelectedParents;
                            }
                        }
                        else if (oldSelectedParents !== null && oldSelectedParents.length)
                        {
                            selectedParents = oldSelectedParents;
                        }

                        parentInput.val(selectedParents);

                        refreshChosen('jformparentID');
                    });
                    refreshChosen('jformparentID');
                });

                function refreshChosen(id)
                {
                    const chosenElement = jQuery('#' + id);
                    chosenElement.chosen('destroy');
                    chosenElement.chosen();
                }

                function toggleElement(chosenElement, value)
                {
                    const parentInput = jQuery('#jformparentID');
                    parentInput.chosen('destroy');
                    jQuery('select#jformparentID option').each(function () {
                        if (chosenElement === jQuery(this).innerHTML)
                        {
                            jQuery(this).prop('selected', value);
                        }
                    });
                    parentInput.chosen();
                }

                function addAddHandler()
                {
                    jQuery('#jformparentID_chzn').find('div.chzn-drop').click(function (element) {
                        toggleElement(element.target.innerHTML, true);
                        addRemoveHandler();
                    });
                }

                function addRemoveHandler()
                {
                    jQuery('div#jformparentID_chzn').find('a.search-choice-close').click(function (element) {
                        toggleElement(element.target.parentElement.childNodes[0].innerHTML, false);
                        addAddHandler();
                    });
                }

                addRemoveHandler();
                addAddHandler();
            });
        </script>
		<?php
	}

	/**
	 * Returns a select box where stored degree program can be chosen
	 *
	 * @return string  the HTML for the select box
	 */
	public function getInput()
	{
		$resourceID   = $this->form->getValue('id');
		$contextParts = explode('.', $this->form->getName());
		$resourceType = str_replace('edit', '', $contextParts[1]);
		$this->addScript($resourceID, $resourceType);

		$ranges = $resourceType === 'pool' ?
			Helpers\Pools::getRanges($resourceID) : Helpers\Subjects::getRanges($resourceID);

		$selectedPrograms = empty($ranges) ? [] : Helpers\Programs::getIDs($ranges);
		$options          = $this->getOptions();

		$defaultOptions = [Helpers\HTML::_('select.option', '-1', Helpers\Languages::_('ORGANIZER_NONE'))];
		$programs       = $defaultOptions + $options;
		$attributes     = ['multiple' => 'multiple', 'size' => '10'];

		return Helpers\HTML::selectBox($programs, 'programID', $attributes, $selectedPrograms, true);
	}

	/**
	 * Creates a list of programs to which the user has documentation access.
	 *
	 * @return array HTML options strings
	 */
	private function getOptions()
	{
		$query = Helpers\Programs::getProgramQuery();
		$dbo   = Factory::getDbo();

		$query->innerJoin('#__organizer_mappings AS m ON m.programID = p.id')->order('name ASC');
		$dbo->setQuery($query);

		$programs = Helpers\OrganizerHelper::executeQuery('loadAssocList');
		if (empty($programs))
		{
			return [];
		}

		$options = [];
		foreach ($programs as $program)
		{
			if (!Helpers\Can::document('program', $program['id']))
			{
				continue;
			}

			$options[] = Helpers\HTML::_('select.option', $program['id'], $program['name']);
		}

		return $options;
	}
}
