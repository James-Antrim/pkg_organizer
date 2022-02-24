<?php
/**
 * @package     Organizer
 * @extension   com_organizer
 * @author      James Antrim, <james.antrim@nm.thm.de>
 * @copyright   2020 TH Mittelhessen
 * @license     GNU GPL v.3
 * @link        www.thm.de
 */

namespace Organizer\Validators;

use Organizer\Helpers;
use Organizer\Tables;
use SimpleXMLElement;
use stdClass;

/**
 * Provides general functions for campus access checks, data retrieval and display.
 */
class Terms extends Helpers\ResourceHelper implements UntisXMLValidator
{
	/**
	 * @inheritDoc
	 */
	public static function setID(Schedule $model, string $code)
	{
		$loadCriteria = [
			['code' => $code],
			['endDate' => $model->term->endDate, 'startDate' => $model->term->startDate]
		];

		$table = new Tables\Terms();

		foreach ($loadCriteria as $criterion)
		{
			if ($exists = $table->load($criterion))
			{
				break;
			}
		}

		if (!$exists)
		{
			$term         = (array) $model->term;
			$term['code'] = $code;
			$shortEndYear = date('y', $term['endDate']);
			$startYear    = date('Y', $term['startDate']);
			$endYear      = date('Y', $term['endDate']);
			$shortYear    = $endYear !== $startYear ? "$startYear/$shortEndYear" : $startYear;

			switch ($term['name'])
			{
				case 'SS':
					$term['name_de']     = "SS $shortYear";
					$term['name_en']     = "Spring $startYear";
					$term['fullName_de'] = "Sommersemester $shortYear";
					$term['fullName_en'] = "Spring Term $startYear";
					break;
				case 'WS':
					$term['name_de']     = "WS $shortYear";
					$term['name_en']     = "Fall $startYear";
					$term['fullName_de'] = "Wintersemester $shortYear";
					$term['fullName_en'] = "Fall Term $startYear";
					break;
				default:
					$term['name_de']     = "{$term['name']} $shortYear";
					$term['name_en']     = "{$term['name']} $startYear";
					$term['fullName_de'] = "{$term['name']} $shortYear";
					$term['fullName_en'] = "{$term['name']} $startYear";
					break;
			}
			$table->save($term);
		}

		$model->termID = $table->id;
	}

	/**
	 * @inheritDoc
	 */
	public static function validate(Schedule $model, SimpleXMLElement $node)
	{
		$model->schoolYear            = new stdClass();
		$model->schoolYear->endDate   = trim((string) $node->schoolyearenddate);
		$model->schoolYear->startDate = trim((string) $node->schoolyearbegindate);

		$validSYED = $model->validateDate($model->schoolYear->endDate, 'SCHOOL_YEAR_END_DATE');
		$validSYSD = $model->validateDate($model->schoolYear->startDate, 'SCHOOL_YEAR_START_DATE');
		$valid     = ($validSYED and $validSYSD);

		$term            = new stdClass();
		$term->endDate   = trim((string) $node->termenddate);
		$validTED        = $model->validateDate($term->endDate, 'TERM_END_DATE');
		$term->code      = trim((string) $node->footer);
		$validTN         = $model->validateText($term->code, 'TERM_NAME', '/[\#\;]/');
		$term->startDate = trim((string) $node->termbegindate);
		$validTSD        = $model->validateDate($term->startDate, 'TERM_START_DATE');
		$valid           = ($valid and $validTED and $validTN and $validTSD);

		// Data type / value checks failed.
		if (!$valid)
		{
			$model->errors[] = Helpers\Languages::_('ORGANIZER_TERM_INVALID');

			return;
		}

		$endTimeStamp = strtotime($term->endDate);

		if ($endTimeStamp < strtotime(date('Y-m-d')))
		{
			$model->errors[] = Helpers\Languages::_('ORGANIZER_TERM_EXPIRED');

			return;
		}

		$invalidEnd = $endTimeStamp > strtotime($model->schoolYear->endDate);

		$startTimeStamp = strtotime($term->startDate);
		$invalidStart   = $startTimeStamp < strtotime($model->schoolYear->startDate);

		$invalidPeriod = $startTimeStamp >= $endTimeStamp;
		$invalid       = ($invalidStart or $invalidEnd or $invalidPeriod);

		// Consistency among the dates failed.
		if ($invalid)
		{
			$model->errors[] = Helpers\Languages::_('ORGANIZER_TERM_INVALID');

			return;
		}

		$model->term = $term;
		$code        = date('y', strtotime($term->endDate)) . $term->code;

		self::setID($model, $code);
	}
}
