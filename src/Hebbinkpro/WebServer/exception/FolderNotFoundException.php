<?php

namespace Hebbinkpro\WebServer\exception;

class FolderNotFoundException extends WebServerException
{
    public function __construct(string $folderPath)
    {
        parent::__construct("No folder found at: '$folderPath'.");
    }
}