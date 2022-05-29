<?php

namespace adevws\Routing\Event;

use InvalidArgumentException;
use adevws\Routing\Http\Request;
use adevws\Routing\Manager;

class EventArgument implements IEventArgument
{
    /**
     * Event name
     * @var string
     */
    protected $eventName;

    /**
     * @var Manager
     */
    protected $router;

    /**
     * @var array
     */
    protected $arguments = [];

    public function __construct(string $eventName, Manager $router, array $arguments = [])
    {
        $this->eventName = $eventName;
        $this->router = $router;
        $this->arguments = $arguments;
    }

    /**
     * Get event name
     *
     * @return string
     */
    public function getEventName(): string
    {
        return $this->eventName;
    }

    /**
     * Set the event name
     *
     * @param string $name
     */
    public function setEventName(string $name): void
    {
        $this->eventName = $name;
    }

    /**
     * Get the router instance
     *
     * @return Manager
     */
    public function getRouter(): Manager
    {
        return $this->router;
    }

    /**
     * Get the request instance
     *
     * @return Request
     */
    public function getRequest(): Request
    {
        return $this->getRouter()->getRequest();
    }

    /**
     * @param string $name
     * @return mixed
     */
    public function __get(string $name)
    {
        return $this->arguments[$name] ?? null;
    }

    /**
     * @param string $name
     * @return bool
     */
    public function __isset(string $name): bool
    {
        return array_key_exists($name, $this->arguments);
    }

    /**
     * @param string $name
     * @param mixed $value
     * @throws InvalidArgumentException
     */
    public function __set(string $name, $value): void
    {
        throw new InvalidArgumentException('Not supported');
    }

    /**
     * Get arguments
     *
     * @return array
     */
    public function getArguments(): array
    {
        return $this->arguments;
    }

}