<?php

namespace Hebbinkpro\WebServer;

use Hebbinkpro\WebServer\http\HttpRequest;

class WebClient
{
    private WebServer $server;
    private string $address;
    private int $port;

    /** @var resource */
    private mixed $socket;


    public function __construct(WebServer $server, string $address, int $port, mixed $socket)
    {
        $this->server = $server;
        $this->address = $address;
        $this->port = $port;
        $this->socket = $socket;
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
     * @return resource
     */
    public function getSocket(): mixed
    {
        return $this->socket;
    }

    public function close(): void
    {
        $this->server->getPlugin()->getLogger()->debug("Closing client: " . $this->address . ":" . $this->port);
        stream_socket_shutdown($this->socket, STREAM_SHUT_RDWR);
    }

    /**
     * Handle an incoming request
     * @param HttpRequest $req
     * @return void
     */
    public function handleRequest(HttpRequest $req): void
    {
        $this->getServer()->getRouter()->handleRequest($this, $req);
    }

    /**
     * @return WebServer
     */
    public function getServer(): WebServer
    {
        return $this->server;
    }

    /**
     * Send data to the client
     * @param string $data the data to send
     * @return void
     */
    public function send(string $data): void
    {
        fwrite($this->socket, $data, mb_strlen($data, '8bit'));
        fflush($this->socket);
    }
}