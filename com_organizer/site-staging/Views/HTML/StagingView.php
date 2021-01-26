<?php
/**
 * @package     Organizer
 * @extension   com_organizer
 * @author      James Antrim, <james.antrim@nm.thm.de>
 * @copyright   2020 TH Mittelhessen
 * @license     GNU GPL v.3
 * @link        www.thm.de
 */

namespace Organizer\Views\HTML;

use Joomla\CMS\Uri\Uri;
use Organizer\Adapters;
use Organizer\Helpers;

/**
 * Class loads person workload statistics into the display context.
 */
class StagingView extends BaseView
{
	public $model = null;

	public $params = null;

	public $scheduleSelectBox = '';

	public $startCalendar = '';

	public $table = '';

	public $persons;

	/**
	 * Method to get display
	 *
	 * @param   Object  $tpl  template  (default: null)
	 *
	 * @return void
	 */
	public function display($tpl = null)
	{
		if (!Helpers\Can::administrate())
		{
			Helpers\OrganizerHelper::error(403);
		}

		// Sets js and css
		$this->modifyDocument();

		$this->params = Helpers\Input::getParams();

		$this->model            = $this->getModel();
		$this->organizationName = $this->model->organizationName;
		$this->makeScheduleSelectBox();

		if (!empty($this->model->schedule))
		{
			$this->makePersonSelectBox();
			$this->tables = $this->getWorkloadTables();
		}
		parent::display($tpl);
	}

	/**
	 * Modifies document variables and adds links to external files
	 *
	 * @return void
	 */
	protected function modifyDocument()
	{
		parent::modifyDocument();

		Adapters\Document::addScript(Uri::root() . 'components/com_organizer/js/workload.js');
	}

	/**
	 * Creates a select box for the active schedules
	 *
	 * @return void
	 */
	private function makeScheduleSelectBox()
	{
		$scheduleID = $this->model->scheduleID;
		$schedules  = $this->model->getOrganizationSchedules();

		$options    = [];
		$options[0] = Helpers\Languages::_('ORGANIZER_FILTER_SCHEDULE');
		foreach ($schedules as $schedule)
		{
			$options[$schedule['id']] = $schedule['name'];
		}

		$attribs             = [];
		$attribs['onChange'] = "jQuery('#reset').val('1');this.form.submit();";

		$this->scheduleSelectBox = Helpers\HTML::selectBox($options, 'scheduleID', $attribs, $scheduleID);
	}

	/**
	 * Creates a select box for persons
	 *
	 * @return void
	 */
	private function makePersonSelectBox()
	{
		$persons = $this->model->persons;

		$options      = [];
		$options['*'] = Helpers\Languages::_('JALL');
		foreach ($persons as $personID => $personName)
		{
			$options[$personID] = $personName;
		}

		$attribs         = ['multiple' => 'multiple', 'size' => '10'];
		$selectedPersons = $this->model->selected;
		$this->persons   = Helpers\HTML::selectBox($options, 'persons', $attribs, $selectedPersons);
	}

	/**
	 * Function to get a table displaying resource consumption for a schedule
	 *
	 * @return string  a HTML string for a consumption table
	 */
	public function getWorkloadTables()
	{
		$tables = [];
		foreach ($this->model->workload as $personID => $workload)
		{
			$displaySummary = !empty($workload['summary']);
			$displayTally   = !empty($workload['tally']);

			$table = '<table class="workload-table" id="workload-table-' . $personID . '">';
			$table .= '<thead class="workload-table-head-' . $personID . '">';
			$table .= '<tr class="person-header"><th colspan="5">' . $workload['name'] . '</th></tr></thead>';
			if ($displaySummary)
			{
				$table .= '<tbody class="workload-table-body" id="workload-table-body-sum-' . $personID . '">';
				$table .= '<tr class="sum-header">';
				$table .= '<th>Lehrveranstaltung</th>';
				$table .= '<th>Art<br/>(Kürzel)</th>';
				$table .= '<th>Studiengang Semester</th>';
				$table .= '<th>Wochentag u. Stunde<br/>(bei Blockveranstalt. Datum)</th>';
				$table .= '<th>Gemeldetes Deputat (SWS)<br/> und Summe</th>';
				$table .= '</tr>';
				$table .= $this->getSummaryRows($personID, $workload);
				$table .= '</tbody>';
			}
			if ($displayTally)
			{
				$table      .= '<tbody class="workload-table-body" id="deputat-table-body-tally-' . $personID . '">';
				$extraClass = $displaySummary ? 'second-group' : '';
				$table      .= '<tr class="tally-header ' . $extraClass . '">';
				$table      .= '<th>Rechtsgrundlage<br/>gemäß LVVO</th>';
				$table      .= '<th>Art der Abschlussarbeit<br/>(nur bei Betreuung als Referent/in)</th>';
				$table      .= '<th>Umfang der Anrechnung in SWS je Arbeit<br />(insgesamt max. 2 SWS)</th>';
				$table      .= '<th>Anzahl der Arbeiten</th>';
				$table      .= '<th>Gemeldetes Deputat (SWS)</th>';
				$table      .= '</tr>';
				$table      .= $this->getTallyRows($personID, $workload);
				$table      .= '</tbody>';
			}
			$table    .= '</table>';
			$tables[] = $table;
		}

		return implode('', $tables);
	}

