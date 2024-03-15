<?php

namespace Hebbinkpro\WebServer\exception;

use Hebbinkpro\WebServer\http\HttpMethod;

class RouteExistsException extends WebServerException
{
    public function __construct(string $path, HttpMethod $method)
    {
        parent::__construct("There already exists a $method->value route for path '$path'.");
    }
}