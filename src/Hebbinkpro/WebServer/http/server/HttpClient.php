<?php
/*
 * MIT License
 *
 * Copyright (c) 2025 Hebbinkpro
 *
 * Permission is hereby granted, free of charge, to any person obtaining a copy
 * of this software and associated documentation files (the "Software"), to deal
 * in the Software without restriction, including without limitation the rights
 * to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
 * copies of the Software, and to permit persons to whom the Software is
 * furnished to do so, subject to the following conditions:
 *
 * The above copyright notice and this permission notice shall be included in all
 * copies or substantial portions of the Software.
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 * IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
 * AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
 * OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE
 * SOFTWARE.
 */

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
     * @param int<1, max> $bytes
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