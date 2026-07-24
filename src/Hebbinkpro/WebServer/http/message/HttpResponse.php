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

namespace Hebbinkpro\WebServer\http\message;

use DateTime;
use DateTimeInterface;
use Hebbinkpro\WebServer\exception\FileNotFoundException;
use Hebbinkpro\WebServer\http\HttpContentType;
use Hebbinkpro\WebServer\http\HttpHeaders;
use Hebbinkpro\WebServer\http\HttpVersion;
use Hebbinkpro\WebServer\http\message\header\HttpHeader;
use Hebbinkpro\WebServer\http\message\header\HttpHeaderBuilder;
use Hebbinkpro\WebServer\http\server\HttpClient;
use Hebbinkpro\WebServer\http\server\HttpServer;
use Hebbinkpro\WebServer\http\status\HttpStatus;
use Hebbinkpro\WebServer\http\status\HttpStatusCodes;
use Hebbinkpro\WebServer\http\status\HttpStatusRegistry;

/**
 * HTTP Response send by the server
 * @deprecated Will be replaced by an immutable class
 */
class HttpResponse implements HttpMessage
{
    private HttpClient $client;
    private HttpStatus $status;
    private HttpVersion $version;
    private HttpHeaderBuilder $headers;
    private string $body; // Moved To HttpBody
    private bool $head;
    private bool $ended;

    /**
     * Construct a basic response
     * @param HttpClient $client
     * @param int|HttpStatus $status
     * @param string $body
     * @param bool $head
     * @param HttpHeaderBuilder $headers
     */
    public function __construct(HttpClient $client, int|HttpStatus $status, string $body = "", bool $head = false, HttpHeaderBuilder $headers = new HttpHeaderBuilder())
    {
        $this->client = $client;
        $this->status = HttpStatusRegistry::getInstance()->parseOrDefault($status);
        $this->version = HttpVersion::getDefault();
        $this->headers = $headers;
        $this->body = $head ? "" : $body;
        $this->head = $head;
        $this->ended = false;

        // set some default headers
        // Not my problem
        $this->headers->addField(HttpHeaders::CONTENT_TYPE, HttpContentType::TEXT_HTML);
        $this->headers->addField(HttpHeaders::CONTENT_ENCODING, "utf-8");

    }

    /**
     * Construct a 200 OK response
     * @param HttpClient $client
     * @return HttpResponse
     * @deprecated Moved to HttpResponseFactory
     */
    public static function ok(HttpClient $client): HttpResponse
    {
        return new HttpResponse($client, HttpStatusCodes::OK);
    }

    /**
     * Construct a 204 No Content response.
     *
     * Using this constructor will make it IMPOSSIBLE to send content to the client,
     * as the head-only flag will be enabled.
     * @param HttpClient $client
     * @return HttpResponse
     * @deprecated Moved to HttpResponseFactory
     */
    public static function noContent(HttpClient $client): HttpResponse
    {
        return new HttpResponse($client, HttpStatusCodes::NO_CONTENT, "", true);
    }

    /**
     * Construct a 404 Not Found response
     * @param HttpClient $client
     * @return HttpResponse
     * @deprecated Moved to HttpResponseFactory
     */
    public static function notFound(HttpClient $client): HttpResponse
    {
        $res = new HttpResponse($client, HttpStatusCodes::NOT_FOUND);
        $res->getHeaders()->setField(HttpHeaders::CONNECTION, "close");
        $res->text($res->getStatus()->toString());
        return $res;
    }

    public function getHeaders(): HttpHeader
    {
        return $this->headers->build();
    }

    /**
     * Send plain text to the client
     * @param string $data
     * @return void
     * @deprecated Moved to HttpResponseBuilder
     */
    public function text(string $data): void
    {
        $this->setBody($data);
    }

