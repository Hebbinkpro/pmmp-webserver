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

namespace Hebbinkpro\WebServer\http\message\request;

use Hebbinkpro\WebServer\exception\HttpException;
use Hebbinkpro\WebServer\exception\HttpProblemException;
use Hebbinkpro\WebServer\http\HttpConstants;
use Hebbinkpro\WebServer\http\HttpHeaders;
use Hebbinkpro\WebServer\http\HttpMethod;
use Hebbinkpro\WebServer\http\HttpParsingRules;
use Hebbinkpro\WebServer\http\HttpProblem;
use Hebbinkpro\WebServer\http\HttpVersion;
use Hebbinkpro\WebServer\http\server\HttpClient;
use Hebbinkpro\WebServer\http\server\HttpServer;
use Hebbinkpro\WebServer\http\server\HttpServerInfo;
use Hebbinkpro\WebServer\http\status\HttpStatusCodes;
use Hebbinkpro\WebServer\http\uri\HttpRequestForm;
use Hebbinkpro\WebServer\http\uri\PathUri;
use Hebbinkpro\WebServer\http\uri\url\HttpUrlFactory;
use InvalidArgumentException;
use Logger;

class HttpRequestParser
{

    private HttpServerInfo $serverInfo;
    private Logger $logger;

    private HttpRequestParserState $state = HttpRequestParserState::EMPTY;
    private ?HttpProblem $httpProblem = null;

    private HttpRequestBuilder $builder;

    private string $buffer = "";
    private string $startLine = "";
    private string $headerFieldLine = "";
    private int $headerLength = 0;

    private string $requestTarget = "";
    private int $contentLength = 0;
    private int $bodyLength = 0;
    private string $body = "";

    public function __construct(HttpServerInfo $serverInfo, Logger $logger)
    {
        $this->serverInfo = $serverInfo;
        $this->logger = $logger;
        $this->builder = new HttpRequestBuilder();
    }


    /**
     * Append new data to the builder
     * @param string $data the data to add to the builder
     * @return string|null Remaining data
     * @throws HttpRequestParserException if the builder is invalid or already completed
     * @throws HttpException if the appended data resulted in an invalid HTTP request
     */
    function appendData(string $data): ?string
    {
        // check if the buffer will exceed the maximum size
        if (strlen($this->buffer) + strlen($data) > HttpConstants::MAX_CLIENT_BUFFER_SIZE) {
            // this shouldn't be possible if the request was valid
            $this->logger->debug("[INVALID REQUEST] Max client buffer size reached");
            $this->setInvalid(HttpStatusCodes::BAD_REQUEST, "Max client buffer size reached");
        }

        // append the new data to the buffer
        $this->buffer .= $data;

        $previousState = null;
        // loop until the states don't change anymore
        while ($this->state !== $previousState) {
            $previousState = $this->state;
            switch ($this->state) {
                // throw exceptions in states in which it is impossible to append data
                case HttpRequestParserState::COMPLETE:
                    throw new HttpRequestParserException("Cannot append data to a completed HTTP Request.");
                case HttpRequestParserState::INVALID:
                    if ($this->httpProblem === null) $this->httpProblem = new HttpProblem(HttpStatusCodes::BAD_REQUEST, "/", null);
                    throw new HttpRequestParserException("Cannot append data to an invalid HTTP Request.", previous: new HttpException($this->httpProblem));

                // not yet started
                case HttpRequestParserState::EMPTY:
                    // update the state and set default values
                    $this->state = HttpRequestParserState::READING_START_LINE;
                    $this->startLine = "";
                    break;

                // Read the request line
                case HttpRequestParserState::READING_START_LINE:
                    if (!$this->buildRequestLine()) return null;

                    // update the state and set default values
                    $this->state = HttpRequestParserState::READING_HEADER;
                    $this->headerFieldLine = "";
                    $this->headerLength = 0;
                    break;

                // read all headers
                case  HttpRequestParserState::READING_HEADER:
                    if (!$this->readHeader()) return null;

                    $this->buildHeader();

                    // update the state and set default values
                    $this->body = "";
                    $this->contentLength = intval($this->builder->getHeaderBuilder()->getFieldValue(HttpHeaders::CONTENT_LENGTH, "0"));

                    if ($this->contentLength == 0) {
                        $this->state = HttpRequestParserState::COMPLETE;
                    } else if ($this->contentLength > HttpConstants::MAX_BODY_SIZE) {
                        $this->setInvalid(HttpStatusCodes::CONTENT_TOO_LARGE, "Content length is larger then max body size");
                    } else {
                        $this->state = HttpRequestParserState::READING_BODY;
                    }
                    break;

                case HttpRequestParserState::READING_BODY:
                    if (!$this->buildBody()) return null;
                    $this->state = HttpRequestParserState::COMPLETE;
                    break;

            }

            // ensure we don't loop again if one of these states is reached
            if (in_array($this->state, [HttpRequestParserState::COMPLETE, HttpRequestParserState::INVALID], true)) {
                break;
            }
        }

        // Return null when the builder is not complete
        if ($this->state !== HttpRequestParserState::COMPLETE) return null;

        // if the builder is complete, return all bytes from the buffer that are left
        return $this->buffer;
    }

