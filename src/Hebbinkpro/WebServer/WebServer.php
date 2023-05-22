<?php

namespace Hebbinkpro\WebServer;

use Hebbinkpro\WebServer\http\status\HttpStatus;
use Hebbinkpro\WebServer\route\Router;
use Hebbinkpro\WebServer\task\SocketListener;
use Hebbinkpro\WebServer\task\SocketListenTask;
use pocketmine\plugin\PluginBase;

class WebServer
{
    private PluginBase $plugin;
    private string $address;
    private int $port;

    private mixed $socket;

    private Router $router;
    private SocketListener $listener;

    private bool $started;

    public function __construct(PluginBase $plugin, string $address = "127.0.0.1", int $port = 3000)
    {
        $this->plugin = $plugin;
        $this->address = $address;
        $this->port = $port;

        // register all status codes
        HttpStatus::registerAll();

        $this->router = new Router($this);

        $this->started = false;
    }

    /**
     * @return string
     */
    public function getAddress(): string
    {
        return $this->address;
    }

    /**
     * @return int
     */
    public function getPort(): int
    {
        return $this->port;
    }

    /**
     * @return Router
     */
    public function getRouter(): Router
    {
        return $this->router;
    }

    /**
     * @return PluginBase
     */
    public function getPlugin(): PluginBase
    {
        return $this->plugin;
    }

    /**
     * Start the webserver
     * @param int $listenerPeriod the amount of ticks after which the web server is checking for new connections
     * @return void
     */
    public function start(int $listenerPeriod = 1): void
    {
        if ($this->started) {
            $this->plugin->getLogger()->warning("Could not start the webserver, it is already started.");
            return;
        }
        $this->started = true;

        $errorCode = 0;
        $error = "";
        $socket = stream_socket_server("tcp://$this->address:$this->port", $errorCode, $error);
        if (!$socket) {
            $this->plugin->getLogger()->error("Something went wrong while creating the web server socket: $errorCode" . PHP_EOL . $error);
            return;
        }
        $this->socket = $socket;

        $this->plugin->getLogger()->info("The web server is running at: http://$this->address:$this->port/");

        $this->listener = new SocketListenTask($this->plugin, $this, $this->socket);
        $this->plugin->getScheduler()->scheduleRepeatingTask($this->listener, $listenerPeriod);
    }

    /**
     * @return bool
     */
    public function isStarted(): bool
    {
        return $this->started;
    }

    /**
     * Stop the web server
     * @return void
     */
    public function close(): void
    {
        if (!$this->started) {
            $this->plugin->getLogger()->warning("Could not stop the web server, it is not running.");
            return;
        }

        $this->plugin->getLogger()->info("Stopping the web server...");

        // close all clients
        foreach ($this->listener->getClients() as $client) {
            $client->close();
        }

        // shutdown the socket
        stream_socket_shutdown($this->socket, STREAM_SHUT_RDWR);

        // close the listener
        $this->listener->close();
    }
}