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
use Hebbinkpro\WebServer\http\HttpConstants;
use Hebbinkpro\WebServer\http\message\builder\HttpRequestBuilder;
use Hebbinkpro\WebServer\http\message\HttpRequest;
use Hebbinkpro\WebServer\http\status\HttpStatusCodes;
use Hebbinkpro\WebServer\router\Router;
use LogicException;

class HttpClient
{
    private string $host;
    private int $port;

    /** @var resource */
    private mixed $socket;

    protected string $buffer = "";

    private bool $closed = false;

    private ?HttpRequestBuilder $requestBuilder = null;

    /** @var int The time when the client was last active (unix time in seconds) */
    private int $lastActivity;

    private int $servedRequests = 0;


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
        $this->updateLastActivity();
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
     * Get the name of the client as [host]:[port]
     * @return string [host]:[port]
     */
    public function getName(): string
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
        fclose($this->socket);
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

            // update activity
            $this->updateLastActivity();
        } catch (Exception $e) {
            HttpServer::getInstance()->getLogger()->error("Could not write to client socket. " . $e->getMessage());
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
     * Read data from the client and append it to the buffer
     * @param int<1, max> $bytes
     * @return bool if data was added to the buffer
     */
    public function read(int $bytes): bool
    {

        // we cannot read from a closed socket
        if (!$this->isAvailable()) return false;

        try {
            $data = fread($this->socket, $bytes);
        } catch (Exception) {
            return false;
        }

        // no data
        if (strlen($data) <= 0) return false;

        // write data to the buffer
        $this->writeBuffer($data);

        // update activity
        $this->updateLastActivity();
        return true;
    }

    /**
     * Get the data from the buffer, this will clear the buffer
     * @return string the data from the buffer
     */
    public function readBuffer(): string
    {
        $data = $this->buffer;
        $this->buffer = "";
        return $data;
    }

    /**
     * Write data to the buffer
     * @param string $data data to write
     * @return void
     */
    public function writeBuffer(string $data): void
    {
        $bufferSize = strlen($this->buffer) + strlen($data);
        if ($bufferSize > HttpConstants::MAX_CLIENT_BUFFER_SIZE) {
            throw new HttpServerException(HttpStatusCodes::BAD_REQUEST, "Client Buffer cannot exceed " . HttpConstants::MAX_CLIENT_BUFFER_SIZE . " bytes.");
        }

        $this->buffer .= $data;
    }

    /**
     * Set a new request builder
     * @param HttpRequestBuilder $builder
     * @return void
     */
    public function setRequestBuilder(HttpRequestBuilder $builder): void
    {
        if ($this->requestBuilder !== null) {
            throw new LogicException("Cannot set a RequestBuilder when the previous builder is still active!");
        }

        $this->requestBuilder = $builder;
    }

    public function getRequestBuilder(): ?HttpRequestBuilder
    {
        return $this->requestBuilder;
    }

    /**
     * Mark the client as closed
     * @param bool $closed
     * @return void
     */
    public function setClosed(bool $closed = true): void
    {
        $this->closed = $closed;
    }

    public function isClosed(): bool
    {
        return $this->closed;
    }

    /**
     * @return resource
     */
    public function getSocket()
    {
        return $this->socket;
    }

    /**
     * Get the time when the client sent data for the last time
     * @return int in seconds since the Unix Epoch
     */
    public function getLastActivity(): int
    {
        return $this->lastActivity;
    }

    /**
     * Serve an HTTP request
     * @param Router $router the router which will serve the request
     * @param HttpRequest $req the request to serve
     * @return void
     */
    public function serveRequest(Router $router, HttpRequest $req): void
    {
        // remove the request builder from the client, since its finished
        $this->requestBuilder = null;

        // increment served requests, this value will be used by handle request
        $this->servedRequests++;

        // handle the request
        $router->handleRequest($this, $req);
    }

    /**
     * Get the number of requests served by this client
     * @return int
     */
    public function getServedRequests(): int
    {
        return $this->servedRequests;
    }

    /**
     * Update the time the client was last active
     * @return void
     */
    private function updateLastActivity(): void
    {
        $this->lastActivity = time();
    }
}