    /**
     * Mark the request builder as invalid with a status code and detail.
     *
     * This will automatically retrieve the <code>$instance</code> URI from the request line if it exists.
     * @param int $status the HTTP Status code to respond
     * @param string|null $detail the error detail
     * @return never
     */
    private function setInvalid(int $status, ?string $detail): never
    {
        if (!isset($this->requestLine)) $instance = "/";
        else $instance = $this->requestLine->getTarget();

        $this->setInvalidProblem(new HttpProblem($status, $instance, $detail));
    }

    /**
     * Mark the request builder as invalid with an HttpException
     * @param HttpProblem $problem HTTP problem details
     * @return never will always throw an HttpException
     * @throws HttpException with the HTTP Problem details
     */
    private function setInvalidProblem(HttpProblem $problem): never
    {
        $this->httpProblem = $problem;
        $this->state = HttpRequestParserState::INVALID;
        $this->logger->debug("[INVALID REQUEST] {$problem->getDetail()}");
        throw new HttpException($this->httpProblem);
    }

    private function buildRequestLine(): bool
    {
        $finished = $this->readBufferUntil($this->startLine, "\r\n");

        $lineSize = strlen($this->startLine);

        // validate the request line length
        if ($lineSize > HttpConstants::MAX_REQUEST_LINE_LENGTH) {
            $this->setInvalid(HttpStatusCodes::URI_TOO_LONG, "Max request line length reached");
        }

        // needs more data, or got an empty line
        if (!$finished || $lineSize == 0) {
            return false;
        }

        // check if the request line contains exactly 2 spaces
        $count = substr_count($this->startLine, " ");
        if ($count != 2) {
            $this->setInvalid(HttpStatusCodes::BAD_REQUEST, "Malformed start line");
        }

        // get the different parts
        [$methodStr, $target, $versionStr] = explode(" ", $this->startLine, 3);;

        try {
            $this->builder->setHttpVersion(HttpVersion::parse($versionStr));
        } catch (HttpException $e) {
            $this->setInvalidProblem($e->getHttpError());
        }


        // ensure it is a valid token
        if (!@preg_match("/^" . HttpParsingRules::TOKEN . "$/", $methodStr)) {
            $this->setInvalid(HttpStatusCodes::BAD_REQUEST, "Malformed Request Method");
        }

        // validate the method, also gainst the servers supported methods
        $method = HttpMethod::parse(strtoupper($methodStr));
        if ($method === null) {
            $this->setInvalid(HttpStatusCodes::NOT_IMPLEMENTED, "Method not implemented");
        }
        $this->builder->setMethod($method);

        // allow all visible ascii characters, proper parsing will be done later
        if (!@preg_match("/^" . HttpParsingRules::VCHAR . "+$/", $target)) {
            $this->setInvalid(HttpStatusCodes::BAD_REQUEST, "Invalid Request Target");
        }
        $this->requestTarget = $target;

        $supportedMethods = HttpServer::getInstance()->getServerInfo()->getSupportedMethods();
        if (!in_array($method, $supportedMethods)) {
            $this->setInvalid(HttpStatusCodes::NOT_IMPLEMENTED, "Method not implemented");
        }

        return true;
    }

    /**
     * Read all bytes upto the $until string
     * @param string $into The variable into which the read data should be stored
     * @param string $until Until where the buffer should be read, THIS IS EXCLUSIVE
     * @return bool If the until string was encountered, if this value is FALSE, no value is written to $into
     */
    private function readBufferUntil(string &$into, string $until): bool
    {
        $untilSize = strlen($until);
        if ($untilSize == 0) throw new InvalidArgumentException('$until cannot be empty');

        $bufferSize = strlen($this->buffer);
        $pos = strpos($this->buffer, $until);

        // partial write to into if the until string was not found
        if ($pos === false) {
            // keep the last bytes in the buffer, in the case they are part of $until
            $keep = $untilSize - 1;

            // skip substr calls when only the keep bytes are in the buffer
            if ($bufferSize > $keep) {
                // flush all other data to $into
                $into .= substr($this->buffer, 0, $bufferSize - $keep);
                // put the keep bytes back into the buffer
                $this->buffer = substr($this->buffer, -$keep);
            }
            return false;
        }

        // skip substr calls when all data that needs to be written is in the buffer
        if ($bufferSize > $pos + $untilSize) {
            // read until the pos of the until string
            $into .= substr($this->buffer, 0, $pos);
            // remove the data and until string from the buffer
            $this->buffer = substr($this->buffer, $pos + $untilSize);
        } else {
            // cut off the until part from the end of the string
            $into .= substr($this->buffer, 0, -$untilSize);
            $this->buffer = "";
        }

        return true;
    }

