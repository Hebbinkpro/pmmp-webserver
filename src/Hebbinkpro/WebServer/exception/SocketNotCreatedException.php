<?php

namespace Hebbinkpro\WebServer\exception;

/**
 * Exception thrown when there went something wrong while creating the server socket
 */
class SocketNotCreatedException extends WebServerException
{
    public function __construct(string $reason)
    {
        parent::__construct("The server socket is not created. Reason: $reason");
    }
}