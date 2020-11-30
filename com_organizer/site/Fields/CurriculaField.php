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

use Joomla\CMS\Form\FormField;
use Joomla\CMS\Uri\Uri;
use Organizer\Adapters;
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
	 * Returns a select box where stored degree program can be chosen
	 *
	 * @return string  the HTML for the select box
	 */
	public function getInput()
	{
		$resourceID   = $this->form->getValue('id');
		$contextParts = explode('.', $this->form->getName());
		$resourceType = str_replace('edit', '', $contextParts[1]);

		$curriculumParameters = [
			'rootURL' => Uri::root(),
			'id'      => $resourceID,
			'type'    => $resourceType
		];

		Adapters\Document::addScriptOptions('curriculumParameters', $curriculumParameters);

		$ranges = $resourceType === 'pool' ?
			Helpers\Pools::getRanges($resourceID) : Helpers\Subjects::getRanges($resourceID);

		$selectedPrograms = empty($ranges) ? [] : Helpers\Programs::getIDs($ranges);
		$options          = $this->getOptions();

		$defaultOptions = [Helpers\HTML::_('select.option', '-1', Helpers\Languages::_('ORGANIZER_NONE'))];
		$programs       = array_merge($defaultOptions, $options);
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
		$query->innerJoin('#__organizer_curricula AS c ON c.programID = p.id')->order('name ASC');
		Adapters\Database::setQuery($query);

		if (!$programs = Adapters\Database::loadAssocList())
		{
			return [];
		}

		$options = [];
		foreach ($programs as $program)
		{
			if (!Helpers\Can::document('program', (int) $program['id']))
			{
				continue;
			}

			$options[] = Helpers\HTML::_('select.option', $program['id'], $program['name']);
		}

		return $options;
	}
}
