<?php

namespace adevws\Routing;

use adevws\Routing\Http\Request;

interface IRouterBootManager
{
    /**
     * Called when router loads it's routes
     *
     * @param Manager $router
     * @param Request $request
     */
    public function boot(Manager $router, Request $request): void;
}