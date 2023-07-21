<?php

namespace Hebbinkpro\WebServer\exception;

use pocketmine\plugin\PluginBase;

class WebServerAlreadyStartedException extends WebServerException
{
    public function __construct()
    {
        parent::__construct("The web server is already started.");
    }
}