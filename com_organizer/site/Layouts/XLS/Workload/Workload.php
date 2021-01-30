<?php
/**
 * @package     Organizer\Layouts\XLS\Rooms
 * @extension   Organizer
 * @author      James Antrim, <james.antrim@nm.thm.de>
 * @copyright   2020 TH Mittelhessen
 * @license     GNU GPL v.3
 * @link        www.thm.de
 */

namespace Organizer\Layouts\XLS\Workload;

use Exception;
use Organizer\Helpers;
use Organizer\Helpers\Languages;
use Organizer\Layouts\XLS\BaseLayout;
use PHPExcel_Worksheet_Drawing;
use XLC;

/**
 * Class generates the room statistics XLS file.
 */
class Workload extends BaseLayout
{
	/**
	 * @var \array[][] Border definitions
	 */
	private $borders = [
		'data'   => [
			'left'   => [
				'style' => XLC::MEDIUM
			],
			'right'  => [
				'style' => XLC::MEDIUM
			],
			'bottom' => [
				'style' => XLC::THIN
			],
			'top'    => [
				'style' => XLC::THIN
			]
		],
		'cell'   => [
			'left'   => [
				'style' => XLC::THIN
			],
			'right'  => [
				'style' => XLC::THIN
			],
			'bottom' => [
				'style' => XLC::THIN
			],
			'top'    => [
				'style' => XLC::THIN
			]
		],
		'header' => [
			'left'   => [
				'style' => XLC::MEDIUM
			],
			'right'  => [
				'style' => XLC::MEDIUM
			],
			'bottom' => [
				'style' => XLC::MEDIUM
			],
			'top'    => [
				'style' => XLC::MEDIUM
			]
		]
	];

	/**
	 * @var array[] Fill definitions
	 */
	private $fills = [
		'header' => [
			'type'  => XLC::SOLID,
			'color' => ['rgb' => '80BA24']
		],
		'index'  => [
			'type'  => XLC::SOLID,
			'color' => ['rgb' => 'FFFF00']
		],
		'data'   => [
			'type'  => XLC::SOLID,
			'color' => ['rgb' => 'DFEEC8']
		]
	];

	/**
	 * @var string[] Height definitions
	 */
	private $heights = [
		'basicField'    => '18.75',
		'sectionHead'   => '13.5',
		'sectionSpacer' => '8.25',
		'spacer'        => '6.25',
		'sum'           => '18.75'
	];

	/**
	 * Adds an instruction cell to the active sheet.
	 *
	 * @param   int     $row     the row number
	 * @param   float   $height  the row height
	 * @param   string  $text    the cell text
	 * @param   bool    $bold    whether the text should be displayed in a bold font
	 *
	 * @return void
	 * @throws Exception
	 */
	private function addInstruction(int $row, float $height, string $text, $bold = false)
	{
		$activeSheet = $this->view->getActiveSheet();
		$coords      = 'B' . $row;
		$activeSheet->getRowDimension($row)->setRowHeight($height);
		$activeSheet->setCellValue($coords, $text);
		$cellStyle = $activeSheet->getStyle($coords);
		$cellStyle->getAlignment()->setWrapText(true);

		if ($bold)
		{
			$cellStyle->getFont()->setBold(true);
		}

		$cellStyle->getAlignment()->setVertical(XLC::TOP);
		$activeSheet->getStyle($coords)->getFont()->setSize('14');
	}

