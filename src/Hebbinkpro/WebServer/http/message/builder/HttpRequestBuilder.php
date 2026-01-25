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

namespace Hebbinkpro\WebServer\http\message\builder;

use Hebbinkpro\WebServer\exception\HttpException;
use Hebbinkpro\WebServer\exception\HttpProblemException;
use Hebbinkpro\WebServer\http\HttpConstants;
use Hebbinkpro\WebServer\http\HttpHeaders;
use Hebbinkpro\WebServer\http\HttpMethod;
use Hebbinkpro\WebServer\http\HttpParsingRules;
use Hebbinkpro\WebServer\http\HttpProblem;
use Hebbinkpro\WebServer\http\HttpRequestLine;
use Hebbinkpro\WebServer\http\message\header\HttpHeaderBuilder;
use Hebbinkpro\WebServer\http\message\HttpRequest;
use Hebbinkpro\WebServer\http\server\HttpServer;
use Hebbinkpro\WebServer\http\server\HttpServerInfo;
use Hebbinkpro\WebServer\http\status\HttpStatusCodes;
use Hebbinkpro\WebServer\http\uri\HttpRequestForm;
use Hebbinkpro\WebServer\http\uri\PathUri;
use Hebbinkpro\WebServer\http\uri\url\HttpUrl;
use Hebbinkpro\WebServer\http\uri\url\HttpUrlFactory;
use InvalidArgumentException;
use Logger;

class HttpRequestBuilder implements HttpMessageBuilder
{

    private HttpServerInfo $serverInfo;
    private Logger $logger;

    private HttpBuilderState $state = HttpBuilderState::EMPTY;
    private ?HttpProblem $httpProblem = null;

    private string $buffer = "";
    private string $requestLineStr = "";
    private string $headerLine = "";
    private int $totalHeaderLength = 0;

    private HttpRequestLine $requestLine;

    private HttpUrl $url;

    private int $contentLength = 0;
    private int $bodyLength = 0;

    private HttpHeaderBuilder $headers;
    private string $body = "";

    public function __construct(HttpServerInfo $serverInfo, Logger $logger)
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
                case HttpBuilderState::COMPLETE:
                    throw new HttpRequestBuilderException("Cannot append data to a completed HTTP Request.");
                case HttpBuilderState::INVALID:
                    if ($this->httpProblem === null) $this->httpProblem = new HttpProblem(HttpStatusCodes::BAD_REQUEST, "/", null);
                    throw new HttpRequestBuilderException("Cannot append data to an invalid HTTP Request.", previous: new HttpException($this->httpProblem));

                // not yet started
                case HttpBuilderState::EMPTY:
                    // update the state and set default values
                    $this->state = HttpBuilderState::READING_REQUEST_LINE;
                    $this->requestLineStr = "";
                    break;

                // Read the request line
                case HttpBuilderState::READING_REQUEST_LINE:
                    if (!$this->buildRequestLine()) return null;

                    // update the state and set default values
                    $this->state = HttpBuilderState::READING_HEADERS;
                    $this->headerLine = "";
                    $this->headers = new HttpHeaderBuilder();
                    $this->totalHeaderLength = 0;
                    break;

                // read all headers
                case  HttpBuilderState::READING_HEADERS:
                    if (!$this->buildHeaders()) return null;

                    // update the state and set default values
                    $this->body = "";
                    $this->contentLength = intval($this->headers->getFieldValue(HttpHeaders::CONTENT_LENGTH, "0"));

                    if ($this->contentLength == 0) {
                        $this->state = HttpBuilderState::COMPLETE;
                    } else if ($this->contentLength > HttpConstants::MAX_BODY_SIZE) {
                        $this->setInvalid(HttpStatusCodes::CONTENT_TOO_LARGE, "Content length is larger then max body size");
                    } else {
                        $this->state = HttpBuilderState::READING_BODY;
                    }
                    break;

