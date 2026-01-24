<?php
/*
 * MIT License
 *
 * Copyright (c) 2025-2026 Hebbinkpro
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
use Hebbinkpro\WebServer\exception\HttpException;
use Hebbinkpro\WebServer\http\HttpConstants;
use Hebbinkpro\WebServer\http\HttpHeaders;
use Hebbinkpro\WebServer\http\HttpProblem;
use Hebbinkpro\WebServer\http\message\builder\HttpRequestBuilder;
use Hebbinkpro\WebServer\http\message\builder\HttpRequestBuilderException;
use Hebbinkpro\WebServer\http\message\HttpRequest;
use Hebbinkpro\WebServer\http\status\HttpStatusCodes;
use Hebbinkpro\WebServer\socket\SocketBufferOverflowException;
use Hebbinkpro\WebServer\socket\SocketClient;
use Hebbinkpro\WebServer\socket\SocketException;
use Logger;
use LogicException;
use LogLevel;
use PrefixedLogger;

class HttpClient extends SocketClient
{
    protected int $maxClientBufferSize = HttpConstants::MAX_CLIENT_BUFFER_SIZE;

    private bool $closed = false;

    private ?HttpRequestBuilder $requestBuilder = null;

    /** @var int The time when the client was last active (unix time in seconds) */
    private int $lastActivity;

    private int $servedRequests = 0;

    private Logger $logger;


    /**
     * @param string $host
     * @param int $port
     * @param resource $socket
     */
    public function __construct(string $host, int $port, mixed $socket)
    {
        parent::__construct($host, $port, $socket);
        $this->updateLastActivity();

        // create our own logger
        $this->logger = new PrefixedLogger(HttpServer::getInstance()->getLogger(), $this->getName());
    }

    /**
     * Update the time the client was last active
     * @return void
     */
    private function updateLastActivity(): void
    {
        $this->lastActivity = time();
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

    public function isClosed(): bool
    {
        return $this->closed;
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
     * Let the client read incoming data and perform HTTP requests
     * @return void
     */
    public function serve(): void
    {

        try {
            $hasData = $this->read(HttpConstants::MAX_STREAM_READ_LENGTH);
            if (!$hasData) return;
        } catch (Exception $e) {
            $problem = new HttpProblem(HttpStatusCodes::INTERNAL_SERVER_ERROR, null, $e->getMessage());
            $this->reject($problem, LogLevel::ERROR);
            return;
        }

        $serverInfo = HttpServer::getInstance()->getServerInfo();
        $builder = $this->getOrCreateRequestBuilder();
        $router = $serverInfo->getRouter();

        // append the client buffer to the builder
        try {
            $remaining = $builder->appendData($this->readBuffer());
        } catch (HttpException $e) {
            // the HTTP request was invalid
            $this->reject($e->getHttpError());
            return;
        } catch (HttpRequestBuilderException $e) {
            // this should never happen if the builder is properly used
            $this->logger->error("Error while parsing request: " . $e->getMessage());
            $this->reject(new HttpProblem(HttpStatusCodes::INTERNAL_SERVER_ERROR, null, $e->getMessage()));
            return;
        }

        // the request is not complete
        if (!$builder->isComplete()) return;

        try {
            // write remaining data back to the client buffer
            $this->writeBuffer($remaining ?? "");
        } catch (SocketBufferOverflowException $e) {
            // too much data in the buffer, this shouldn't even be possible since the builder buffer has the same size
            $problem = new HttpProblem(HttpStatusCodes::INTERNAL_SERVER_ERROR, null, $e->getMessage());
            $this->reject($problem, LogLevel::ERROR);
            return;
        }

        // build the HTTP Request from the parsed result
        $req = $builder->build();

        // reset request builder
        $this->requestBuilder = null;

        // we are serving a new request, so increment the counter
        $this->servedRequests++;

        // if not already closed, validate the http connection using the headers
        if (!$this->closed) $this->closed = $this->validateHttpConnection($req);

        // handle the request
        try {
            $router->handleRequest($this, $req);
        } catch (Exception $e) {
            // log the error but don't reject the connection as it's unavailable
            $this->logger->logException($e);
        }

        // ensure all data is flushed
        try {
            $this->flush();
        } catch (SocketException) {
            // ignore exception, can already be closing
        }
    }

    private function reject(HttpProblem $problem, string $level = LogLevel::DEBUG): void
    {
        $this->closed = true;
        HttpServer::getInstance()->getServerInfo()->getRouter()->rejectRequestWithProblem($this, $problem);
        if ($problem->getDetail() !== null) $this->logger->log($level, "Client rejected. Reason: " . $problem->getDetail());
    }

    /**
     * Returns the HttpRequestBuilder of the client or creates one
     * @return HttpRequestBuilder
     */
    public function getOrCreateRequestBuilder(): HttpRequestBuilder
    {
        if ($this->requestBuilder === null || $this->requestBuilder->isInvalid()) {
            $this->requestBuilder = new HttpRequestBuilder(HttpServer::getInstance()->getServerInfo(), $this->logger);
        }

        return $this->requestBuilder;
    }

    /**
     * Check if the connection with the client should be closed after this request
     * @param HttpRequest $req
     * @return bool if the connection should be closed after handling this request
     */
    private function validateHttpConnection(HttpRequest $req): bool
    {
        // if Connection: close, close the connection after handling the request
        if ($req->getHeaders()->getFieldValue(HttpHeaders::CONNECTION, "keep-alive") === "close") {
            return true;
        }

        // max is reached, close connection after
        $keepAliveMax = HttpServer::getInstance()->getServerInfo()->getKeepAliveMax();
        if ($keepAliveMax > 0 && $this->getServedRequests() + 1 >= $keepAliveMax) {
            return true;
        }

        return false;
    }

    /**
     * Get the number of requests served by this client
     * @return int
     */
    public function getServedRequests(): int
    {
        return $this->servedRequests;
    }
}