<?php
/**
 * @package     Organizer
 * @extension   com_organizer
 * @author      James Antrim, <james.antrim@nm.thm.de>
 * @copyright   2020 TH Mittelhessen
 * @license     GNU GPL v.3
 * @link        www.thm.de
 */

namespace THM\Organizer\Models;

use JDatabaseQuery;
use Joomla\CMS\Factory;
use Joomla\CMS\Table\Menu;
use Joomla\CMS\Uri\Uri;
use THM\Organizer\Adapters\Input;
use THM\Organizer\Helpers;
use stdClass;

/**
 * Class retrieves information for an instance and related instances.
 */
class InstanceItem extends ListModel
{
    public array $conditions = [];
    protected $defaultLimit = 0;
    public stdClass $instance;
    public string $referrer;

    /** @inheritDoc */
    public function __construct($config = [])
    {
        parent::__construct($config);

        $instanceID = Input::id();
        $instance   = Helpers\Instances::instance($instanceID);

        $endDate    = Helpers\Terms::endDate($instance['termID']);
        $tStartDate = Helpers\Terms::startDate($instance['termID']);
        $today      = date('Y-m-d');
        $startDate  = $tStartDate > $today ? $tStartDate : $today;

        $this->conditions = [
            'delta'           => date('Y-m-d 00:00:00', strtotime('-14 days')),
            'endDate'         => $endDate,
            'eventIDs'        => [$instance['eventID']],
            'showUnpublished' => Helpers\Can::manage('instance', $instanceID),
            'startDate'       => $startDate,
            'status'          => self::CURRENT
        ];

        Helpers\Instances::fill($instance, $this->conditions);
        $this->instance = (object) $instance;
        $this->setReferrer();
    }

    /** @inheritDoc */
    public function getItems(): array
    {
        $items = parent::getItems();

        foreach ($items as $key => $instance) {
            $instance = Helpers\Instances::instance($instance->id);
            Helpers\Instances::fill($instance, $this->conditions);
            $items[$key] = (object) $instance;
        }

        return $items;
    }

    /** @inheritDoc */
    protected function getListQuery(): JDatabaseQuery
    {
        $endDate   = $this->conditions['endDate'];
        $endTime   = date('H:i:s');
        $query     = Helpers\Instances::getInstanceQuery($this->conditions);
        $startDate = $this->conditions['startDate'];

        $query->select("DISTINCT i.id")
            ->where("(b.date > '$startDate' OR (b.date = '$startDate' AND b.endTime >= '$endTime'))")
            ->where("b.date <= '$endDate'")
            ->order('b.date, b.startTime, b.endTime');

        return $query;
    }

    /**
     * Sets the referrer to the item view in order to return to the list/schedule view from which it was called.
     * @return void
     */
    private function setReferrer()
    {
        $session = Factory::getSession();

        if (!$this->referrer = $session->get('organizer.instance.item.referrer', '')) {
            $root     = Uri::root();
            $referrer = Uri::getInstance(Input::instance()->server->getString('HTTP_REFERER'));

            // Site external => irrelevant
            if (!str_starts_with((string) $referrer, $root)) {
                return;
            }

            // Not SEF
            if ($option = $referrer->getVar('option', '')) {
                $view = $referrer->getVar('view', '');

                // Component external => irrelevant, no view => nowhere to go back to
                if ($option !== 'com_organizer' or !$view) {
                    return;
                }

                if (strtolower($view) !== 'instances') {
                    return;
                }

                $this->referrer = (string) $referrer;
                $session->set('organizer.instance.item.referrer', $this->referrer);

                return;
            }

            $theRest = str_replace($root, '', (string) $referrer);

            // The query will only interfere with resolution
            $path = str_contains($theRest, '?') ? $theRest : explode('?', $theRest)[0];

            // Joomla doesn't store the format in the path variable
            $path = str_replace('.html', '', $path);

            // Joomla doesn't store the language tag in the path variable
            if (str_starts_with($path, 'en/')) {
                $path = substr_replace($path, '', 0, 3);
            }

            // Menu item?
            $menu = new Menu(Factory::getDbo());

            if ($menu->load(['path' => $path])) {
                // Typically index.php?key=value...
                /** @noinspection PhpUndefinedFieldInspection */
                $query = explode('?', $menu->link)[1];
                parse_str($query, $query);

                $option = (!empty($query['option']) and $query['option'] === 'com_organizer');
                $view   = (!empty($query['view']) and $query['view'] === 'instances');

                if ($option and $view) {
                    $this->referrer = (string) $referrer;
                    $session->set('organizer.instance.item.referrer', $this->referrer);
                }
            }
        }
    }
}