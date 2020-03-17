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
 * Class creates a select box for programs to filter the context for subordinate resources.
 */
class CurriculaField extends FormField
{
	use Translated;

	/**
	 * @var  string
	 */
	protected $type = 'Curricula';

	/**
	 * Adds the javascript to the page necessary to refresh the super ordinate resource options
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
                jQuery('#jformcurricula').change(function () {
                    const cInput = jQuery('#jformcurricula'),
                        soInput = jQuery('#superordinates'),
                        oldSOs = soInput.val();
                    let selectedCurricula = cInput.val(), soURL;

                    if (selectedCurricula === null) {
                        selectedCurricula = '';
                    } else if (Array.isArray(selectedCurricula)) {
                        selectedCurricula = selectedCurricula.join(',');
                    }

                    if (selectedCurricula.includes('-1') !== false) {
                        cInput.find('option').removeAttr('selected');
                        return false;
                    }

                    soURL = '<?php echo Uri::root(); ?>index.php?option=com_organizer';
                    soURL += '&view=super_ordinates&format=json';
                    soURL += "&id=<?php echo $resourceID; ?>";
                    soURL += '&curricula=' + selectedCurricula;
                    soURL += "&type=<?php echo $resourceType; ?>";

                    jQuery.get(soURL, function (options) {
                        soInput.html(options);
                        const newSOs = soInput.val();
                        let selectedSOs = [];
                        if (newSOs !== null && newSOs.length) {
                            if (oldSOs !== null && oldSOs.length) {
                                selectedSOs = jQuery.merge(newSOs, oldSOs);
                            } else {
                                selectedSOs = newSOs;
                            }
                        } else if (oldSOs !== null && oldSOs.length) {
                            selectedSOs = oldSOs;
                        }

                        soInput.val(selectedSOs);
                    });
                });
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

		return Helpers\HTML::selectBox($programs, 'curricula', $attributes, $selectedPrograms, true);
	}

	/**
	 * Creates a list of programs to which the user has documentation access.
	 *
	 * @return array HTML options strings
	 */
	private function getOptions()
	{
		$query = Helpers\Programs::getQuery();
		$dbo   = Factory::getDbo();

		$query->innerJoin('#__organizer_curricula AS c ON c.programID = p.id')->order('name ASC');
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
