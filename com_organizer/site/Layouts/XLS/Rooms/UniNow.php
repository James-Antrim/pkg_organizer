<?php
/**
 * @package     Organizer\Layouts\XLS\Rooms
 * @extension   Organizer
 * @author      James Antrim, <james.antrim@nm.thm.de>
 * @copyright   2020 TH Mittelhessen
 * @license     GNU GPL v.3
 * @link        www.thm.de
 */

namespace Organizer\Layouts\XLS\Rooms;

use Exception;
use Organizer\Layouts\XLS\BaseLayout;
use PHPExcel_Style_Border as BorderStyle;

class UniNow extends BaseLayout
{
	/**
	 * Adds the column headers to the document.
	 *
	 * @return void
	 */
	private function addColumnHeaders()
	{
		$sheet = $this->view->getActiveSheet();

		$sheet->getColumnDimension('A')->setWidth(35.43);

		$sheet->setCellValue('A1', 'Building ID');
		/**
		 * ======
		 * ID#AAAAKmhoypk
		 * Importierter Autor    (2020-10-30 12:54:24)
		 * Building ID
		 * Leave blank for new building creation The ID of a building. If not specified, we will try and match the building ID by the building external id or building name, and if we cannot find the building, we will create a new building with the specified name and external id.
		 *
		 * format: internal uninow id
		 *
		 * required: required for edits
		 * ----
		 * Name streichen
		 * -Stefan Wegener
		 * i18n
		 * -Stefan Wegener
		 */
		$sheet->setCellValue('B1', 'Building External ID');
		/**
		 * ======
		 * ID#AAAAKmhoyqQ
		 * Importierter Autor    (2020-10-30 12:54:24)
		 * Building External ID
		 * External ID to identify the building in other services. If specified, we will try to find the building id using this, and if we cannot find an existing building with the external id specified, we will create a new building.
		 *
		 * format: text
		 * required: optional
		 * example: B1234
		 */
		$sheet->setCellValue('C1', 'Building Name');
		/**
		 * ======
		 * ID#AAAAKmhoypo
		 * Importierter Autor    (2020-10-30 12:54:24)
		 * Building Name
		 *
		 * The name of the building. If specified, we will try to find the building id using this, and if we cannot find an existing building with the name specified, we will create a new building.
		 *
		 * format: text
		 * required: true
		 * example: Hauptsitz
		 */
		$sheet->setCellValue('D1', 'Building Status');
		/**
		 * ======
		 * ID#AAAAKmhoyp8
		 * Importierter Autor    (2020-10-30 12:54:24)
		 * Building Status
		 *
		 * The current status of a building. If the status is DRAFT, the building is not yet visible.
		 *
		 * format: ACTIVE, DRAFT, DELETED
		 * required: recommended
		 * example: ACTIVE
		 */
		$sheet->setCellValue('E1', 'Building Description');
		/**
		 * ======
		 * ID#AAAAKmhoyp0
		 * Importierter Autor    (2020-10-30 12:54:24)
		 * Building Description
		 * The description is additional text used to describe the building.
		 *
		 * format: text
		 * required: optional
		 * example: Hauptsitz der UniNow GmbH in Magdeburg
		 */
		$sheet->setCellValue('F1', 'Building Geo Coordinates');
		/**
		 * ======
		 * ID#AAAAKmhoypg
		 * Importierter Autor    (2020-10-30 12:54:24)
		 * Building Geo Coordinates
		 * The geo coordinates describe the location of a building.
		 *
		 * format: latitude,longitude
		 * required: optional
		 * example: 52.1079129,11.6349061
		 */
		$sheet->setCellValue('G1', 'Building Address');
		/**
		 * ======
		 * ID#AAAAKmhoyqA
		 * Importierter Autor    (2020-10-30 12:54:24)
		 * Building Address
		 * The address describe the location of a building.
		 *
		 * format: street number, postalcode, city, country
		 * required: optional
		 * example: Dorotheenstraße 10, 39104 Magdeburg, Deutschland
		 */
		$sheet->setCellValue('H1', 'Tracking Code');
		/**
		 * ======
		 * ID#AAAAKmhoypI
		 * Importierter Autor    (2020-10-30 12:54:24)
		 * Tracking Code
		 * This is the public identifier to check in or check out from the room.
		 *
		 * format: XXX-XX-XX
		 * read only
		 */
		$sheet->setCellValue('I1', 'Room ID');
		/**
		 * ======
		 * ID#AAAAKmhoypw
		 * Importierter Autor    (2020-10-30 12:54:24)
		 * Room ID
		 * Leave blank for new room creation The ID of a room. If not specified, we will try and match the room ID by the room external id or the room external name, and if we cannot find the room, we will create a new room with the specified name and external id.
		 *
		 * format: internal uninow id
		 * required: required for edits
		 */
		$sheet->setCellValue('J1', 'Room External ID');
		/**
		 * ======
		 * ID#AAAAKmhoyqI
		 * Importierter Autor    (2020-10-30 12:54:24)
		 * Room External ID
		 * External ID to identify the room in other services. If specified, we will try to find the room id using this, and if we cannot find an existing room with the external id specified, we will create a new room.
		 *
		 * format: text
		 * required: optional
		 * example: R1234
		 */
		$sheet->setCellValue('K1', 'Room Name');
		/**
		 * ======
		 * ID#AAAAKmhoyqU
		 * Importierter Autor    (2020-10-30 12:54:24)
		 * Room Name
		 *
		 * The name of the room. If specified, we will try to find the room id using this, and if we cannot find an existing room with the name specified, we will create a new room.
		 *
		 * format: text
		 * required: true
		 * example: Besprechungsraum
		 */
		$sheet->setCellValue('L1', 'Room Status');
		/**
		 * ======
		 * ID#AAAAKmhoypY
		 * Importierter Autor    (2020-10-30 12:54:24)
		 * Room Status
		 *
		 * The current status of an room. If you are editing the status of your room, make sure your building status correlates with your room status. For example, if an building status is set to "DELETED" but the room status is set to "ACTIVE" this can cause errors. Make sure both statuses correspond. If the status is DRAFT, the room is not yet visible.
		 *
		 * format: ACTIVE, DRAFT,DELETED
		 * required: recommended
		 * example: ACTIVE
		 */
		$sheet->setCellValue('M1', 'Room Description');
		/**
		 * ======
		 * ID#AAAAKmhoypA
		 * Importierter Autor    (2020-10-30 12:54:24)
		 * Room Description
		 * The description is additional text used to describe the room.
		 *
		 * format: text
		 * required: optional
		 * example: Großer Besprechungsraum mit Tisch und Bestuhlung
		 */
		$sheet->setCellValue('N1', 'Room Floor');
		/**
		 * ======
		 * ID#AAAAKmhoyp4
		 * Importierter Autor    (2020-10-30 12:54:24)
		 * Room Floor
		 * The floor is additional text used to describe the location of room in the building.
		 *
		 * format: text
		 * required: optional
		 * example: Erdgeschoss
		 */
		$sheet->setCellValue('O1', 'Room Capacity');
		/**
		 * ======
		 * ID#AAAAKmhoypc
		 * Importierter Autor    (2020-10-30 12:54:24)
		 * Room Capacity
		 * The capacity is additional number used to limit the number of people who are allowed to enter the room.
		 *
		 * format: positive integer
		 * required: optional
		 * example: 10
		 */
		$sheet->setCellValue('P1', 'Seats');
		/**
		 * ======
		 * ID#AAAAKmhoyqM
		 * Importierter Autor    (2020-10-30 12:54:24)
		 * Seats
		 * The number of seats in a room.
		 *
		 * format: positive integer
		 * required: optional
		 * example: 10
		 */
		$sheet->setCellValue('Q1', 'Tracking Code');
		/**
		 * ======
		 * ID#AAAAKmhoypU
		 * Importierter Autor    (2020-10-30 12:54:24)
		 * Tracking Code
		 * This is the public identifier to check in or check out from the room.
		 *
		 * format: XXX-XX-XX
		 * read only
		 */
		$sheet->setCellValue('R1', 'Capacity is Limit');
		/**
		 * ======
		 * ID#AAAAKmhoyqE
		 * Importierter Autor    (2020-10-30 12:54:24)
		 * Capacity is Limit
		 *
		 * If this value is true, the capacity is the upper limit for active check ins and overbooking is not possible.
		 * If set to false overbooking is possible.
		 *
		 * format: TRUE, FALSE
		 * required: required
		 * example: TRUE
		 */
		$sheet->setCellValue('S1', 'Checkout Reminder');
		/**
		 * ======
		 * ID#AAAAKmhoyps
		 * Importierter Autor    (2020-10-30 12:54:24)
		 * Checkout Reminder
		 *
		 * If set to TRUE, we send a push notification to all users with an open check in after a configurable time
		 * (Threshold Checkout Reminder).
		 *
		 * format: TRUE, FALSE
		 * required: required
		 * example: TRUE
		 */
		$sheet->setCellValue('T1', 'Auto Checkout');
		/**
		 * ======
		 * ID#AAAAKmhoypE
		 * Importierter Autor    (2020-10-30 12:54:24)
		 * Auto Checkout
		 *
		 * If set to TRUE, we perform a check out on each active check in after a configurable time
		 * (Threshold Auto Checkout).
		 *
		 * format: TRUE, FALSE
		 * required: required
		 * example: TRUE
		 */
		$sheet->setCellValue('U1', 'Threshold Checkout Reminder');
		/**
		 * ======
		 * ID#AAAAKmhoypM
		 * Importierter Autor    (2020-10-30 12:54:24)
		 * Threshold Checkout Reminder
		 *
		 * If the field "Checkout Reminder" is set to TRUE, we send a push notification to all active check ins after this value in minutes.
		 *
		 *
		 * format: positive integer range (60,90,120,150,180,210,240)
		 * required: optional
		 * example: 120
		 */
		$sheet->setCellValue('V1', 'Threshold Auto Checkout');
		/**
		 * ======
		 * ID#AAAAKmhoypQ
		 * Importierter Autor    (2020-10-30 12:54:24)
		 * Threshold Auto Checkout
		 *
		 * If the field "Auto Checkout" is set to TRUE, we perform a check out on all active check ins after this value in minutes.
		 *
		 *
		 * format: positive integer range (60,90,120,150,180,210,240)
		 * required: optional
		 * example: 120
		 */
	}