    /**
     * Create a string representation of the response
     * @return string
     * @deprecated Moved to HttpBody
     */
    public function toString(): string
    {
        $data = $this->version->toString() . " " . $this->status->toString() . "\r\n";
        $data .= $this->headers->build()->toString() . "\r\n";
        $data .= strlen($this->body) === 0 ? "" : $this->body . "\r\n";

        return $data;
    }

    /**
     * @return HttpStatus
     */
    public function getStatus(): HttpStatus
    {
        return $this->status;
    }

    /**
     * @param int|HttpStatus $status
     */
    public function setStatus(int|HttpStatus $status): void
    {
        $this->status = HttpStatusRegistry::getInstance()->parseOrDefault($status);
    }

    /**
     * Construct a 500 Internal Server Error response
     * @param HttpClient $client
     * @return HttpResponse
     * @deprecated Moved to HttpResponseFactory
     */
    public static function internalServerError(HttpClient $client): HttpResponse
    {
        $res = new HttpResponse($client, HttpStatusCodes::INTERNAL_SERVER_ERROR);
        $res->getHeaders()->setField(HttpHeaders::CONNECTION, "close");
        $res->text($res->getStatus()->toString());
        return $res;
    }

    /**
     * Construct a 501 Not Implemented response
     * @param HttpClient $client
     * @return HttpResponse
     * @deprecated Moved to HttpResponseFactory
     */
    public static function notImplemented(HttpClient $client): HttpResponse
    {
        $res = new HttpResponse($client, HttpStatusCodes::NOT_IMPLEMENTED);
        $res->getHeaders()->setField(HttpHeaders::CONNECTION, "close");
        $res->text($res->getStatus()->toString());
        return $res;
    }

    /**
     * Send data
     * @param string $data
     * @param string $contentType content type of the data
     * @return void
     * @deprecated use setBody instead, to be removed in v0.7.0
     */
    public function send(string $data, string $contentType = HttpContentType::TEXT_HTML): void
    {
        $this->setBody($data, $contentType);
    }

    /**
     * Send the status message
     * @return void
     * @deprecated Moved to HttpResponseFactory
     */
    public function sendStatusMessage(): void
    {
        $this->headers->addField(HttpHeaders::CONTENT_TYPE, HttpContentType::TEXT_PLAIN);
        $this->body = $this->status->getMessage();
    }

    /**
     * Get the client to which this response should be sent
     * @return HttpClient
     */
    public function getClient(): HttpClient
    {
        return $this->client;
    }

    /**
     * @inheritDoc
     */
    public function getVersion(): HttpVersion
    {
        return $this->version;
    }

    /**
     * @inheritDoc
     */
    public function getBody(): string
    {
        return $this->body;
    }

    /**
     * Set the response body
     * @param string $data the body data
     * @param string $contentType the content type of the data, the default is text/html
     * @return void
     */
    public function setBody(string $data, string $contentType = HttpContentType::TEXT_HTML): void
    {
        // it is not possible to add data to a HEAD response
        if ($this->head) return;

        $this->setContentType($contentType);
        $this->body = $data;
    }

    /**
     * Send the contents of a file to the client.
     * @param string $fileName name of the file to send
     * @param string|null $default default value when the file does not exist
     * @param string|null $contentType if no content type is given, the file extension will be used to detect it
     * @return void
     * @throws FileNotFoundException when the file does not exist and the default value is null
     * @deprecated Moved to HttpResponseBuilder
     */
    public function sendFile(string $fileName, ?string $default = null, ?string $contentType = null): void
    {
        if (!file_exists($fileName) && $default === null || $fileName === "") throw new FileNotFoundException($fileName);

        $parts = explode(".", $fileName);
        $fileExtension = end($parts);

        if ($contentType === null) {
            // no content type given try to make something of it
            $contentType = match ($fileExtension) {
                "txt" => HttpContentType::TEXT_PLAIN,
                "css" => HttpContentType::TEXT_CSS,
                "js" => HttpContentType::TEXT_JAVASCRIPT,
                "json" => HttpContentType::APPLICATION_JSON,
                "jpeg" => HttpContentType::IMAGE_JPEG,
                "png" => HttpContentType::IMAGE_PNG,
                "gif" => HttpContentType::IMAGE_GIF,
                "csv" => HttpContentType::TEXT_CSV,
                "xml" => HttpContentType::TEXT_XML,
                "mp3" => HttpContentType::AUDIO_MPEG,
                "mp4" => HttpContentType::VIDEO_MP4,
                default => HttpContentType::TEXT_HTML,
            };
        }

        $body = file_exists($fileName) ? file_get_contents($fileName) : $default;
        if (!is_string($body)) $body = "";

        $this->setBody($body, $contentType);
    }