	/**
	 * Retrieves a rows containing information about
	 *
	 * @param   int    $personID  the personID
	 * @param   array &$workload  the table columns
	 *
	 * @return string  HTML string for the summary row
	 */
	private function getSummaryRows($personID, &$workload)
	{
		$rows      = [];
		$swsSum    = 0;
		$realSum   = 0;
		$weeks     = $this->params->get('workload_weeks', 13);
		$rowNumber = 0;
		foreach ($workload['summary'] as $summary)
		{
			$rowID       = $personID . '-' . $rowNumber;
			$remove      = '<a id="remove-data-row-' . $rowID . '" onclick="removeRow(this)">';
			$remove      .= '<i class="icon-remove"></i>';
			$remove      .= '</a>';
			$periodsText = (count($summary['periods']) > 10) ?
				"{$summary['startDate']} bis {$summary['endDate']}" : implode(', ', array_keys($summary['periods']));
			$row         = '<tr class="data-row" id="data-row-' . $rowID . '">';
			$row         .= '<td>' . $summary['name'] . '</td>';
			$row         .= '<td>' . $summary['type'] . '</td>';
			$row         .= '<td>' . implode(',', $summary['pools']) . '</td>';
			$row         .= '<td>' . $periodsText . '</td>';
			$sws         = ceil((int) $summary['hours'] / $weeks);
			$row         .= '<td>';
			$row         .= '<span class="row-sws" id="row-sws-' . $rowID . '">' . $sws . '</span>';
			$row         .= ' (<span class="row-sws" id="row-total-' . $rowID . '">' . $summary['hours'] . '</span>)';
			$row         .= $remove . '</td>';
			$swsSum      += $sws;
			$realSum     += $summary['hours'];
			$row         .= '</tr>';
			$rows[]      = $row;
			$rowNumber++;
		}
		$sumRow = '<tr class="sum-row-' . $personID . '">';
		$sumRow .= '<td class="empty-cell"></td>';
		$sumRow .= '<td class="empty-cell"></td>';
		$sumRow .= '<td class="empty-cell"></td>';
		$sumRow .= '<td>Summe</td>';
		$sumRow .= '<td>';
		$sumRow .= '<span class="sum-sws" id="sum-sws-' . $personID . '">' . $swsSum . '</span>';
		$sumRow .= ' (<span class="sum-total" id="sum-total-' . $personID . '">' . $realSum . '</span>)';
		$sumRow .= '</td>';
		$sumRow .= '</tr>';
		$rows[] = $sumRow;

		return implode('', $rows);
	}

	/**
	 * Retrieves a row containing a summary of the column values in all the other rows. In the process it removes
	 * columns without values.
	 *
	 * @param   int    $personID  the personID
	 * @param   array &$workload  the table columns
	 *
	 * @return string  HTML string for the summary row
	 */
	private function getTallyRows($personID, &$workload)
	{
		$rows   = [];
		$swsSum = 0;
		foreach ($workload['tally'] as $name => $data)
		{
			$sws    = $data['rate'] * $data['count'];
			$swsSum += $sws;
			$row    = '<tr class="data-row-' . $personID . '">';
			$row    .= '<td>LVVO § 2 (5)</td>';
			$row    .= '<td>' . $name . '</td>';
			$row    .= '<td>' . $data['rate'] . '</td>';
			$row    .= '<td>' . $data['count'] . '</td>';
			$row    .= '<td>' . $sws . '</td>';
			$row    .= '</tr>';
			$rows[] = $row;
		}
		$sumRow = '<tr class="sum-row-' . $personID . '">';
		$sumRow .= '<td class="empty-cell"></td>';
		$sumRow .= '<td class="empty-cell"></td>';
		$sumRow .= '<td class="empty-cell"></td>';
		$sumRow .= '<td>Summe</td>';
		$sumRow .= '<td>' . $swsSum . '</td>';
		$sumRow .= '</tr>';
		$rows[] = $sumRow;

		return implode('', $rows);
	}
}
