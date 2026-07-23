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

namespace Hebbinkpro\WebServer\http\message\request;

use Hebbinkpro\WebServer\exception\HttpException;
use Hebbinkpro\WebServer\exception\HttpProblemException;
use Hebbinkpro\WebServer\http\HttpConstants;
use Hebbinkpro\WebServer\http\HttpHeaders;
use Hebbinkpro\WebServer\http\HttpMethod;
use Hebbinkpro\WebServer\http\HttpProblem;
use Hebbinkpro\WebServer\http\HttpVersion;
use Hebbinkpro\WebServer\http\message\HttpBody;
use Hebbinkpro\WebServer\http\server\HttpClientInfo;
use Hebbinkpro\WebServer\http\server\HttpServer;
use Hebbinkpro\WebServer\http\server\HttpServerInfo;
use Hebbinkpro\WebServer\http\status\HttpStatusCodes;
use Hebbinkpro\WebServer\http\uri\AuthorityUri;
use Hebbinkpro\WebServer\http\uri\HttpRequestForm;
use Hebbinkpro\WebServer\http\uri\PathUri;
use Hebbinkpro\WebServer\http\uri\url\HttpUrlFactory;
use Hebbinkpro\WebServer\utils\Buffer;
use Hebbinkpro\WebServer\utils\StreamUtils;
use Logger;

class HttpRequestParser
{

    private HttpServerInfo $serverInfo;
    private Logger $logger;

    private HttpRequestParserState $state = HttpRequestParserState::EMPTY;
    private ?HttpProblem $httpProblem = null;

    private HttpRequestBuilder $builder;

    private Buffer $buffer;
    private int $headerLength = 0;

    private string $requestTarget = "";
    private int $contentLength = 0;
    private int $bodyLength = 0;

    /** @var resource */
    private mixed $body;

    public function __construct(Buffer $buffer, HttpServerInfo $serverInfo, Logger $logger)
    {
        $this->buffer = $buffer;
        $this->serverInfo = $serverInfo;
        $this->logger = $logger;
        $this->builder = new HttpRequestBuilder();
    }


    /**
     * Parse new data from the buffer and update the builder accordingly.
     * @return bool if the builder is complete, false if more data is needed
     */
    function readFromBuffer(): bool
    {

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
                    break;

                // Read the request line
                case HttpRequestParserState::READING_START_LINE:
                    if (!$this->parseRequestLine()) return false;

                    $this->parseRequestTarget();

                    // update the state and set default values
                    $this->state = HttpRequestParserState::READING_HEADER;
                    $this->headerLength = 0;
                    break;

                // read all headers
                case  HttpRequestParserState::READING_HEADER:
                    if (!$this->parseHeader()) return false;

                    $this->parseHostHeader();

                    // update the state and set default values
                    $this->body = StreamUtils::openTempStream();
                    $this->contentLength = intval($this->builder->getHeader()->getFieldValue(HttpHeaders::CONTENT_LENGTH, "0"));

                    if ($this->contentLength === 0) {
                        $this->state = HttpRequestParserState::COMPLETE;
                    } else if ($this->contentLength > HttpConstants::MAX_BODY_SIZE) {
                        $this->setInvalid(HttpStatusCodes::CONTENT_TOO_LARGE, "Content length is larger then max body size");
                    } else {
                        $this->state = HttpRequestParserState::READING_BODY;
                    }
                    break;

                case HttpRequestParserState::READING_BODY:
                    if (!$this->parseBody()) return false;
                    $this->state = HttpRequestParserState::COMPLETE;
                    break;

            }

