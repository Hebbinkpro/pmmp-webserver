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

declare(strict_types=1);

namespace Hebbinkpro\WebServer\http\server;

use Exception;
use Hebbinkpro\WebServer\exception\HttpException;
use Hebbinkpro\WebServer\http\HttpConstants;
use Hebbinkpro\WebServer\http\HttpHeaders;
use Hebbinkpro\WebServer\http\HttpProblem;
use Hebbinkpro\WebServer\http\message\request\HttpRequest;
use Hebbinkpro\WebServer\http\message\request\HttpRequestParser;
use Hebbinkpro\WebServer\http\message\request\HttpRequestParserException;
use Hebbinkpro\WebServer\http\message\response\HttpResponse;
use Hebbinkpro\WebServer\http\status\HttpStatusCodes;
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

    private ?HttpRequestParser $requestParser = null;

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
     * Set a new request parser
     * @param HttpRequestParser $parser
     * @return void
     */
    public function setRequestParser(HttpRequestParser $parser): void
    {
        if ($this->requestParser !== null) {
            throw new LogicException("Cannot set a RequestParser when the previous parser is still active!");
        }

        $this->requestParser = $parser;
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

        $this->updateLastActivity();

        $serverInfo = HttpServer::getInstance()->getServerInfo();
        $router = $serverInfo->getRouter();

        // serve all requests until an incomplete request was received
        $dataSent = false;
        while (true) {
            $parser = $this->getOrCreateRequestParser();

            // append the client buffer to the builder
            try {
                $success = $parser->readFromBuffer();
            } catch (HttpException $e) {
                // the HTTP request was invalid
                $this->reject($e->getHttpError());
                break;
            } catch (HttpRequestParserException $e) {
                // this should never happen if the builder is properly used
                $this->logger->error("Error while parsing request: " . $e->getMessage());
                $problem = new HttpProblem(HttpStatusCodes::INTERNAL_SERVER_ERROR, null, $e->getMessage());
                $this->reject($problem);
                break;
            }

            // the request is not complete
            if (!$success) break;

            // build the HTTP Request from the parsed result
            $req = $parser->build($this);

            // reset request builder
            $this->requestParser = null;

            // we are serving a new request, so increment the counter
            $this->servedRequests++;

            // if not already closed, validate the http connection using the headers
            if (!$this->closed) $this->closed = $this->validateHttpConnection($req);

            // handle the request
            try {
                $res = $router->handleRequest($this, $req);
                $this->sendResponse($res);
            } catch (Exception $e) {
                // log the error but don't reject the connection as it's unavailable
                $this->logger->logException($e);
            }

            $dataSent = true;
        }

        if ($dataSent) {
            // ensure all data is sent to the client
            try {
                $this->flush();
            } catch (SocketException) {
                // ignore exception, can already be closing
            }

            // compact the buffer to free up memory
            $this->buffer->cleanup();
        }

    }

    /**
     * Reject a client request
     * @param HttpProblem $problem the reason of the rejection
     * @param string $level logging level of the rejection reason
     * @return void
     */
    private function reject(HttpProblem $problem, string $level = LogLevel::DEBUG): void
    {
        $this->closed = true;
        HttpServer::getInstance()->getServerInfo()->getRouter()->rejectRequestWithProblem($this, $problem);
        if ($problem->getDetail() !== null) {
            $this->logger->log($level, "Client rejected. Reason: " . $problem->getDetail());
        }
    }

    /**
     * Send a HTTP Response to the client
     *
     * This writes all data of the response to the socket of the client
     * @param HttpResponse $res the response to be send
     * @return void
     */
    private function sendResponse(HttpResponse $res): void
    {
        $requestLine = $res->getVersion()->toString() . " " . $res->getStatus()->toString();
        $this->write($requestLine . "\r\n");

        $headers = $res->getHeaders()->toString();
        $this->write($headers . "\r\n");

        if (($body = $res->getBody()) !== null) {
            // ensure the entire body can be read
            $body->rewind();

            if ($body->getLength() > 0) {
                // stream the body to the socket
                $this->stream($body->getStream(), $body->getLength());
                // always end with a new line
                $this->write("\r\n");
            }
        }

        $this->flush();
    }

    /**
     * Returns the HttpRequestBuilder of the client or creates one
     * @return HttpRequestParser
     */
    public function getOrCreateRequestParser(): HttpRequestParser
    {
        if ($this->requestParser === null || $this->requestParser->isInvalid()) {
            $this->requestParser = new HttpRequestParser($this->buffer, HttpServer::getInstance()->getServerInfo(), $this->logger);
        }

        return $this->requestParser;
    }

    /**
     * Check if the connection with the client should be closed after this request
     * @param HttpRequest $req
     * @return bool if the connection should be closed after handling this request
     */
    private function validateHttpConnection(HttpRequest $req): bool
    {
        // if Connection: close, close the connection after handling the request
        if ($req->getHeader()->getFieldValue(HttpHeaders::CONNECTION, "keep-alive") === "close") {
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