                case HttpBuilderState::READING_BODY:
                    if (!$this->buildBody()) return null;
                    $this->state = HttpBuilderState::COMPLETE;
                    break;

            }

            // ensure we don't loop again if one of these states is reached
            if (in_array($this->state, [HttpBuilderState::COMPLETE, HttpBuilderState::INVALID], true)) {
                break;
            }
        }

        // Return null when the builder is not complete
        if ($this->state !== HttpBuilderState::COMPLETE) return null;

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
        else $instance = $this->requestLine->getUriTarget();

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
        $this->state = HttpBuilderState::INVALID;
        $this->logger->debug("[INVALID REQUEST] {$problem->getDetail()}");
        throw new HttpException($this->httpProblem);
    }

    private function buildRequestLine(): bool
    {
        $finished = $this->readBufferUntil($this->requestLineStr, "\r\n");

        $lineSize = strlen($this->requestLineStr);

        // validate the request line length
        if ($lineSize > HttpConstants::MAX_REQUEST_LINE_LENGTH) {
            $this->setInvalid(HttpStatusCodes::URI_TOO_LONG, "Max request line length reached");
        }

        // needs more data, or got an empty line
        if (!$finished || $lineSize == 0) {
            return false;
        }

        // parse the request line and store the values
        try {
            $this->requestLine = HttpRequestLine::parse($this->requestLineStr);
        } catch (HttpException $e) {
            $this->setInvalidProblem($e->getHttpError());
        }

        $method = $this->requestLine->getMethod();
        $supportedMethods = HttpServer::getInstance()->getServerInfo()->getSupportedMethods();
        if (!in_array($method, $supportedMethods)) throw new HttpProblemException(
            HttpStatusCodes::NOT_IMPLEMENTED,
            $this->requestLine->getUriTarget(),
            "Not Implemented"
        );


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

    private function buildHeaders(): bool
    {

        // loop until all headers have been read
        while (true) {
            // read the next header into $this->headerData
            $headersAvailable = $this->readBufferUntil($this->headerLine, HttpParsingRules::CRLF);

            // first check buffer sizes, such that we do never read more into the buffer then we are allowed

            // current header line is already too long
            $headerLength = strlen($this->headerLine);
            if ($headerLength > HttpConstants::MAX_HEADER_LINE_LENGTH) {
                $this->setInvalid(HttpStatusCodes::REQUEST_HEADER_FIELDS_TOO_LONG, "Max header line length reached");
            }

            // add to total header length, and add 2 additional bytes for the linebreak (\r\n)
            $newTotalLength = $this->totalHeaderLength + $headerLength + 2;

            // the total header length is too large
            if ($newTotalLength > HttpConstants::MAX_TOTAL_HEADERS_LENGTH) {
                $this->setInvalid(HttpStatusCodes::REQUEST_HEADER_FIELDS_TOO_LONG, "Max total header length reached");
            }

            // incomplete header, wait for more data
            if (!$headersAvailable) return false;

            // write the total header length
            $this->totalHeaderLength = $newTotalLength;

            // found the double linebreak, headers are complete
            if ($headerLength == 0) break;

            // try to set the field from the parsed line
            try {
                $this->headers->setFromFieldLine($this->headerLine);
            } catch (HttpProblemException $e) {
                $this->setInvalidProblem($e->getHttpError());
            }

            // reset header line
            $this->headerLine = "";
        }

        // host is required for HTTP/1.1
        if (!$this->headers->fieldExists(HttpHeaders::HOST)) {
            $this->setInvalid(HttpStatusCodes::BAD_REQUEST, "Missing header: host");
        }

        $uriTarget = $this->requestLine->getUriTarget();
        try {
            $url = HttpUrlFactory::parseRequestTarget($uriTarget);
        } catch (HttpProblemException $e) {
            $this->setInvalidProblem($e->getHttpError());
        }

        switch ($url->getRequestForm()) {
            case HttpRequestForm::ASTERISK:
                // only valid for OPTIONS
                if ($this->requestLine->getMethod() !== HttpMethod::OPTIONS) {
                    $this->setInvalid(HttpStatusCodes::BAD_REQUEST, "Invalid request form");
                }
                break;

            case HttpRequestForm::AUTHORITY:
                // only valid for CONNECT
                if ($this->requestLine->getMethod() !== HttpMethod::CONNECT) {
                    $this->setInvalid(HttpStatusCodes::BAD_REQUEST, "Invalid request form");
                }
                break;

            case HttpRequestForm::ABSOLUTE:

                // if the server is not a proxy, convert to ORIGIN
                if (!$this->serverInfo->isProxy()) {
                    if ($url instanceof PathUri) {
                        // transform to Origin URL
                        $this->url = HttpUrlFactory::pathUriAsOrigin($url);
                    } else {
                        // unknown class
                        $this->setInvalid(HttpStatusCodes::BAD_REQUEST, "Invalid request form");
                    }
                }
                break;

            case HttpRequestForm::ORIGIN:
                $this->url = $url;
                break;
        }

        return true;
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
     * @inheritDoc
     */
    function getState(): HttpBuilderState
    {
        return $this->state;
    }

    /**
     * @inheritDoc
     */
    function isComplete(): bool
    {
        return $this->state === HttpBuilderState::COMPLETE;
    }

    /**
     * @inheritDoc
     */
    function build(): HttpRequest
    {
        if ($this->state !== HttpBuilderState::COMPLETE) {
            throw new HttpRequestBuilderException("Cannot build an HttpRequest from an incomplete builder");
        }

        return HttpRequest::withRequestLine($this->requestLine, $this->url, $this->headers->build(), $this->body);
    }

    /**
     * @phpstan-assert-if-true HttpBuilderState::INVALID $this->state
     * @phpstan-assert-if-true HttpProblem $this->httpProblem
     */
    public function isInvalid(): bool
    {
        return $this->state === HttpBuilderState::INVALID;
    }
}