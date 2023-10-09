<?php
/**
 * @package     Organizer
 * @extension   com_organizer
 * @author      James Antrim, <james.antrim@nm.thm.de>
 * @copyright   2020 TH Mittelhessen
 * @license     GNU GPL v.3
 * @link        www.thm.de
 */

namespace Organizer\Tables;

/**
 * Resources which can be reached over a URL are addressable.
 */
trait Coded
{
    /**
     * An abbreviated nomenclature for the resource. Currently corresponding to the identifier in Untis scheduling
     * software with the exception of units which are also supplemented locally.
     * VARCHAR(60) DEFAULT NULL
     * @var string
     */
    public $code;
}