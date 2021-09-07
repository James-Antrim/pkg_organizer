<?php
namespace Organizer\Controllers;

use Organizer\Controller;

/**
 * Class receives user actions and performs access checks and redirection.
 */
class Equipment extends Controller
{
    protected $listView = 'equipment';

    protected $resource = 'equipment';
}