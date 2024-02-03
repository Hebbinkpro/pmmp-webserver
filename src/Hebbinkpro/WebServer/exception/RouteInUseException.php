<?php

namespace Hebbinkpro\WebServer\exception;

/**
 * Exception thrown when a route path is already in use
 */
class RouteInUseException extends WebServerException
{
    public function __construct(string $route)
    {
        parent::__construct("The route '$route' is already in use.");
    }
}