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

namespace Hebbinkpro\WebServer\http\message\response;

use DateTime;
use DateTimeInterface;
use Hebbinkpro\WebServer\exception\FileNotFoundException;
use Hebbinkpro\WebServer\http\HttpContentType;
use Hebbinkpro\WebServer\http\HttpHeaders;
use Hebbinkpro\WebServer\http\HttpVersion;
use Hebbinkpro\WebServer\http\message\header\HttpHeaderBuilder;
use Hebbinkpro\WebServer\http\message\HttpBody;
use Hebbinkpro\WebServer\http\server\HttpClient;
use Hebbinkpro\WebServer\http\server\HttpServer;
use Hebbinkpro\WebServer\http\status\HttpStatus;
use Hebbinkpro\WebServer\http\status\HttpStatusCodes;
use Hebbinkpro\WebServer\http\status\HttpStatusRegistry;
use JsonException;
use LogicException;

class HttpResponseBuilder implements Response
{
    private HttpStatus $status;
    private HttpHeaderBuilder $headers;
    /** @var resource|null */
    private mixed $body;
    private bool $headOnly;

    /**
     * @param bool $headOnly if true, only the response headers will be set in the final HttpResponse
     */
    public function __construct(bool $headOnly = false)
    {
        $this->status = HttpStatusRegistry::getInstance()->get(HttpStatusCodes::OK);
        $this->headers = new HttpHeaderBuilder();
        $this->body = null;
        $this->headOnly = $headOnly;
    }

    /**
     * Set the status of the response
     * @param HttpStatus|int $status
     * @return $this
     */
    public function setStatus(HttpStatus|int $status): HttpResponseBuilder
    {
        $this->status = HttpStatusRegistry::getInstance()->parseOrDefault($status);
        return $this;
    }

    /**
     * @return HttpHeaderBuilder
     */
    public function getHeader(): HttpHeaderBuilder
    {
        return $this->headers;
    }


    /**
     * Send a string as plain text
     *
     *  Alias: `sendString($text, HttpContentType::TEXT_PLAIN)`
     * @param string $text the text to send
     * @return $this
     */
    public function text(string $text): HttpResponseBuilder
    {
        $this->sendString($text, HttpContentType::TEXT_PLAIN);
        return $this;
    }

    /**
     * Send a string as a response.
     *
     * Sets the body as a temporary file stream to which the string is written.
     * @param string $data the data to send
     * @param string $contentType the content type of the data
     * @return $this
     */
    public function sendString(string $data, string $contentType): HttpResponseBuilder
    {
        $stream = fopen("php://temp", "r+");
        fwrite($stream, $data);
        rewind($stream);

        $this->setBody(new HttpBody($stream));
        $this->setContentType($contentType);
        return $this;
    }

    /**
     * Set the body stream of the response
     *
     * @param HttpBody|null $body the body
     * @return $this
     */
    public function setBody(?HttpBody $body): HttpResponseBuilder
    {
        $this->body = $body;
        return $this;
    }

    /**
     * Set the content type of the response
     *
     * Equivalent to `$builder->getHeaders()->setHeader(HttpHeaders::CONTENT_TYPE, $contentType)`
     * @param string $contentType
     * @return $this
     */
    public function setContentType(string $contentType): HttpResponseBuilder
    {
        $this->headers->setField(HttpHeaders::CONTENT_TYPE, $contentType);
        return $this;
    }

    /**
     * Send HTML as a response
     *
     * Alias: `sendString($html, HttpContentType::TEXT_HTML)`
     * @param string $html the HTML to send
     * @return $this
     */
    public function html(string $html): HttpResponseBuilder
    {
        $this->sendString($html, HttpContentType::TEXT_HTML);
        return $this;
    }

    /**
     * Send JSON data as a response
     *
     * Encodes the JSON data using `json_encode($json, $flags | JSON_THROW_ON_ERROR)`
     *
     * Alias: `sendString($encoded_json, HttpContentType::APPLICATION_JSON);`
     * @param array $data the JSON data to send
     * @param int $flags the `json_encode` flags to use when encoding the JSON data
     * @return $this
     * @throws JsonException if the JSON data is invalid
     */
    public function json(array $data, int $flags = JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES): HttpResponseBuilder
    {
        $this->sendString(json_encode($data, $flags | JSON_THROW_ON_ERROR), HttpContentType::APPLICATION_JSON);
        return $this;
    }

    /**
     * Send a file as a response or send a default value if the file does not exist
     *
     * The content type is required to determine the content type of the default value.
     * @param string $filename the name of the file to send
     * @param string $default the default value to send if the file does not exist
     * @param string $contentType the content type of the file and default value
     * @return $this
     */
    public function sendFileOrDefault(mixed $filename, string $default, string $contentType): HttpResponseBuilder
    {
        try {
            return $this->sendFile($filename, $contentType);
        } catch (FileNotFoundException) {
            // file does not exist, send the default value
            return $this->sendString($default, $contentType);
        }
    }

