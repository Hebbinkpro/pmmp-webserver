<?php

namespace Hebbinkpro\WebServer\task;

use Hebbinkpro\WebServer\http\HttpRequest;
use Hebbinkpro\WebServer\WebClient;
use pocketmine\scheduler\AsyncTask;

class AsyncClientSocketListenTask extends AsyncTask
{

    private mixed $socket;

    public function __construct(WebClient $client, mixed $socket)
    {
        $this->storeLocal("client", $client);
        $this->socket = $socket;
    }

    public function onRun(): void
    {
        // wait until there is new data received
        while (!feof($this->socket) && ($data = fread($this->socket, 8192)) !== false) {
            // publish the data to the main thread to handle it
            $this->publishProgress(["data" => $data]);
        }

        // end of file, nothing to be read anymore
    }

    public function onProgressUpdate($progress): void
    {
        /** @var WebClient $client */
        $client = $this->fetchLocal("client");
        /** @var string $data */
        $data = $progress["data"];

        // get the request from the data
        $req = HttpRequest::fromString($data);
        // invalid (non http) request
        if ($req === null) return;

        // handle the request
        $client->handleRequest($req);
    }
}