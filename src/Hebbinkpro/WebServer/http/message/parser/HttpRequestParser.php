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

namespace Hebbinkpro\WebServer\http\message\parser;

use Hebbinkpro\WebServer\http\HttpConstants;
use Hebbinkpro\WebServer\http\HttpHeaders;
use Hebbinkpro\WebServer\http\HttpMethod;
use Hebbinkpro\WebServer\http\HttpURI;
use Hebbinkpro\WebServer\http\HttpVersion;
use Hebbinkpro\WebServer\http\message\HttpMessageHeaders;
use Hebbinkpro\WebServer\http\message\HttpRequest;
use Hebbinkpro\WebServer\http\server\HttpServerInfo;
use Hebbinkpro\WebServer\http\status\HttpStatusCodes;
use InvalidArgumentException;
use Logger;

class HttpRequestParser implements HttpMessageParser
{

    private HttpServerInfo $serverInfo;
    private Logger $logger;

    private HttpParserState $state = HttpParserState::EMPTY;
    private int $errorStatusCode = 0;

    private string $buffer = "";
    private string $requestLine = "";
    private string $uriTarget = "";
    private string $headerData = "";
    private int $totalHeaderLength = 0;

    private HTTPMethod $method;
    private HttpURI $uri;
    private HttpVersion $version;

    private int $contentLength = 0;
    private int $bodyLength = 0;


    private ?HttpMessageHeaders $headers = null;
    private string $body = "";

    public function __construct(HttpServerInfo $serverInfo, \Logger $logger)
    {
        $this->serverInfo = $serverInfo;
        $this->logger = $logger;
    }

    /**
     * @inheritDoc
     */
    function appendData(string $data): ?string
    {
        // check if the buffer will exceed the maximum size
        if (strlen($this->buffer) + strlen($data) > HttpConstants::MAX_CLIENT_BUFFER_SIZE) {
            // this shouldn't be possible if the request was valid
            $this->logger->debug("[INVALID REQUEST] Max client buffer size reached");
            $this->setInvalid(HttpStatusCodes::BAD_REQUEST);
            return null;
        }

        // append the new data to the buffer
        $this->buffer .= $data;

        $previousState = null;
        // loop until the states dont change anymore
        while ($this->state !== $previousState) {
            $previousState = $this->state;
            switch ($this->state) {
                // throw exceptions in states in which it is impossible to append data
                case HttpParserState::COMPLETE:
                    throw new HttpRequestParserException("Cannot append data to a completed HTTP Request.");
                case HttpParserState::INVALID:
                    throw new HttpRequestParserException("Cannot append data to an invalid HTTP Request. Error Status Code: $this->errorStatusCode");

                // not yet started
                case HttpParserState::EMPTY:
                    // update the state and set default values
                    $this->state = HttpParserState::READING_REQUEST_LINE;
                    $this->requestLine = "";
                    break;

                // Read the request line
                case HttpParserState::READING_REQUEST_LINE:
                    if (!$this->parseRequestLine()) return null;

                    // update the state and set default values
                    $this->state = HttpParserState::READING_HEADERS;
                    $this->headerData = "";
                    $this->headers = new HttpMessageHeaders();
                    $this->totalHeaderLength = 0;
                    break;

                // read all headers
                case  HttpParserState::READING_HEADERS:
                    if (!$this->parseRequestHeaders()) return null;

                    // update the state and set default values
                    $this->body = "";
                    $this->contentLength = $this->headers->getHeader(HttpHeaders::CONTENT_LENGTH, 0);

                    if ($this->contentLength == 0) {
                        $this->state = HttpParserState::COMPLETE;
                    } else if ($this->contentLength > HttpConstants::MAX_BODY_SIZE) {
                        $this->logger->debug("[INVALID REQUEST] Content length is larger then max body size");
                        return $this->setInvalid(HttpStatusCodes::CONTENT_TOO_LARGE);
                    } else {
                        $this->state = HttpParserState::READING_BODY;
                    }
                    break;

                case HttpParserState::READING_BODY:
                    if (!$this->parseBody()) return null;
                    $this->state = HttpParserState::COMPLETE;
                    break;

            }

            // ensure we don't loop again if one of these states is reached
            if (in_array($this->state, [HttpParserState::COMPLETE, HttpParserState::INVALID], true)) {
                break;
            }
        }

        // Return null when the parser is not complete
        if ($this->state !== HttpParserState::COMPLETE) return null;

        // if the parser is complete, return all bytes from the buffer that are left
        return $this->buffer;
    }

