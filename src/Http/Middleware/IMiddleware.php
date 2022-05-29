<?php

namespace adevws\Routing\Http\Middleware;

use adevws\Routing\Http\Request;

interface IMiddleware
{
    /**
     * @param Request $request
     */
    public function handle(Request $request): void;

}