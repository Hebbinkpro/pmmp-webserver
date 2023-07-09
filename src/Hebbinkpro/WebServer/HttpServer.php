<?php

namespace Hebbinkpro\WebServer;

use Exception;
use Hebbinkpro\WebServer\http\HttpRequest;
use Hebbinkpro\WebServer\http\status\HttpStatus;
use pocketmine\thread\Thread;
use pocketmine\thread\ThreadSafeClassLoader;

class HttpServer extends Thread
{
    /**
     * @var resource
     */
    private static mixed $socket = null;

    /**
     * @var WebClient[]
     */
    private static array $clients = [];

    private bool $alive = true;
    private WebServer $server;

    /**
     * @param WebServer $server
     * @param ThreadSafeClassLoader $classLoader
     */
    public function __construct(WebServer $server, ThreadSafeClassLoader $classLoader)
    {
        $this->server = $server;
        $this->setClassLoaders([$classLoader]);
    }

    /**
     * @throws Exception
     */
    protected function onRun(): void
    {
        $this->register();

        // declare the error variables
        $eCode = -1;
        $eMsg = null;

        // create the socket
        $socketAddress = "tcp://" . $this->server->getAddress() . ":" . $this->server->getPort();
        self::$socket = stream_socket_server($socketAddress, $eCode, $eMsg);
        socket_set_blocking(self::$socket, false);

        // if there is no socket created, throw the exception with the error.
        if (!self::$socket) {
            throw new Exception($eMsg);
        }

        // start the loop to listen for new clients
        while (!$this->isJoined() && is_resource(self::$socket)) {
            $this->serveExistingConnections();
            $this->serveNewConnections();

            usleep(100);
        }
    }

    /**
     * Register all required things to let this thread work
     * @return void
     */
    private function register(): void {
        // register all class loaders
        $this->registerClassLoaders();

        // register all status codes
        HttpStatus::registerAll();
    }

    private function closeConnections(): void {
        // close all clients
        foreach (self::$clients as $client) {
            $client->close();
        }

        // close the server socket
        stream_socket_shutdown(self::$socket, STREAM_SHUT_RDWR);
    }

    /**
     * Handles all client connections
     * @return void
     */
    private function serveExistingConnections(): void
    {
        $closed = [];

        // go through all clients
        foreach (self::$clients as $name => $client) {
            $socket = $client->getSocket();

            // check if the socket is still open
            if (feof($socket)) {
                $closed[] = $name;
                continue;
            }

            // check if there is some data
            if (($data = fread($socket, 8192)) !== false) {
                // publish the data to the main thread to handle it
                // get the request from the data
                $req = HttpRequest::fromString($data);
                // invalid (non http) request
                if ($req === null) return;

                // handle the request
                $client->handleRequest($req);
            }
        }

        // remove all closed clients
        foreach ($closed as $name) {
            // close the client
            self::$clients[$name]->close();
            // remove the client from the list
            unset(self::$clients[$name]);
        }
    }

    /**
     * Handles all  incoming connections
     * @return void
     */
    private function serveNewConnections(): void
    {

        try {
            for ($i = 0; $i < 100 && $incomingSocket = stream_socket_accept(self::$socket, 0); $i++) {

                $clientName = stream_socket_get_name($incomingSocket, true);
                $clientInfo = explode(":", $clientName);

                socket_set_blocking($incomingSocket, false);
                $client = new WebClient($this->server, $clientInfo[0], intval($clientInfo[1]), $incomingSocket);

                self::$clients[$clientName] = $client;
            }
        } catch (Exception $e) {
            // this exception is thrown when there was nothing to connect to
            return;
        }

    }
}