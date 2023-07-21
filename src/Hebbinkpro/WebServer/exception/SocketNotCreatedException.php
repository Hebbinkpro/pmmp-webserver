<?php

namespace Hebbinkpro\WebServer\exception;

class SocketNotCreatedException extends WebServerException
{
    public function __construct(string $reason)
    {
        parent::__construct("The server socket is not created. Reason: $reason");
    }
}