    private function readHeader(): bool
    {

        // loop until all headers have been read
        while (true) {
            // read the next header into $this->headerData
            $headersAvailable = $this->readBufferUntil($this->headerFieldLine, HttpParsingRules::CRLF);

            // first check buffer sizes, such that we do never read more into the buffer then we are allowed

            // current header line is already too long
            $headerLength = strlen($this->headerFieldLine);
            if ($headerLength > HttpConstants::MAX_HEADER_LINE_LENGTH) {
                $this->setInvalid(HttpStatusCodes::REQUEST_HEADER_FIELDS_TOO_LONG, "Max header line length reached");
            }

            // add to total header length, and add 2 additional bytes for the linebreak (\r\n)
            $newTotalLength = $this->headerLength + $headerLength + 2;

            // the total header length is too large
            if ($newTotalLength > HttpConstants::MAX_TOTAL_HEADERS_LENGTH) {
                $this->setInvalid(HttpStatusCodes::REQUEST_HEADER_FIELDS_TOO_LONG, "Max total header length reached");
            }

            // incomplete header, wait for more data
            if (!$headersAvailable) return false;

            // write the total header length
            $this->headerLength = $newTotalLength;

            // found the double linebreak, headers are complete
            if ($headerLength == 0) break;

            // try to set the field from the parsed line
            try {
                $this->builder->getHeaderBuilder()->setFromFieldLine($this->headerFieldLine);
            } catch (HttpProblemException $e) {
                $this->setInvalidProblem($e->getHttpError());
            }

            // reset header line
            $this->headerFieldLine = "";
        }

        return true;
    }

    private function buildHeader(): void
    {

        // host is required for HTTP/1.1
        if (!$this->builder->getHeaderBuilder()->fieldExists(HttpHeaders::HOST)) {
            $this->setInvalid(HttpStatusCodes::BAD_REQUEST, "Missing header: host");
        }

        try {
            $url = HttpUrlFactory::parseRequestTarget($this->requestTarget);
        } catch (HttpProblemException $e) {
            $this->setInvalidProblem($e->getHttpError());
        }

        switch ($url->getRequestForm()) {
            case HttpRequestForm::ASTERISK:
                // only valid for OPTIONS
                if ($this->builder->getMethod() !== HttpMethod::OPTIONS) {
                    $this->setInvalid(HttpStatusCodes::BAD_REQUEST, "Invalid request form");
                }
                break;

            case HttpRequestForm::AUTHORITY:
                // only valid for CONNECT
                if ($this->builder->getMethod() !== HttpMethod::CONNECT) {
                    $this->setInvalid(HttpStatusCodes::BAD_REQUEST, "Invalid request form");
                }
                break;

            case HttpRequestForm::ABSOLUTE:

                // if the server is not a proxy, convert to ORIGIN
                if (!$this->serverInfo->isProxy()) {
                    if ($url instanceof PathUri) {
                        // transform to Origin URL
                        $this->builder->setTarget(HttpUrlFactory::pathUriAsOrigin($url));
                    } else {
                        // unknown class
                        $this->setInvalid(HttpStatusCodes::BAD_REQUEST, "Invalid request form");
                    }
                }
                break;

            case HttpRequestForm::ORIGIN:
                $this->builder->setTarget($url);
                break;
        }

    }

    private function buildBody(): bool
    {
        $remaining = $this->contentLength - $this->bodyLength;
        $bufferSize = strlen($this->buffer);

        // append the complete buffer
        if ($remaining >= $bufferSize) {
            $this->body .= $this->buffer;
            $this->buffer = "";
            $this->bodyLength += $bufferSize;
        } else {
            $bodyData = substr($this->buffer, 0, $remaining);
            $this->buffer = substr($this->buffer, $remaining);
            $this->body .= $bodyData;
            $this->bodyLength += strlen($bodyData);
        }

        if ($this->bodyLength < $this->contentLength) {
            // need more data
            return false;
        } else if ($this->bodyLength === $this->contentLength) {
            // complete
            return true;
        } else {
            // something went horribly wrong
            $this->logger->emergency("[INVALID REQUEST] Body is larger then the given content length");
            $this->setInvalid(HttpStatusCodes::INTERNAL_SERVER_ERROR, null);
        }
    }

    /**
     * @return HttpProblem|null
     */
    public function getHttpProblem(): ?HttpProblem
    {
        return $this->httpProblem;
    }

    /**
     * Get the current state of the builder
     * @return HttpRequestParserState
     */
    function getState(): HttpRequestParserState
    {
        return $this->state;
    }

    /**
     * Get if the message is completely parsed
     * @return bool
     */
    function isComplete(): bool
    {
        return $this->state === HttpRequestParserState::COMPLETE;
    }

    /**
     * Build an HTTP Message from a completely parsed message
     * @param HttpClient $client
     * @return HttpRequest
     */
    function build(HttpClient $client): HttpRequest
    {
        if ($this->state !== HttpRequestParserState::COMPLETE) {
            throw new HttpRequestParserException("Cannot build an HttpRequest from an incomplete builder");
        }

        return $this->builder->build($client);
    }

    /**
     * @phpstan-assert-if-true HttpRequestParserState::INVALID $this->state
     * @phpstan-assert-if-true HttpProblem $this->httpProblem
     */
    public function isInvalid(): bool
    {
        return $this->state === HttpRequestParserState::INVALID;
    }
}