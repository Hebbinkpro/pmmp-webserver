<?php

namespace Hebbinkpro\WebServer\exception;

/**
 * Exception thrown when a file was not found in a FileRoute, StaticRoute or any other file response
 */
class FileNotFoundException extends WebServerException
{
    public function __construct(string $file)
    {
        parent::__construct("No file found at: '$file'.");
    }
}