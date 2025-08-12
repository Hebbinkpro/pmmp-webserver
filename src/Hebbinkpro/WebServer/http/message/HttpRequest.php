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

namespace Hebbinkpro\WebServer\http\message;

use Hebbinkpro\WebServer\http\HttpConstants;
use Hebbinkpro\WebServer\http\HttpHeaders;
use Hebbinkpro\WebServer\http\HttpMethod;
use Hebbinkpro\WebServer\http\HttpURI;
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
    private HttpURI $uri;
    private HttpVersion $version;
    private HttpMessageHeaders $headers;
    private string $body;
    /** @var array<string, string> */
    private array $pathParams;
    private bool $completed;

    /**
     * @param HttpMethod $method
     * @param HttpURI $uri
     * @param HttpVersion $version
     * @param HttpMessageHeaders $headers
     * @param string $body
     */
    public function __construct(HttpMethod $method, HttpURI $uri, HttpVersion $version, HttpMessageHeaders $headers, string $body)
    {
        $this->routePath = "";
        $this->method = $method;
        $this->uri = $uri;
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
     * @return int 0 if not completed, 1 if completed, 2 if the content limit is exceeded
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
    public static function parse(string $data, HttpServerInfo $serverInfo): int|HttpRequest
    {
        if ($data === "") return HttpStatusCodes::REQUEST_HEADER_FIELDS_TOO_LONG;

        // split the data into the HEAD and BODY parts (seperated by double line break)
        $parts = explode("\r\n\r\n", trim($data), 2);

        // data does not contain a double line break, so the header is too long
        if (sizeof($parts) == 0) return HttpStatusCodes::REQUEST_HEADER_FIELDS_TOO_LONG;

        $head = $parts[0];
        $body = $parts[1] ?? "";

        $lines = explode("\r\n", $head);
        if (sizeof($lines) == 0) return HttpStatusCodes::BAD_REQUEST;

        if (strlen($lines[0]) > HttpConstants::MAX_REQUEST_LINE_LENGTH) return HttpStatusCodes::URI_TOO_LONG;

        // parse the request line and return if we get an error code
        $res = self::parseRequestLine($lines[0]);
        if (is_int($res)) return $res;
        [$method, $target, $httpVersion] = $res;

        $headers = HttpMessageHeaders::parse(array_slice($lines, 1));
        if ($headers === null || !$headers->exists(HttpHeaders::HOST)) return HttpStatusCodes::BAD_REQUEST;

        $scheme = $serverInfo->isSslEnabled() ? HttpConstants::HTTPS_SCHEME : HttpConstants::HTTP_SCHEME;

        $host = $headers->getHeader(HttpHeaders::HOST);
        if ($host === null) return HttpStatusCodes::BAD_REQUEST;

        $uri = HttpURI::parseRequestTarget($scheme, $host, $target);
        if ($uri === null) return HttpStatusCodes::BAD_REQUEST;

        // check the content limit
        $bodyLength = strlen($body);
        $contentLength = intval($headers->getHeader(HttpHeaders::CONTENT_LENGTH) ?? 0);
        if ($bodyLength > $contentLength) return HttpStatusCodes::CONTENT_TOO_LARGE;

        return new HttpRequest($method, $uri, $httpVersion, $headers, $body);
    }

    /**
     * Parse the request line (the first line) of an HTTP Request
     * @return array<HttpMethod, string, HttpVersion>|int The HTTP Method, target and HTTP Version or an integer with an error status code
     */
    public static function parseRequestLine(string $requestLine): array|int
    {
        $parts = explode(" ", $requestLine);
        if (sizeof($parts) != 3) return HttpStatusCodes::BAD_REQUEST;

        $method = HttpMethod::tryFrom($parts[0]);
        if ($method === null) return HttpStatusCodes::NOT_IMPLEMENTED;

        $target = $parts[1];
        if (strlen($target) < 1) return HttpStatusCodes::BAD_REQUEST;

        $httpVersion = HttpVersion::fromString($parts[2]);
        if ($httpVersion === null) return HttpStatusCodes::HTTP_VERSION_NOT_SUPPORTED;

        return [$method, $target, $httpVersion];
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

        $path = explode("/", $this->uri->getPath());
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
     * Get the uri path without the route path
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
     * @return HttpURI
     */
    public function getURL(): HttpURI
    {
        return $this->uri;
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
     * @return array<string, string>
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