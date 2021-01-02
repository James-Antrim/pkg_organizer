<?php
/**
 * @package     Organizer
 * @extension   com_organizer
 * @author      James Antrim, <james.antrim@nm.thm.de>
 * @copyright   2020 TH Mittelhessen
 * @license     GNU GPL v.3
 * @link        www.thm.de
 */

/** @noinspection PhpTooManyParametersInspection */

namespace Organizer\Views\PDF;

use Joomla\Registry\Registry;

/**
 * Base class for a Joomla View
 *
 * Class holding methods for displaying presentation data.
 */
abstract class ListView extends BaseView
{
	/**
	 * TCPDF has it's own 'state' property. This is the state from the submitted form.
	 * @var Registry
	 */
	protected $formState;

	/**
	 * Performs initial construction of the TCPDF Object.
	 *
	 * @param   string  $orientation  page orientation
	 * @param   string  $unit         unit of measure
	 * @param   mixed   $format       page format; possible values: string - common format name, array - parameters
	 *
	 * @see \TCPDF_STATIC::getPageSizeFromFormat(), setPageFormat()
	 */
	public function __construct($orientation = self::PORTRAIT, $unit = 'mm', $format = 'A4')
	{
		parent::__construct($orientation, $unit, $format);
		$this->formState = $this->get('state');
	}

	/**
	 * @inheritdoc
	 */
	public function display()
	{
		$this->layout->setTitle();
		$this->layout->fill($this->get('items'));

		parent::display();
	}

	/**
	 * Set header items and footer colors.
	 *
	 * @return void
	 */
	abstract public function setOverhead();
}
