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

use Joomla\CMS\{Table\Menu, Uri\Uri};
use Joomla\Database\DatabaseQuery;
use stdClass;
use THM\Organizer\Adapters\{Application, Input};
use THM\Organizer\Helpers\{Can, Instances, Terms};

/** @inheritDoc */
class InstanceItem extends ListModel
{
    public array $conditions = [];
    protected int $defaultLimit = 0;
    public stdClass $instance;
    public string $referrer;

    /** @inheritDoc */
    public function __construct($config = [])
    {
        parent::__construct($config);

        $instanceID = Input::id();
        $instance   = Instances::instance($instanceID);

        $endDate    = Terms::endDate($instance['termID']);
        $tStartDate = Terms::startDate($instance['termID']);
        $today      = date('Y-m-d');
        $startDate  = $tStartDate > $today ? $tStartDate : $today;

        $this->conditions = [
            'delta'           => date('Y-m-d 00:00:00', strtotime('-14 days')),
            'endDate'         => $endDate,
            'eventIDs'        => [$instance['eventID']],
            'showUnpublished' => Can::manage('instance', $instanceID),
            'startDate'       => $startDate,
            'status'          => self::CURRENT
        ];

        Instances::fill($instance, $this->conditions);
        $this->instance = (object) $instance;
        $this->setReferrer();
    }

    /** @inheritDoc */
    public function getItems(): array
    {
        $items = parent::getItems();

        foreach ($items as $key => $instance) {
            $instance = Instances::instance($instance->id);
            Instances::fill($instance, $this->conditions);
            $items[$key] = (object) $instance;
        }

        return $items;
    }

    /** @inheritDoc */
    protected function getListQuery(): DatabaseQuery
    {
        $endDate   = $this->conditions['endDate'];
        $endTime   = date('H:i:s');
        $query     = Instances::getInstanceQuery($this->conditions);
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
    private function setReferrer(): void
    {
        $session = Application::session();

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
            $menu = new Menu(Application::database());

            if ($menu->load(['path' => $path])) {
                // Typically index.php?key=value...
                $queryString = explode('?', $menu->link)[1];
                parse_str($queryString, $query);

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