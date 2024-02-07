<?php

namespace Hebbinkpro\WebServer\http\message;

use Hebbinkpro\WebServer\http\HttpHeaders;
use Hebbinkpro\WebServer\http\HttpMessageHeaders;
use Hebbinkpro\WebServer\http\HttpMethod;
use Hebbinkpro\WebServer\http\HttpUrl;
use Hebbinkpro\WebServer\http\HttpVersion;
use Hebbinkpro\WebServer\http\server\HttpServerInfo;
use Hebbinkpro\WebServer\http\status\HttpStatusCodes;

/**
 * HTTP Request send by the client
 */
class HttpRequest implements HttpMessage
{
    private string $routePath;
    private HttpMethod $method;
    private HttpUrl $url;
    private HttpVersion $version;
    private HttpMessageHeaders $headers;
    private string $body;
    /** @var array<string, string> */
    private array $pathParams;
    private bool $completed;

    /**
     * @param HttpMethod $method
     * @param HttpUrl $url
     * @param HttpVersion $version
     * @param HttpMessageHeaders $headers
     * @param string $body
     */
    public function __construct(HttpMethod $method, HttpUrl $url, HttpVersion $version, HttpMessageHeaders $headers, string $body)
    {
        $this->routePath = "";
        $this->method = $method;
        $this->url = $url;
        $this->version = $version;
        $this->headers = $headers;
        $this->body = "";
        $this->pathParams = [];
        $this->completed = false;
        $this->appendData($body);
    }

    /**
     * Append data to the body
     * @param string $data
     * @return int 0 if not completed, 1 if completed, 2 if content limit is exceeded
     */
    public function appendData(string $data): int
    {
        $this->body .= $data;

        $bodyLength = strlen($this->body);
        $contentLength = intval($this->headers->getHeader(HttpHeaders::CONTENT_LENGTH) ?? 0);

        // content limit is exceeded
        if ($bodyLength > $contentLength) return 2;
        // request is only completed when the body has the same length
        else if ($bodyLength == $contentLength) {
            $this->completed = true;
            return 1;
        }

        return 0;
    }

    /**
     * Decode an HTTP Request
     * @param string $data
     * @param HttpServerInfo $serverInfo
     * @return HttpRequest|int the HttpRequest or a 4xx status code
     */
    public static function decode(string $data, HttpServerInfo $serverInfo): int|HttpRequest
    {
        // split the data into the HEAD and BODY parts (seperated by double line break)
        $parts = explode("\r\n\r\n", trim($data), 2);

        // data does not contain a double line break, so the header is too long
        if (!$parts || sizeof($parts) == 0) return HttpStatusCodes::REQUEST_HEADER_FIELDS_TOO_LONG;

        $head = $parts[0];
        $body = $parts[1] ?? "";

        $lines = explode("\r\n", $head);
        if (sizeof($lines) == 0) return HttpStatusCodes::BAD_REQUEST;

        // get first line of the data: METHOD PATH HTTP/1.1
        $httpData = explode(" ", $lines[0]);
        if (sizeof($httpData) != 3) return HttpStatusCodes::BAD_REQUEST;

        // get the head, path and version
        $method = HttpMethod::tryFrom($httpData[0]);
        $path = trim($httpData[1], "/");
        $httpVersion = HttpVersion::fromString($httpData[2]);

        // validate the request head
        if ($method === null) return HttpStatusCodes::METHOD_NOT_ALLOWED;
        else if ($httpVersion === null) return HttpStatusCodes::HTTP_VERSION_NOT_SUPPORTED;

        $headers = HttpMessageHeaders::fromStringArray(array_slice($lines, 1));
        if (!$headers->exists(HttpHeaders::HOST)) return HttpStatusCodes::BAD_REQUEST;

        $url = HttpUrl::parse(($serverInfo->isSslEnabled() ? "https" : "http") . "://" . $headers->getHeader(HttpHeaders::HOST) . "/" . $path);

        // check the content limit
        $bodyLength = strlen($body);
        $contentLength = intval($headers->getHeader(HttpHeaders::CONTENT_LENGTH) ?? 0);
        if ($bodyLength > $contentLength) return HttpStatusCodes::CONTENT_TOO_LARGE;

        return new HttpRequest($method, $url, $httpVersion, $headers, $body);
    }

    /**
     * Get the path over which the request was routed
     * @return string
     */
    public function getRoutePath(): string
    {
        return $this->routePath;
    }

    /**
     * Set the route that will handle this request
     * @param string $routePath
     */
    public function setRoutePath(string $routePath): void
    {
        $this->routePath = $routePath;
        $this->pathParams = [];

        $path = explode("/", $this->url->getPath());
        $routePath = explode("/", $routePath);

        foreach ($routePath as $i => $value) {
            // the path is shorter than the route path for some reason
            if (!array_key_exists($i, $path)) break;

            // given index is a path param
            if (str_starts_with($value, ":")) {
                $name = substr($value, 1);
                $this->pathParams[$name] = $path[$i];
            }
        }
    }

    /**
     * Appends the route path at the end of the current route path.
     * If the current route path ends with *, this will be replaced.
     * @param string $routePath
     * @return void
     */
    public function appendRoutePath(string $routePath): void
    {
        $current = $this->routePath;
        if (str_ends_with($current, "*")) $current = substr($current, 0, -1);

        $this->setRoutePath($current . $routePath);
    }


    /**
     * Get the url path without the route path
     * @return string
     */
    public function getSubPath(): string
    {
        if (strlen($this->routePath) == 0) return $this->getURL()->getPath();

        // a/b/c => c
        $parts = substr_count($this->routePath, "/");

        $subPath = $this->getURL()->getPath();
        for ($i = 0; $i < $parts; $i++) {
            $idx = strpos($subPath, "/");
            if ($idx === false) break;

            $subPath = substr($subPath, $idx + 1);
        }

        return $subPath;
    }

    /**
     * @return HttpUrl
     */
    public function getURL(): HttpUrl
    {
        return $this->url;
    }

    /**
     * @return HttpMethod
     */
    public function getMethod(): HttpMethod
    {
        return $this->method;
    }

    /**
     * Get all path params
     *
     * Path params are defined by :param in a Route path. (e.g. /my/path/:param, where :param is the path parameter
     * @return array
     */
    public function getPathParams(): array
    {
        return $this->pathParams;
    }

    /**
     * Get a path param by its name
     * @param string $name the name of the path param
     * @return string|null null when the param does not exist.
     */
    public function getPathParam(string $name): ?string
    {
        return $this->pathParams[$name] ?? null;
    }

    public function getHeaders(): HttpMessageHeaders
    {
        return $this->headers;
    }

    public function getBody(): string
    {
        return $this->body;
    }

    /**
     * Get if the message body is completed
     * @return bool
     */
    public function isCompleted(): bool
    {
        return $this->completed;
    }

    public function getVersion(): HttpVersion
    {
        return $this->version;
    }
}