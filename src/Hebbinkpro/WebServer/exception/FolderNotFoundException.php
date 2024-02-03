<?php

namespace Hebbinkpro\WebServer\exception;

/**
 * Exception thrown when a folder is not found in a static Route
 */
class FolderNotFoundException extends WebServerException
{
    public function __construct(string $folderPath)
    {
        parent::__construct("No folder found at: '$folderPath'.");
    }
}