	private function addRows()
	{
		// The first row is used by the header
		$index = 2;
		$sheet = $this->view->getActiveSheet();

		foreach ($this->view->model->getItems() as $room)
		{
			for ($column = 'A'; $column < 'W'; $column++)
			{
				$coordinates = "$column$index";
				$value       = '';

				switch ($column)
				{
					// Generated externally
					case 'A':
					case 'H':
					case 'I':
					case 'Q':
						// Red
						break;

					case 'B':
						$value = $room->buildingName ?: '-';
						break;
					case 'C':
						$value = $room->buildingName ?: 'Unbekannt';
						break;
					case 'D':
					case 'L':
						$value = 'ACTIVE';
						break;
					case 'E':
						if ($room->campus)
						{
							$value = $room->parent ? "$room->parent / $room->campus" : $room->campus;
						}
						else
						{
							$value = '';
						}
						break;
					case 'F':
						$value = $room->location ?: '';
						break;
					case 'G':
						$value = $room->address ?: '';
						break;
					case 'J':
						$value = $room->code;
						break;
					case 'K':
						$value = $room->roomName;
						break;
					case 'M':
						$value = $room->roomType ? $room->roomType : '';
						break;
					case 'N':
						if (preg_match("/^[A-Z]{1}\d+.(U?\d+).\d+[A-Za-z]?$/", $room->roomName, $matches))
						{
							$symbols = str_split($matches[1]);
							if ($symbols[0] === '0')
							{
								$value = 'Erdgeschoss';
							}
							elseif ($symbols[0] === 'U')
							{
								$level = implode('', array_splice($symbols, 1));
								$value = "$level. Untergeschoss";
							}
							else
							{
								$level = implode('', $symbols);
								$value = "$level. Etage";
							}
						}
						else
						{
							$value = '';
						}

						break;
					case 'O':
						// Capacity - corona factoring?
						$value = $room->capacity ?: '';
						break;
					case 'P':
						// Capacity - absolute
						break;
					case 'R':
						// Hard limit to capacity
						break;
					case 'S':
					case 'T':
						$value = 'TRUE';
						break;
					case 'U':
						$value = 120;
						break;
					case 'V':
						$value = 240;
						break;
				}

				$sheet->setCellValue($coordinates, $value);
			}
			$index++;
		}
	}

