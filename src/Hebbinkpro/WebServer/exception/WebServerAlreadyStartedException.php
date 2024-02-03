<?php

namespace Hebbinkpro\WebServer\exception;

/**
 * Exception thrown when someone tries to start the server when it is already running.
 */
class WebServerAlreadyStartedException extends WebServerException
{
    public function __construct()
    {
        parent::__construct("The web server is already started.");
    }
}