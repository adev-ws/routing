<?php

namespace adevws\Routing\Event;

use adevws\Routing\Http\Request;
use adevws\Routing\Manager;

interface IEventArgument
{

    /**
     * Get event name
     *
     * @return string
     */
    public function getEventName(): string;

    /**
     * Set event name
     *
     * @param string $name
     */
    public function setEventName(string $name): void;

    /**
     * Get router instance
     *
     * @return Manager
     */
    public function getRouter(): Manager;

    /**
     * Get request instance
     *
     * @return Request
     */
    public function getRequest(): Request;

    /**
     * Get all event arguments
     *
     * @return array
     */
    public function getArguments(): array;

}