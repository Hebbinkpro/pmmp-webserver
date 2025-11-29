# Getting Started

For creating a web server,
you have to register the WebServer and create a new instance of `WebServer` to start the server.

```php
<?php

use Hebbinkpro\WebServer\WebServer;
use Hebbinkpro\WebServer\http\server\HttpServerInfo;

class YourPlugin extends \pocketmine\plugin\PluginBase {
    
    protected function onEnable() : void{
        // ...
        
        $serverInfo = new HttpServerInfo("0.0.0.0", 80)
        // Create a new server on the address and port
        $webServer = new WebServer($this, $serverInfo);
        
        // after starting the server, the site will be available at http://127.0.0.1:80
        $webServer->start();
    }
}
```

_Note that if you want to access the server outside your localhost or local network, you may need to port-forward,
otherwise it will not be available for the outside world!_
