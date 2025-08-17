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

namespace Hebbinkpro\WebServer\socket;

use Exception;

/**
 * Generic SocketClient class for sockets accepted by a stream_socket_server
 */
class SocketClient
{
    protected int $maxClientBufferSize = 65536; // 64KB
    protected string $buffer = "";
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
     * @return resource
     */
    public function getSocket(): mixed
    {
        return $this->socket;
    }

    /**
     * Close the connection to the client
     * @return void
     * @throws SocketException when an unexpected exception happened
     */
    public function close(): void
    {
        try {
            stream_socket_shutdown($this->socket, STREAM_SHUT_RDWR);
            fclose($this->socket);
        } catch (Exception $e) {
            throw new SocketException("Could not close socket {$this->getName()}.", 0, $e);
        }
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
     * Send data to the client
     * @param string $data the data to send
     * @return bool if the data was sent
     * @throws SocketClosedException when the socket is closed
     * @throws SocketException when an unexpected exception happened
     */
    public function write(string $data): bool
    {
        // we cannot write from a closed socket
        if (!$this->isAvailable()) {
            throw new SocketClosedException("Cannot write to closed socket {$this->getName()}");
        }

        try {
            fwrite($this->socket, $data);
        } catch (Exception $e) {
            throw new SocketException("Failed to write to socket {$this->getName()}", 0, $e);
        }

        return true;
    }


    /**
     * Check if the socket is still available
     * @return bool
     */
    public function isAvailable(): bool
    {
        try {
            return !feof($this->socket);
        } catch (Exception) {
            return false;
        }
    }

    /**
     * Flushes all written data to the client
     * @return void
     * @throws SocketException when an unexpected exception happend
     */
    public function flush(): void
    {
        try {
            fflush($this->socket);
        } catch (Exception $e) {
            throw new SocketException("Failed to flush socket {$this->getName()}.", 0, $e);
        }
    }

    /**
     * Read data from the client and append it to the buffer
     * @param int<1, max> $bytes
     * @return bool if data was added to the buffer
     * @throws SocketClosedException when the socket is closed
     * @throws SocketException when an unexpected exception happened
     * @throws SocketBufferOverflowException when too much data is put into the buffer
     */
    public function read(int $bytes): bool
    {

        // we cannot read from a closed socket
        if (!$this->isAvailable()) {
            throw new SocketClosedException("Cannot read from closed socket {$this->getName()}");
        }

        try {
            $data = fread($this->socket, $bytes);
        } catch (Exception $e) {
            throw new SocketException("Failed to read from socket {$this->getName()}", 0, $e);
        }

        // no data
        if ($data === false || strlen($data) <= 0) return false;

        // write data to the buffer
        $this->writeBuffer($data);

        return true;
    }

    /**
     * Write data to the buffer
     * @param string $data data to write
     * @return void
     * @throws SocketBufferOverflowException when too much data is put into the buffer
     */
    public function writeBuffer(string $data): void
    {
        $bufferSize = strlen($this->buffer) + strlen($data);
        if ($bufferSize > $this->maxClientBufferSize) {
            throw new SocketBufferOverflowException("Client Buffer cannot exceed " . $this->maxClientBufferSize . " bytes.");
        }

        $this->buffer .= $data;
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

}