<?php

namespace Hebbinkpro\WebServer\http;

use DateTime;
use DateTimeInterface;
use Hebbinkpro\WebServer\http\header\HttpHeaderNames;
use Hebbinkpro\WebServer\http\header\HttpHeaders;
use Hebbinkpro\WebServer\http\status\HttpStatus;
use Hebbinkpro\WebServer\WebClient;
use pocketmine\VersionInfo;
use function mb_strlen;

class HttpResponse
{
    private WebClient $client;
    private HttpStatus $status;
    private HttpVersion $version;
    private HttpHeaders $headers;
    private string $body;
    private bool $ended;

    public function __construct(WebClient $client)
    {
        $this->client = $client;
        $this->status = HttpStatus::get(200);
        $this->version = HttpVersion::get();
        $this->headers = new HttpHeaders();
        $this->body = "";
        $this->ended = false;

        // set some default headers
        $this->headers->set(HttpHeaderNames::CONTENT_TYPE, "text/html; charset=utf-8");
        $this->headers->set(HttpHeaderNames::DATE, (new DateTime())->format(DateTimeInterface::RFC7231));
        $this->headers->set(HttpHeaderNames::SERVER, VersionInfo::NAME . " " . VersionInfo::BASE_VERSION);
    }

    public static function notFound(WebClient $client): HttpResponse
    {
        $res = new HttpResponse($client);
        $res->setStatus(404);
        $res->getHeaders()->set(HttpHeaderNames::CONNECTION, "close");
        $res->send($res->getStatus(), "text/plain");
        return $res;
    }

    /**
     * @return HttpHeaders
     */
    public function getHeaders(): HttpHeaders
    {
        return $this->headers;
    }

    /**
     * Send data as html
     * @param string $data
     * @param string $contentType content type of the data
     * @return void
     */
    public function send(string $data, string $contentType = "text/html"): void
    {
        $this->headers->set(HttpHeaderNames::CONTENT_TYPE, $contentType);
        $this->body = $data;
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
        if (is_int($status)) $status = HttpStatus::get($status);
        $this->status = $status;
    }

    /**
     * @return WebClient
     */
    public function getClient(): WebClient
    {
        return $this->client;
    }

    /**
     * @return HttpVersion
     */
    public function getVersion(): HttpVersion
    {
        return $this->version;
    }

    /**
     * @return string
     */
    public function getBody(): string
    {
        return $this->body;
    }

    /**
     * Send the contents of a file.
     * Sets the Content-Type if the file extension is recognized.
     * - HTML: text/html
     * - JSON: application/json
     * - etc...
     * @param string $fileName
     * @return void
     */
    public function sendFile(string $fileName): void
    {
        $parts = explode(".", $fileName) ?? [];
        $fileExtension = end($parts);

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

        $this->headers->set(HttpHeaderNames::CONTENT_TYPE, $contentType);
        $this->body = file_get_contents($fileName);
    }

    /**
     * Send an array as json
     * This sets the content-type header to: application/json
     * @param array $data
     * @return void
     */
    public function json(array $data): void
    {
        $this->headers->set(HttpHeaderNames::CONTENT_TYPE, "application/json");
        $this->body = json_encode($data);
    }

    /**
     * Send plain text
     * @param string $data
     * @return void
     */
    public function text(string $data): void
    {
        $this->headers->set(HttpHeaderNames::CONTENT_TYPE, "text/plain");
        $this->body = $data;
    }

    /**
     * End the response, this will send the response to the client.
     * After a response is ended, it is sent immediately and cannot be sent again.
     * @return void
     */
    public function end(): void
    {
        if ($this->ended) return;

        $this->ended = true;
        // set the content length
        $this->headers->set(HttpHeaderNames::CONTENT_LENGTH, mb_strlen($this->body, '8bit'));

        // the body is empty and the status code is 200
        if (empty($this->body) && $this->status === HttpStatus::get(200)) {
            // change the status code to 204 No Content, because it's successful but no content (body) is given
            $this->setStatus(HttpStatus::get(204));
        }

        // set first line
        $data = $this->version . " " . $this->status . PHP_EOL;
        // set the headers including the empty line that breaks the headers and body
        $data .= $this->headers . PHP_EOL;
        // set the body
        $data .= $this->body . PHP_EOL;

        // send the constructed data to the client
        $this->client->send($data);
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