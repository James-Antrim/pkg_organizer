<?php
/**
 * @package     Organizer\Layouts\XLS
 * @extension   Organizer
 * @author      James Antrim, <james.antrim@nm.thm.de>
 * @copyright   2020 TH Mittelhessen
 * @license     GNU GPL v.3
 * @link        www.thm.de
 */

namespace Organizer\Layouts\XLS;


use Organizer\Views\XLS\BaseView;

abstract class BaseLayout
{
	/**
	 * @var BaseView
	 */
	protected $view;

	public function __construct(BaseView $view)
	{
		$this->view = $view;
	}

	/**
	 * Gets the description for the layout.
	 *
	 * @return string
	 */
	abstract public function fill();

	/**
	 * Gets the description for the layout.
	 *
	 * @return string
	 */
	abstract public function getDescription();

	/**
	 * Gets the description for the layout.
	 *
	 * @return string
	 */
	abstract public function getTitle();
}