	/**
	 * Creates an instructions sheet
	 *
	 * @return void
	 * @throws Exception
	 */
	private function addInstructionSheet()
	{
		$view = $this->view;
		$view->setActiveSheetIndex();
		$activeSheet = $view->getActiveSheet();
		$pageSetup   = $activeSheet->getPageSetup();
		$pageSetup->setOrientation(XLC::PORTRAIT);
		$pageSetup->setPaperSize(XLC::A4);
		$pageSetup->setFitToPage(true);

		$activeSheet->setTitle('Anleitung');
		$activeSheet->setShowGridlines(false);
		$activeSheet->getColumnDimension()->setWidth(5);
		$activeSheet->getColumnDimension('B')->setWidth(75);
		$activeSheet->getColumnDimension('C')->setWidth(5);
		$activeSheet->getRowDimension('1')->setRowHeight('85');

		$this->addLogo('B1', 60, 25);

		$text = 'Mit dem ablaufenden Wintersemester 2017/18 wird ein leicht veränderter B-Bogen in Umlauf ';
		$text .= 'gesetzt. Er dient einer dezi\ndieteren Kostenrechnung. Bitte nutzen Sie ausschließlich diesen ';
		$text .= 'Bogen.';
		$this->addInstruction(2, 90, $text);

		$this->addInstruction(3, 35, 'Hinweise:', true);

		$text = 'In der Spalte "Studiengang" ist eine Auswahlliste für Ihren Fachbereich hinterlegt. ';
		$text .= 'Bitte klicken Sie den entsprechenden Studiengang an.';
		$this->addInstruction(4, 55, $text);

		$text = 'Sollten Sie in der Auswahlliste einen Studiengang nicht finden, so nutzen Sie bitte die ';
		$text .= 'letzte Rubrik "nicht vorgegeben". ';
		$this->addInstruction(5, 55, $text);

		$text = 'Sollte eine Lehrveranstaltung in mehreren Studiengängen sein, so können Sie, dann über ';
		$text .= 'mehrere Zeilen, nach Ihrem Ermessen quoteln.';
		$this->addInstruction(6, 55, $text);

		$this->addInstruction(7, 45, 'So können alle Studiengänge berücksichtigt werden. ');

		$text = 'Sollten Sie eine Lehrveranstaltung gehalten haben, die in mehreren Fachbereichen ';
		$text .= 'angeboten wird, so verfahren Sie bitte analog, nutzen aber die Rubrik "mehrere ';
		$text .= 'Fachbereiche", da dort eine  Auswahlliste hinterlegt ist, die alle Studiengänge ';
		$text .= 'der THM enthält.';
		$this->addInstruction(8, 90, $text);

		$this->addInstruction(9, 20, 'Die Liste ist nach Fachbereichen geordnet.');
		$activeSheet->getRowDimension('10')->setRowHeight('20');
		$this->addInstruction(11, 20, 'Für Ihre Mühe danke ich Ihnen,');
		$this->addInstruction(12, 20, 'Prof. Olaf Berger');

		$noOutline = ['borders' => ['outline' => ['style' => XLC::NONE]]];
		$activeSheet->getStyle('A1:C12')->applyFromArray($noOutline);
	}

	/**
	 * Adds the THM Logo to a cell.
	 *
	 * @param   string  $cell     the cell coordinates
	 * @param   int     $height   the display height of the logo
	 * @param   int     $offsetY  the offset from the top of the cell
	 *
	 * @return void
	 * @throws Exception
	 */
	private function addLogo(string $cell, int $height, int $offsetY)
	{
		$objDrawing = new PHPExcel_Worksheet_Drawing();
		$objDrawing->setName('THM Logo');
		$objDrawing->setDescription('THM Logo');
		$objDrawing->setPath(JPATH_COMPONENT_SITE . '/images/logo.png');
		$objDrawing->setCoordinates($cell);
		$objDrawing->setHeight($height);
		$objDrawing->setOffsetY($offsetY);
		$activeSheet = $this->view->getActiveSheet();
		$objDrawing->setWorksheet($activeSheet);
	}

	/**
	 * @inheritDoc
	 */
	public function fill()
	{
		$this->view->getDefaultStyle()->getFont()->setName('Arial')->setSize(10);
		$this->addInstructionSheet();
		// TODO: Implement fill() method.
	}

	/**
	 * @inheritDoc
	 */
	public function getDescription(): string
	{
		$person = Helpers\Persons::getDefaultName(Helpers\Input::getInt('personID'));
		$term   = Helpers\Terms::getFullName(Helpers\Input::getInt('termID'));
		$date   = Helpers\Dates::formatDate(date('Y-m-d'));

		return Languages::sprintf('ORGANIZER_WORKLOAD_XLS_DESCRIPTION', $person, $term, $date);
	}

	/**
	 * @inheritDoc
	 */
	public function getTitle(): string
	{
		$person = Helpers\Persons::getLNFName(Helpers\Input::getInt('personID'));
		$term   = Helpers\Terms::getName(Helpers\Input::getInt('termID'));

		return Languages::_('ORGANIZER_WORKLOAD') . ": $person - $term";
	}
}
