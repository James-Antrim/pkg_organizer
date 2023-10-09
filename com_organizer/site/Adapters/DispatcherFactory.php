<?php
/**
 * @package     Groups
 * @extension   com_groups
 * @author      James Antrim, <james.antrim@nm.thm.de>
 * @copyright   2022 TH Mittelhessen
 * @license     GNU GPL v.2
 * @link        www.thm.de
 */

namespace THM\Organizer\Adapters;

use Joomla\CMS\Application\CMSApplicationInterface;
use Joomla\CMS\Dispatcher\ApiDispatcher;
use Joomla\CMS\Dispatcher\ComponentDispatcherFactoryInterface;
use Joomla\CMS\Dispatcher\DispatcherInterface;
use Joomla\CMS\MVC\Factory\MVCFactoryInterface;
use Joomla\Input\Input;

/**
 * Namespace based implementation of the ComponentDispatcherFactoryInterface
 */
class DispatcherFactory implements ComponentDispatcherFactoryInterface
{
    protected string $namespace;
    private MVCFactoryInterface $mvcFactory;

    /**
     * ComponentDispatcherFactory constructor.
     *
     * @param string              $namespace  The namespace
     * @param MVCFactoryInterface $mvcFactory The MVC factory
     */
    public function __construct(string $namespace, MVCFactoryInterface $mvcFactory)
    {
        $this->namespace  = $namespace;
        $this->mvcFactory = $mvcFactory;
    }

    /**
     * @inheritDoc
     */
    public function createDispatcher(CMSApplicationInterface $application, Input $input = null): DispatcherInterface
    {
        if ($application->isClient('api')) {
            $className = ApiDispatcher::class;
        } else {
            $className = Dispatcher::class;
        }

        return new $className($application, $input ?: $application->input, $this->mvcFactory);
    }
}
