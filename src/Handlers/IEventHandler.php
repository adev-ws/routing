<?php

namespace adevws\Routing\Handlers;

use adevws\Routing\Manager;

interface IEventHandler
{

    /**
     * Get events.
     *
     * @param string|null $name Filter events by name.
     * @return array
     */
    public function getEvents(?string $name): array;

    /**
     * Fires any events registered with given event-name
     *
     * @param Manager $router Router instance
     * @param string $name Event name
     * @param array $eventArgs Event arguments
     */
    public function fireEvents(Manager $router, string $name, array $eventArgs = []): void;

}