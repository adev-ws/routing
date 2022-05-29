<?php

namespace adevws\Routing\Handlers;

use Closure;
use adevws\Routing\Event\EventArgument;
use adevws\Routing\Manager;

class DebugEventHandler implements IEventHandler
{

    /**
     * Debug callback
     * @var Closure
     */
    protected $callback;

    public function __construct()
    {
        $this->callback = static function (EventArgument $argument): void {
            // todo: log in database
        };
    }

    /**
     * Get events.
     *
     * @param string|null $name Filter events by name.
     * @return array
     */
    public function getEvents(?string $name): array
    {
        return [
            $name => [
                $this->callback,
            ],
        ];
    }

    /**
     * Fires any events registered with given event-name
     *
     * @param Manager $router Router instance
     * @param string $name Event name
     * @param array $eventArgs Event arguments
     */
    public function fireEvents(Manager $router, string $name, array $eventArgs = []): void
    {
        $callback = $this->callback;
        $callback(new EventArgument($name, $router, $eventArgs));
    }

    /**
     * Set debug callback
     *
     * @param Closure $event
     */
    public function setCallback(Closure $event): void
    {
        $this->callback = $event;
    }

}