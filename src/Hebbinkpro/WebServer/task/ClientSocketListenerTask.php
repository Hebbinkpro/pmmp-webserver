<?php

namespace Hebbinkpro\WebServer\task;

use Hebbinkpro\WebServer\http\HttpRequest;
use Hebbinkpro\WebServer\WebClient;
use pocketmine\scheduler\Task;

class ClientSocketListenerTask extends Task implements SocketListener
{
    /**
     * @var WebClient[] $clients
     */
    private array $clients = [];

    public function onRun(): void
    {
        // loop through all the clients
        foreach ($this->clients as $i=>$client) {
            $socket = $client->getSocket();

            // check if the socket is still open
            if (feof($socket)) {
                // remove the client from the list
                array_splice($this->clients, $i, 1);
                // close the client
                $client->close();
                continue;
            }

            // check if there is some data
            if (($data = fread($socket, 8192)) !== false) {
                // publish the data to the main thread to handle it
                $this->runClient($client, $data);
            }
        }

    }

    private function runClient(WebClient $client, string $data) {
        // get the request from the data
        $req = HttpRequest::fromString($data);
        // invalid (non http) request
        if ($req === null) return;

        // handle the request
        $client->handleRequest($req);
    }

    public function addClient(WebClient $client) {
        $this->clients[] = $client;
    }

    /**
     * @return WebClient[]
     */
    public function getClients(): array
    {
        return $this->clients;
    }

    public function close(): void
    {
        foreach ($this->clients as $client) {
            $client->close();
        }
        $this->getHandler()->cancel();
    }
}