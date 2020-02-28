<?php
/**
 * @package     Organizer
 * @extension   com_organizer
 * @author      James Antrim, <james.antrim@nm.thm.de>
 * @copyright   2020 TH Mittelhessen
 * @license     GNU GPL v.3
 * @link        www.thm.de
 */

namespace Organizer\Helpers;

use Joomla\CMS\Factory;
use Organizer\Tables;

/**
 * Provides general functions for room type access checks, data retrieval and display.
 */
class Fields extends ResourceHelper implements Selectable
{
	/**
	 * Returns the color value associated with the field.
	 *
	 * @param   int  $fieldID         the id of the field
	 * @param   int  $organizationID  the id of the organization
	 *
	 * @return string the hexadecimal color value associated with the field
	 */
	public static function getColor($fieldID, $organizationID)
	{
		$default = Input::getParams()->get('backgroundColor', '#f2f5f6');
		$table   = new Tables\FieldColors;
		$exists  = $table->load(['fieldID' => $fieldID, 'organizationID' => $organizationID]);
		if (!$exists or empty($table->colorID))
		{
			return $default;
		}

		return Colors::getColor($table->colorID);
	}

	/**
	 * Creates the display for a field item as used in a list view.
	 *
	 * @param   int  $fieldID         the id of the field
	 * @param   int  $organizationID  the id of the organization
	 *
	 * @return string the HTML output of the field attribute display
	 */
	public static function getFieldColorDisplay($fieldID, $organizationID = 0)
	{
		if (!$fieldID)
		{
			return '';
		}

		$organizationIDs = $organizationID ? [$organizationID] : Organizations::getIDs();
		$return          = '';

		foreach ($organizationIDs as $organizationID)
		{
			$table = new Tables\FieldColors;
			if ($table->load(['fieldID' => $fieldID, 'organizationID' => $organizationID]))
			{
				$link         = 'index.php?option=com_organizer&view=field_color_edit&id=' . $table->id;
				$organization = Organizations::getShortName($organizationID);
				$text         = HTML::_('link', $link, $organization);
				$return       .= Colors::getListDisplay($text, $table->colorID);
			}
		}

		return $return;
	}

	/**
	 * Creates the display for a field item as used in a list view.
	 *
	 * @param   int  $fieldID         the id of the field
	 * @param   int  $organizationID  the id of the organization
	 *
	 * @return string the HTML output of the field attribute display
	 */
	public static function getColoredDisplay($fieldID, $organizationID = 0)
	{
		if (!$fieldID)
		{
			return '';
		}

		$organizationIDs = $organizationID ? [$organizationID] : Organizations::getIDs();
		$return          = '';

		foreach ($organizationIDs as $organizationID)
		{
			$table = new Tables\FieldColors;
			if ($table->load(['fieldID' => $fieldID, 'organizationID' => $organizationID]))
			{
				$link         = 'index.php?option=com_organizer&view=field_color_edit&id=' . $table->id;
				$organization = Organizations::getShortName($organizationID);
				$text         = HTML::_('link', $link, $organization);
				$return       .= Colors::getListDisplay($text, $table->colorID);
			}
		}

		return $return;
	}

	/**
	 * Retrieves the selectable options for the resource.
	 *
	 * @return array the available options
	 */
	public static function getOptions()
	{
		$options = [];
		foreach (self::getResources() as $field)
		{
			$options[] = HTML::_('select.option', $field['id'], $field['name']);
		}

		return $options;
	}

	/**
	 * Retrieves the resource items.
	 *
	 * @param   string  $access  any access restriction which should be performed
	 *
	 * @return array the available resources
	 */
	public static function getResources()
	{
		$tag   = Languages::getTag();
		$dbo   = Factory::getDbo();
		$query = $dbo->getQuery(true);
		$query->select("DISTINCT *, name_$tag AS name")
			->from('#__organizer_fields')
			->order('name');

		$dbo->setQuery($query);

		return OrganizerHelper::executeQuery('loadAssocList', []);
	}
}
