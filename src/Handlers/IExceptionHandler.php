<?php

namespace adevws\Routing\Handlers;

use Exception;
use adevws\Routing\Http\Request;

interface IExceptionHandler
{
    /**
     * @param Request $request
     * @param Exception $error
     */
    public function handleError(Request $request, Exception $error): void;

}