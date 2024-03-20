<?php

namespace Hebbinkpro\WebServer\http\server;

use Exception;

class HttpClient
{
    private string $host;
    private int $port;

    /** @var resource */
    private mixed $socket;


    /**
     * @param string $host
     * @param int $port
     * @param resource $socket
     */
    public function __construct(string $host, int $port, mixed $socket)
    {
        $this->host = $host;
        $this->port = $port;
        $this->socket = $socket;
    }

    /**
     * @return string
     */
    public function getHost(): string
    {
        return $this->host;
    }

    /**
     * @return int
     */
    public function getPort(): int
    {
        return $this->port;
    }

    /**
     * Get the address (host:port) of the client
     * @return string
     */
    public function getAddress(): string
    {
        return $this->host . ":" . $this->port;
    }

    /**
     * Close the connection to the client
     * @return void
     */
    public function close(): void
    {
        stream_socket_shutdown($this->socket, STREAM_SHUT_RDWR);
    }

    /**
     * Send data to the client
     * @param string $data the data to send
     * @return void
     */
    public function send(string $data): void
    {
        // we cannot write from a closed socket
        if (!$this->isAvailable()) return;

        try {
            fwrite($this->socket, $data, strlen($data));
        } catch (Exception $e) {
        }
    }

    /**
     * Check if the socket is still available
     * @return bool
     */
    public function isAvailable(): bool
    {
        return !feof($this->socket);
    }

    /**
     * Flushes all written data to the client
     * @return void
     */
    public function flush(): void
    {
        fflush($this->socket);
    }

    /**
     * Read data from the client
     * @param int<0, max> $bytes
     * @return string|false
     */
    public function read(int $bytes): string|false
    {
        // we cannot read from a closed socket
        if (!$this->isAvailable()) return false;

        try {
            return fread($this->socket, $bytes);
        } catch (Exception) {
            return false;
        }
    }
}