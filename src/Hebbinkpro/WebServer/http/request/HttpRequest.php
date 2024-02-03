<?php

namespace Hebbinkpro\WebServer\http\request;

use Hebbinkpro\WebServer\http\header\HttpHeaderNames;
use Hebbinkpro\WebServer\http\HttpUrl;
use Hebbinkpro\WebServer\http\HttpVersion;
use Hebbinkpro\WebServer\route\Route;

/**
 * HTTP Request send by the client
 */
class HttpRequest
{
    private ?Route $route;
    private string $method;
    private HttpUrl $url;
    private HttpVersion $version;
    private HttpRequestHeaders $headers;
    private string $body;

    /**
     * @param string $method
     * @param HttpUrl $url
     * @param HttpVersion $version
     * @param HttpRequestHeaders $headers
     * @param string $body
     */
    public function __construct(string $method, HttpUrl $url, HttpVersion $version, HttpRequestHeaders $headers, string $body)
    {
        $this->route = null;
        $this->method = $method;
        $this->url = $url;
        $this->version = $version;
        $this->headers = $headers;
        $this->body = $body;
    }

    /**
     * Decode an HTTP Request
     * @param string $data
     * @return HttpRequest|null
     */
    public static function fromString(string $data): ?HttpRequest
    {
        $parts = explode(PHP_EOL, $data);
        if (empty($parts)) return null;

        $httpData = explode(" ", array_shift($parts));
        if (count($httpData) < 3) return null;

        $method = strtoupper($httpData[0]);
        $path = $httpData[1];
        $httpVersion = HttpVersion::fromString($httpData[2]);
        $body = "";

        // validate the request head
        if (!HttpRequestMethod::exists($method) || !str_contains($path, "/") || $httpVersion === null) return null;

        // search for the first empty line, this is the separator between the headers and body
        $endHeadersIndex = array_key_last($parts);

        // there is a body after the last header
        if (in_array("", $parts) && array_key_exists($endHeadersIndex + 1, $parts)) {
            $body = implode(PHP_EOL, array_slice($parts, $endHeadersIndex + 1));
            $endHeadersIndex = array_search("", $parts);
            array_splice($parts, 0, $endHeadersIndex);
        }

        $headers = HttpRequestHeaders::fromString(implode(PHP_EOL, array_slice($parts, 0, $endHeadersIndex)));
        if (!$headers->exists(HttpHeaderNames::HOST)) return null;

        $url = HttpUrl::parse("http://" . $headers->get(HttpHeaderNames::HOST) . $path);

        return new HttpRequest($method, $url, $httpVersion, $headers, $body);
    }

    /**
     * @return Route|null
     */
    public function getRoute(): ?Route
    {
        return $this->route;
    }

    /**
     * @param Route|null $route
     */
    public function setRoute(?Route $route): void
    {
        $this->route = $route;
    }

    /**
     * @return string
     */
    public function getMethod(): string
    {
        return $this->method;
    }

    /**
     * @return HttpUrl
     */
    public function getURL(): HttpUrl
    {
        return $this->url;
    }

    /**
     * Get a Query Param by its name
     * @param string $name
     * @return string|null
     */
    public function getQueryParam(string $name): ?string
    {
        return $this->url->getQuery()[$name] ?? null;
    }

    /**
     * Get all path params
     * @return array
     */
    public function getPathParams(): array
    {
        if ($this->route === null) return [];

        $path = $this->url->getPath();
        $params = [];

        foreach ($this->route->getPath() as $i => $value) {
            // the path is shorter than the route path
            if (!array_key_exists($i, $path)) break;

            // given index is a path param
            if (str_starts_with($value, ":")) {
                $name = substr($value, 1);
                $params[$name] = $path[$i];
            }
        }

        return $params;
    }

    /**
     * @return HttpVersion
     */
    public function getHTTPVersion(): HttpVersion
    {
        return $this->version;
    }

    /**
     * @return HttpRequestHeaders
     */
    public function getHeaders(): HttpRequestHeaders
    {
        return $this->headers;
    }

    /**
     * @return string
     */
    public function getBody(): string
    {
        return $this->body;
    }
}