	/**
	 * @inheritDoc
	 */
	public function fill()
	{
		$this->setPageFormatting();
		$this->addColumnHeaders();
		$this->addRows();
	}

	/**
	 * @inheritDoc
	 */
	public function getDescription(): string
	{
		return 'An export of the current room inventory of the Organizer component, suitable for import in the UniNow system.';
	}

	/**
	 * @inheritDoc
	 */
	public function getTitle(): string
	{
		return 'UniNow Room Export';
	}

	/**
	 * Sets the formatting for the page.
	 *
	 * @return void modifies the page formatting
	 * @throws Exception
	 */
	private function setPageFormatting()
	{
		$sheet = $this->view->getActiveSheet();

		// TODO: setting the default style is not working right now.
		$sheet->getDefaultStyle()->applyFromArray(['borders' => ['outline' => ['style' => BorderStyle::BORDER_NONE]]]);

		$sheet->getColumnDimension('A')->setWidth(35.43);
		$sheet->getColumnDimension('B')->setWidth(18.14);
		$sheet->getColumnDimension('C')->setWidth(24);
		$sheet->getColumnDimension('D')->setWidth(14);
		$sheet->getColumnDimension('E')->setWidth(36.14);
		$sheet->getColumnDimension('F')->setWidth(23.29);
		$sheet->getColumnDimension('G')->setWidth(44.43);
		$sheet->getColumnDimension('H')->setWidth(44.43);
		$sheet->getColumnDimension('I')->setWidth(12);
		$sheet->getColumnDimension('J')->setWidth(18.29);
		$sheet->getColumnDimension('K')->setWidth(22.43);
		$sheet->getColumnDimension('L')->setWidth(13.71);
		$sheet->getColumnDimension('M')->setWidth(44.29);
		$sheet->getColumnDimension('N')->setWidth(14.43);
		$sheet->getColumnDimension('O')->setWidth(14);
		$sheet->getColumnDimension('P')->setWidth(14);
		$sheet->getColumnDimension('Q')->setWidth(14);
		$sheet->getColumnDimension('R')->setWidth(18.71);
		$sheet->getColumnDimension('S')->setWidth(23);
		$sheet->getColumnDimension('T')->setWidth(19.71);
		$sheet->getColumnDimension('U')->setWidth(30.71);
		$sheet->getColumnDimension('V')->setWidth(24.43);
	}
}