<?php

namespace Hebbinkpro\WebServer\http\message;

use DateTime;
use DateTimeInterface;
use Hebbinkpro\WebServer\exception\FileNotFoundException;
use Hebbinkpro\WebServer\http\HttpHeaders;
use Hebbinkpro\WebServer\http\HttpMessageHeaders;
use Hebbinkpro\WebServer\http\HttpVersion;
use Hebbinkpro\WebServer\http\server\HttpClient;
use Hebbinkpro\WebServer\http\status\HttpStatus;
use Hebbinkpro\WebServer\http\status\HttpStatusCodes;
use Hebbinkpro\WebServer\http\status\HttpStatusRegistry;
use Hebbinkpro\WebServer\WebServer;

/**
 * HTTP Response send by the server
 */
class HttpResponse implements HttpMessage
{
    private HttpClient $client;
    private HttpStatus $status;
    private HttpVersion $version;
    private HttpMessageHeaders $headers;
    private string $body;
    private bool $head;
    private bool $ended;

    /**
     * Construct a basic response
     * @param HttpClient $client
     * @param int|HttpStatus $status
     * @param string $body
     * @param bool $head
     * @param HttpMessageHeaders $headers
     */
    public function __construct(HttpClient $client, int|HttpStatus $status, string $body = "", bool $head = false, HttpMessageHeaders $headers = new HttpMessageHeaders())
    {
        $this->client = $client;
        $this->status = HttpStatusRegistry::getInstance()->parse($status);
        $this->version = HttpVersion::getDefault();
        $this->headers = $headers;
        $this->body = $head ? "" : $body;
        $this->head = $head;
        $this->ended = false;

        // set some default headers
        $this->headers->setHeader(HttpHeaders::CONTENT_TYPE, "text/plain");
        $this->headers->setHeader(HttpHeaders::CONTENT_ENCODING, "utf-8");

    }

    /**
     * Construct a 200 OK response
     * @param HttpClient $client
     * @return HttpResponse
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
     */
    public static function noContent(HttpClient $client): HttpResponse
    {
        return new HttpResponse($client, HttpStatusCodes::NO_CONTENT, "", true);
    }

    /**
     * Construct a 404 Not Found response
     * @param HttpClient $client
     * @return HttpResponse
     */
    public static function notFound(HttpClient $client): HttpResponse
    {
        $res = new HttpResponse($client, HttpStatusCodes::NOT_F0UND);
        $res->getHeaders()->setHeader(HttpHeaders::CONNECTION, "close");
        $res->text($res->getStatus()->toString());
        return $res;
    }

    public function getHeaders(): HttpMessageHeaders
    {
        return $this->headers;
    }

    /**
     * Send plain text to the client
     * @param string $data
     * @return void
     */
    public function text(string $data): void
    {
        $this->send($data, "text/plain");
    }

    /**
     * Send data as html
     * @param string $data
     * @param string $contentType content type of the data
     * @return void
     */
    public function send(string $data, string $contentType = "text/html"): void
    {
        // it is not possible to add data to a HEAD response
        if ($this->head) return;

        $this->headers->setHeader(HttpHeaders::CONTENT_TYPE, $contentType);
        $this->body = $data;
    }

    public function toString(): string
    {
        $data = $this->version->toString() . " " . $this->status->toString() . "\r\n";
        $data .= $this->headers->toString() . "\r\n";
        $data .= strlen($this->body) == 0 ? "" : $this->body . "\r\n";

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
        $this->status = HttpStatusRegistry::getInstance()->parse($status);
    }

    /**
     * Construct a 501 Not Implemented response
     * @param HttpClient $client
     * @return HttpResponse
     */
    public static function notImplemented(HttpClient $client): HttpResponse
    {
        $res = new HttpResponse($client, HttpStatusCodes::NOT_IMPLEMENTED);
        $res->getHeaders()->setHeader(HttpHeaders::CONNECTION, "close");
        $res->text($res->getStatus()->toString());
        return $res;
    }

    /**
     * Get the client to which this response should be sent
     * @return HttpClient
     */
    public function getClient(): HttpClient
    {
        return $this->client;
    }

    public function getVersion(): HttpVersion
    {
        return $this->version;
    }

    public function getBody(): string
    {
        return $this->body;
    }

    /**
     * Send the contents of a file to the client.
     *
     * Sets the Content-Type if the file extension is recognized.
     * - HTML: text/html
     * - JSON: application/json
     * - etc...
     * @param string $fileName name of the file to send
     * @param string|null $default default value when the file does not exist
     * @return void
     * @throws FileNotFoundException when the file does not exist and the default value is null
     */
    public function sendFile(string $fileName, ?string $default = null): void
    {
        if (!file_exists($fileName) && $default === null) throw new FileNotFoundException($fileName);

        $parts = explode(".", $fileName) ?? [];
        $fileExtension = end($parts);

        // TODO create something for all known HTTP content types

        $contentType = "text/html";
        switch (strtolower($fileExtension)) {
            case "txt":
                $contentType = "text/plain";
                break;
            case "css":
                $contentType = "text/css";
                break;
            case "js":
                $contentType = "application/javascript";
                break;
            case "json":
                $contentType = "application/json";
                break;
            case "jpeg":
                $contentType = "image/jpeg";
                break;
            case "png":
                $contentType = "image/png";
                break;
            case "gif":
                $contentType = "image/gif";
                break;
            case "csv":
                $contentType = "text/csv";
                break;
            case "xml":
                $contentType = "application/xml";
                break;
            case "mp3":
                $contentType = "audio/mpeg";
                break;
            case "mp4":
                $contentType = "video/mp4";
                break;
        }

        $body = file_exists($fileName) ? file_get_contents($fileName) : $default;
        $this->send($body, $contentType);
    }

    /**
     * Send an array as JSON to the client.
     *
     * This sets the content-type header to: application/json
     * @param array $data
     * @return void
     */
    public function json(array $data): void
    {
        $this->send(json_encode($data), "application/json");
    }

    /**
     * End the response, this will send the response to the client.
     *
     * After a response is ended, it is sent immediately and cannot be sent again.
     * @return void
     */
    public function end(): void
    {
        // already ended
        if ($this->ended) return;

        $this->ended = true;

        $bodyLength = strlen($this->body);

        // validate the body
        if ($bodyLength == 0 || $this->head) {
            // we don't have a body or are sending the head
            // unset the body
            $this->body = "";

            // if the status was 200 OK, replace it with 204 No Content
            if ($this->status->getCode() === HttpStatusCodes::OK) {
                $this->setStatus(HttpStatusCodes::NO_CONTENT);
            }
        }

        // set the content length to the body size
        $this->headers->setHeader(HttpHeaders::CONTENT_LENGTH, strlen($this->body));

        // set the final headers
        $this->headers->setHeader(HttpHeaders::DATE, (new DateTime())->format(DateTimeInterface::RFC7231));
        $this->headers->setHeader(HttpHeaders::SERVER, WebServer::getServerName());
        // TODO remove this header after we have fixed the keep-alive issue
        $this->headers->setHeader(HttpHeaders::CONNECTION, "close");

        // send the constructed data to the client
        $this->client->send($this->toString());
    }

    /**
     * Get if the response is ended
     * @return bool
     */
    public function isEnded(): bool
    {
        return $this->ended;
    }
}