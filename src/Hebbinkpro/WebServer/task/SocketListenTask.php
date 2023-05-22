<?php

namespace Hebbinkpro\WebServer\task;

use Exception;
use Hebbinkpro\WebServer\WebClient;
use Hebbinkpro\WebServer\WebServer;
use pocketmine\plugin\Plugin;
use pocketmine\scheduler\Task;

class SocketListenTask extends Task implements SocketListener
{
    private Plugin $plugin;
    private WebServer $server;

    /** @var resource */
    private mixed $socket;
    /** @var WebClient[] */
    private array $clients;

    public function __construct(Plugin $plugin, WebServer $server, $socket)
    {

        $this->plugin = $plugin;
        $this->server = $server;

        $this->socket = $socket;
        $this->clients = [];
    }

    public function onRun(): void
    {
        // make sure socket is not blocking
        socket_set_blocking($this->socket, false);

        try {
            $socket = stream_socket_accept($this->socket, 0);
        } catch (Exception $e) {
            // error occurs when the task is repeated, so we can just ignore it
            return;
        }

        if (is_resource($this->socket) && $socket !== false) {
            $clientName = stream_socket_get_name($socket, true);
            $clientInfo = explode(":", $clientName);
            $this->server->getPlugin()->getLogger()->debug("Client connected: $clientName");

            $client = new WebClient($this->server, $clientInfo[0], intval($clientInfo[1]), $socket);

            // add the client to the list
            $this->clients[] = $client;

            // create a task for the client
            stream_set_blocking($client->getSocket(), true);
            $this->plugin->getServer()->getAsyncPool()->submitTask(new AsyncClientSocketListenTask($client, $client->getSocket()));
        }
    }

    public function close(): void
    {
        $this->getHandler()->cancel();
    }

    /**
     * @return WebClient[]
     */
    public function getClients(): array
    {
        return $this->clients;
    }
}