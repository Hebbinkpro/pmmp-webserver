<?php

namespace Hebbinkpro\WebServer\exception;

class FileNotFoundException extends WebServerException
{
    public function __construct(string $file)
    {
        parent::__construct("No file found at: '$file'.");
    }
}