    /**
     * Send an array as JSON to the client.
     *
     * @param array<mixed> $data
     * @param string $contentType default: application/json
     * @return void
     * @deprecated Moved to HttpResponseBuilder
     */
    public function json(array $data, string $contentType = HttpContentType::APPLICATION_JSON): void
    {
        $json = json_encode($data);
        if (!is_string($json)) $json = "[]";
        $this->setBody($json, $contentType);
    }

    /**
     * Set the content type of the response
     * @param string $contentType the content type
     * @return void
     */
    public function setContentType(string $contentType): void
    {
        $this->headers->addField(HttpHeaders::CONTENT_TYPE, $contentType);
    }

    /**
     * End the response, this will send the response to the client.
     *
     * After a response is ended, it is sent immediately and cannot be sent again.
     * @return void
     * @deprecated Moved to HttpResponseBuilder::finalize() and write to HttpClient::sendResponse()
     */
    public function end(): void
    {
        // already ended
        if ($this->ended) return;

        $this->ended = true;

        // set the content length to the body size
        $this->headers->addField(HttpHeaders::CONTENT_LENGTH, strval(strlen($this->body)));

        // check if head was used
        if ($this->head) {
            // unset the body
            $this->body = "";

            // if the status was 200 OK, replace it with 204 No Content
            if ($this->status->getCode() === HttpStatusCodes::OK) {
                $this->setStatus(HttpStatusCodes::NO_CONTENT);
            }
        }

        // get the server info
        $serverInfo = HttpServer::getInstance()->getServerInfo();

        // set the date header
        $this->headers->addField(HttpHeaders::DATE, (new DateTime())->format(DateTimeInterface::RFC7231));

        // set the server name if it is set
        if (($serverName = $serverInfo->getName()) !== null) {
            $this->headers->addField(HttpHeaders::SERVER, $serverName);
        }


        // set the correct header
        if ($this->client->isClosed()) {
            // set close if client has closed the connection
            $this->headers->addField(HttpHeaders::CONNECTION, "close");
        } else if (!$this->headers->fieldExists(HttpHeaders::CONNECTION)) {
            // set keep-alive if no header was set
            $this->headers->addField(HttpHeaders::CONNECTION, "keep-alive");
        }

        // if connection is keep-alive, set Keep-Alive header
        if ($this->headers->getFieldValue(HttpHeaders::CONNECTION) === "keep-alive") {
            $values = [];

            // set timeout
            $keepAliveTimeout = $serverInfo->getKeepAliveTimeout();
            if ($keepAliveTimeout > 0) {
                $values[] = "timeout=" . $keepAliveTimeout;
            }

            // set max
            $keepAliveMax = $serverInfo->getKeepAliveMax();
            if ($keepAliveMax > 0) {
                $remaining = $keepAliveMax - $this->client->getServedRequests();
                $values[] = "max=" . $remaining;
            }

            // set the keep alive header if a value is set
            if (count($values) > 0) {
                $this->headers->addField(HttpHeaders::KEEP_ALIVE, implode(",", $values));
            }
        }

        // send the constructed data to the client
        $this->client->write($this->toString());
    }

    /**
     * Get if the response is ended
     * @return bool
     * @deprecated Removed
     */
    public function isEnded(): bool
    {
        return $this->ended;
    }
}