            // ensure we don't loop again if one of these states is reached
            if (in_array($this->state, [HttpRequestParserState::COMPLETE, HttpRequestParserState::INVALID], true)) {
                break;
            }
        }

        // Return null when the builder is not complete
        if ($this->state !== HttpRequestParserState::COMPLETE) return false;

        return true;
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
        if (strlen($this->requestTarget) === 0) $instance = "/";
        else $instance = $this->requestTarget;

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

    private function parseRequestLine(): bool
    {
        $requestLine = $this->buffer->readLine(HttpConstants::MAX_START_LINE_LENGTH);
        if ($requestLine === null) return false;


        $lineSize = strlen($requestLine);

        // validate the request line length
        if ($lineSize >= HttpConstants::MAX_START_LINE_LENGTH) {
            $this->setInvalid(HttpStatusCodes::URI_TOO_LONG, "Max request line length reached");
        }

        // needs more data, or got an empty line
        if ($lineSize === 0) {
            return false;
        }

        // check if the request line contains exactly 2 spaces
        $count = substr_count($requestLine, " ");
        if ($count !== 2) {
            $this->setInvalid(HttpStatusCodes::BAD_REQUEST, "Malformed start line");
        }

        // get the different parts
        [$methodStr, $target, $versionStr] = explode(" ", $requestLine, 3);

        try {
            $this->builder->setHttpVersion(HttpVersion::parse($versionStr));
        } catch (HttpException $e) {
            $this->setInvalidProblem($e->getHttpError());
        }

        // validate the method, also gainst the servers supported methods
        $method = HttpMethod::parse(strtoupper($methodStr));
        if ($method === null) {
            $this->setInvalid(HttpStatusCodes::NOT_IMPLEMENTED, "Method not implemented");
        }
        $this->builder->setMethod($method);

        $this->requestTarget = $target;

        $supportedMethods = HttpServer::getInstance()->getServerInfo()->getSupportedMethods();
        if (!in_array($method, $supportedMethods, true)) {
            $this->setInvalid(HttpStatusCodes::NOT_IMPLEMENTED, "Method not implemented");
        }

        return true;
    }

    private function parseRequestTarget(): void
    {
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

    private function parseHeader(): bool
    {

        // loop until all headers have been read
        while (true) {
            $headerFieldLine = $this->buffer->readLine(HttpConstants::MAX_HEADER_LINE_LENGTH);
            if ($headerFieldLine === null) return false;

            // first check buffer sizes, such that we do never read more into the buffer then we are allowed

            // current header line is already too long
            $headerLength = strlen($headerFieldLine);
            if ($headerLength >= HttpConstants::MAX_HEADER_LINE_LENGTH) {
                $this->setInvalid(HttpStatusCodes::REQUEST_HEADER_FIELDS_TOO_LONG, "Max header line length reached");
            }

            // add to total header length, and add 2 additional bytes for the linebreak (\r\n)
            $newTotalLength = $this->headerLength + $headerLength + 2;

            // the total header length is too large
            if ($newTotalLength >= HttpConstants::MAX_TOTAL_HEADERS_LENGTH) {
                $this->setInvalid(HttpStatusCodes::REQUEST_HEADER_FIELDS_TOO_LONG, "Max total header length reached");
            }

            // write the total header length
            $this->headerLength = $newTotalLength;

            // found the double linebreak, headers are complete
            if ($headerLength === 0) break;

            // try to set the field from the parsed line
            try {
                $this->builder->getHeader()->setFromFieldLine($headerFieldLine);
            } catch (HttpProblemException $e) {
                $this->setInvalidProblem($e->getHttpError());
            }
        }

        return true;
    }

    /**
     * Parses the correct host header as specified in RFC 9112 section 3.2
     */
    private function parseHostHeader(): void
    {
        // host header must be included in all HTTP/1.1 request (RFC 9112 - 3.2)
        if (!$this->builder->getHeader()->fieldExists(HttpHeaders::HOST)) {
            $this->setInvalid(HttpStatusCodes::BAD_REQUEST, "Missing header: host");
        }

        $target = $this->builder->getTarget();
        if ($target instanceof AuthorityUri) {
            // all request forms with an authority field should use the authority as host
            // absolute-form: ignore host header if url is in absolute form (RFC 9112 - 3.2.2)
            // authority-form: use host and port from the authority field (RFC 9112 - 3.2.3)
            $host = $target->getAuthority()->getHostString();
        } else {
            /**
             * Since the Host header is known to exists, we can assume it is non-null
             * @var string $host
             * @phpstan-assert !null $host
             */
            $host = $this->builder->getHeader()->getFieldValue(HttpHeaders::HOST);
        }

        // set the host which should be used by the server by replacing it in the header
        $this->builder->getHeader()->setField(HttpHeaders::HOST, $host);
    }

    private function parseBody(): bool
    {
        $remaining = $this->contentLength - $this->bodyLength;
        $bufferSize = $this->buffer->getSize();

        // append the remaining bytes or the entire buffer
        $copySize = min($remaining, $bufferSize);
        if ($copySize > 0) {
            $copied = $this->buffer->copyToStream($this->body, $copySize);
            $this->bodyLength += $copied;
        }

        if ($this->bodyLength < $this->contentLength) {
            // need more data
            return false;
        }

        if ($this->bodyLength > $this->contentLength) {
            // something went horribly wrong
            $this->logger->emergency("[INVALID REQUEST] Body is larger then the given content length");
            $this->setInvalid(HttpStatusCodes::INTERNAL_SERVER_ERROR, null);
        }

        // complete
        $this->builder->setBody(new HttpBody($this->body));
        return true;
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
     * @param HttpClientInfo $client
     * @return HttpRequest
     */
    function build(HttpClientInfo $client): HttpRequest
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