    /**
     * Mark the request parser as invalid with an error code
     * @param int $errorCode an HTTP Status Code
     * @return false
     */
    private function setInvalid(int $errorCode): false
    {
        $this->errorStatusCode = $errorCode;
        $this->state = HttpParserState::INVALID;
        return false;
    }

    private function parseRequestLine(): bool
    {
        $finished = $this->readBufferUntil($this->requestLine, "\r\n");

        // validate the request line length
        if (strlen($this->requestLine) > HttpConstants::MAX_REQUEST_LINE_LENGTH) {
            $this->logger->debug("[INVALID REQUEST] Max request line length reached");
            return $this->setInvalid(HttpStatusCodes::URI_TOO_LONG);
        }

        // needs more data
        if (!$finished) {
            return false;
        }

        // parse the request line and store the values
        $res = HttpRequest::parseRequestLine($this->requestLine);

        // we got an error code
        if (is_int($res)) {
            $this->logger->debug("[INVALID REQUEST] Invalid Request line: $this->requestLine");
            return $this->setInvalid($res);
        }

        // store the result values
        [$this->method, $this->uriTarget, $this->version] = $res;

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
            // cutoff the until part from the end of the string
            $into .= substr($this->buffer, 0, -$untilSize);
            $this->buffer = "";
        }

        return true;
    }

    private function parseRequestHeaders(): bool
    {

        // loop until all headers have been read
        while (true) {
            $headersAvailable = $this->readBufferUntil($this->headerData, "\r\n");
            $headerLength = strlen($this->headerData);

            // current header line is too large
            if ($headerLength > HttpConstants::MAX_HEADER_LINE_LENGTH) {
                $this->logger->debug("[INVALID REQUEST] Max header line length reached");
                return $this->setInvalid(HttpStatusCodes::REQUEST_HEADER_FIELDS_TOO_LONG);
            }

            // incomplete header, wait for more data
            if (!$headersAvailable) return false;

            // found the double linebreak, headers are complete
            if ($headerLength == 0) break;

            // add the header length, and add 2 bytes for the linebreak (\r\n)
            $this->totalHeaderLength += $headerLength + 2;

            // the total header length is too large
            if ($this->totalHeaderLength > HttpConstants::MAX_TOTAL_HEADERS_LENGTH) {
                $this->logger->debug("[INVALID REQUEST] Max total header length reached");
                return $this->setInvalid(HttpStatusCodes::REQUEST_HEADER_FIELDS_TOO_LONG);
            }

            // split the header data into name and value
            $parts = explode(":", $this->headerData, 2);
            // invalid header
            if (count($parts) < 2) {
                $this->logger->debug("[INVALID REQUEST] Invalid header data: $this->headerData");
                return $this->setInvalid(HttpStatusCodes::BAD_REQUEST);
            }

            // add the header
            $this->headers->setHeader(trim($parts[0]), trim($parts[1]));
            // reset header data
            $this->headerData = "";
        }

        // host is required
        if (!$this->headers->exists(HttpHeaders::HOST)) {
            $this->logger->debug("[INVALID REQUEST] Missing header: host");
            return $this->setInvalid(HttpStatusCodes::BAD_REQUEST);
        }

        // parse the URI using the host header
        $scheme = $this->serverInfo->isSslEnabled() ? HttpConstants::HTTPS_SCHEME : HttpConstants::HTTP_SCHEME;
        $host = $this->headers->getHeader(HttpHeaders::HOST);
        $this->uri = HttpURI::parseRequestTarget($scheme, $host, $this->uriTarget);

        return true;
    }

    private function parseBody(): bool
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
            return $this->setInvalid(HttpStatusCodes::INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * @inheritDoc
     */
    function getState(): HttpParserState
    {
        return $this->state;
    }

    /**
     * @inheritDoc
     */
    function isComplete(): bool
    {
        return $this->state === HttpParserState::COMPLETE;
    }

    /**
     * @inheritDoc
     */
    function build(): HttpRequest
    {
        if ($this->state !== HttpParserState::COMPLETE) {
            throw new HttpRequestParserException("Cannot build an HttpRequest from an incomplete parser");
        }

        return new HttpRequest($this->method, $this->uri, $this->version, $this->headers, $this->body);
    }

    public function isInvalid(): bool
    {
        return $this->state === HttpParserState::INVALID;
    }

    public function getErrorStatusCode(): int
    {
        return $this->errorStatusCode;
    }
}