    /**
     * Send a file as a response
     *
     * - Creates a file stream using `fopen()`
     * - By enabling `$textModeTranslation`, \n will be translated into \r\n when reading the file. Refer to PHP's `fopen` documentation for more information.
     * @param string $filename the name of the file to send
     * @param string|null $contentType the content type of the file. If null, `mime_content_type()` is used
     * @param bool $textModeTranslation if true, the file is opened in text mode, otherwise in binary mode. Defaults to false.
     * @return $this
     * @throws FileNotFoundException if the file does not exist
     * @link https://www.php.net/manual/en/function.fopen.php
     */
    public function sendFile(string $filename, ?string $contentType = null, bool $textModeTranslation = false): HttpResponseBuilder
    {

        if (!file_exists($filename)) throw new FileNotFoundException($filename);

        if ($textModeTranslation) {
            $stream = fopen($filename, "rt");
        } else {
            $stream = fopen($filename, "rb");
        }

        if ($contentType === null) {
            // determine the mimetype, otherwise default to octet-stream
            $contentType = @mime_content_type($filename);
            if ($contentType === false) $contentType = HttpContentType::APPLICATION_OCTET_STREAM;
        }

        // try to get the filesize
        $length = @filesize($filename);
        if ($length === false) {
            throw new LogicException("Could not determine file size of $filename");
        }

        return $this->sendStream($stream, $contentType);
    }

    /**
     * Send an octet stream as a response
     * - Warning: If `$copyStream` is false, the response will assume that it has complete ownership over the stream. Using the provided stream after calling this function is therefore not recommended as it can lead to unexpected behaviour.
     * - If `$copyStream` is true, a copy of the stream will be stored in a temporary file and lets the response use the created temp file instead of the original stream. `stream_copy_to_stream($stream, $tmp)` is used to the to the temp file.
     * @param resource $stream the stream to send
     * @param string $contentType the content type of the stream, defaults to octet-stream
     * @param bool $copyStream if true, the stream will be copied to a temporary file. Defaults to false.
     * @return $this
     */
    public function sendStream(mixed $stream, string $contentType = HttpContentType::APPLICATION_OCTET_STREAM, bool $copyStream = false): HttpResponseBuilder
    {

        if (!$copyStream) {
            // use the stream
            $this->setBody(new HttpBody($stream));
        } else {
            // copy the stream to a temporary file and use the temp file
            $tempStream = fopen("php://temp", "r+");
            stream_copy_to_stream($stream, $tempStream);
            $this->setBody(new HttpBody($tempStream));
        }

        $this->setContentType($contentType);
        return $this;
    }

    public function build(HttpClient $client): HttpResponse
    {
        $this->finalize($client);
        $headers = $this->headers->build();

        // set body to null if head only
        $body = $this->headOnly ? null : $this->body;
        return new HttpResponse($client, $this->status, $headers, $body);
    }


    /**
     * Finalize the response such that it is ready to be built
     *
     * This function will always be called during `build()`, which ensures that everything set here will be part of the response.
     * @param HttpClient $client
     * @return void
     */
    protected function finalize(HttpClient $client): void
    {
        $serverInfo = HttpServer::getInstance()->getServerInfo();

        // set the final content length
        $contentLength = $this->body?->getLength() ?? 0;
        $this->headers->setField(HttpHeaders::CONTENT_LENGTH, strval($contentLength));

        // set server headers
        $this->headers->setField(HttpHeaders::DATE, (new DateTime())->format(DateTimeInterface::RFC7231));

        // set the server name if it is set
        if ($serverInfo->getName() !== null) {
            $this->headers->setField(HttpHeaders::SERVER, $serverInfo->getName());
        }

        // if connection is keep-alive, set Keep-Alive header
        if (!$client->isClosed()) {
            $this->headers->setField(HttpHeaders::CONNECTION, "keep-alive");
            $values = [];

            // set timeout
            $keepAliveTimeout = HttpServer::getInstance()->getServerInfo()->getKeepAliveTimeout();
            if ($keepAliveTimeout > 0) {
                $values[] = "timeout=" . $keepAliveTimeout;
            }

            // set max
            $keepAliveMax = HttpServer::getInstance()->getServerInfo()->getKeepAliveMax();
            if ($keepAliveMax > 0) {
                $values[] = "max=" . $keepAliveMax;
            }

            // set the keep alive header if a value is set
            if (sizeof($values) > 0) {
                $this->headers->setField(HttpHeaders::KEEP_ALIVE, implode(",", $values));
            }
        } else {
            $this->headers->setField(HttpHeaders::CONNECTION, "close");
        }
    }

    public function getStatus(): HttpStatus
    {
        return $this->status;
    }

    public function getVersion(): HttpVersion
    {
        return HttpVersion::getDefault();
    }

    public function getBody(): ?HttpBody
    {
        return $this->body;
    }

    /**
     * Get if the response is a head only response
     * @return bool
     */
    public function isHeadOnly(): bool
    {
        return $this->headOnly;
    }

}