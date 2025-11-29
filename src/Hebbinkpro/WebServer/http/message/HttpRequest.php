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

use Hebbinkpro\WebServer\exception\HttpProblemException;
use Hebbinkpro\WebServer\http\HttpConstants;
use Hebbinkpro\WebServer\http\HttpHeaders;
use Hebbinkpro\WebServer\http\HttpMethod;
use Hebbinkpro\WebServer\http\HttpRequestLine;
use Hebbinkpro\WebServer\http\HttpVersion;
use Hebbinkpro\WebServer\http\server\HttpServerInfo;
use Hebbinkpro\WebServer\http\status\HttpStatusCodes;
use Hebbinkpro\WebServer\http\uri\PathUri;
use Hebbinkpro\WebServer\http\uri\url\HttpUrl;
use Hebbinkpro\WebServer\http\uri\url\HttpUrlFactory;

/**
 * HTTP Request received from a client
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
     * @param HttpUrl $uri
     * @param HttpVersion $version
     * @param HttpMessageHeaders $headers
     * @param string $body
     */
    public function __construct(HttpMethod $method, HttpUrl $uri, HttpVersion $version, HttpMessageHeaders $headers, string $body)
    {
        $this->routePath = "";
        $this->method = $method;
        $this->url = $uri;
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
     * @return HttpRequest the parsed HttpRequest
     * @throws HttpProblemException when something went wrong while parsing the request
     */
    public static function parse(string $data, HttpServerInfo $serverInfo): HttpRequest
    {
        if ($data === "") throw HttpProblemException::badRequest();


        // split the data into the HEAD and BODY parts (seperated by double line break)
        $parts = explode("\r\n\r\n", trim($data), 2);

        // data does not contain a double line break
        if (sizeof($parts) == 0) throw HttpProblemException::badRequest();

        $head = $parts[0];
        if (strlen($head) > HttpConstants::MAX_TOTAL_HEADERS_LENGTH) {
            throw HttpProblemException::blankInstance(HttpStatusCodes::REQUEST_HEADER_FIELDS_TOO_LONG);
        }

        $body = $parts[1] ?? "";

        $lines = explode("\r\n", $head);
        if (sizeof($lines) == 0) throw HttpProblemException::badRequest();

        if (strlen($lines[0]) > HttpConstants::MAX_REQUEST_LINE_LENGTH) {
            throw HttpProblemException::blankInstance(HttpStatusCodes::URI_TOO_LONG);
        }

        // parse the request line and return if we get an error code
        $requestLine = self::parseRequestLine($lines[0]);
        $target = $requestLine->getUriTarget();

        $httpUrl = HttpUrlFactory::parseRequestTarget($target);

        $headers = HttpMessageHeaders::parse(array_slice($lines, 1));
        if ($headers === null) throw HttpProblemException::badRequest($target, "Malformed headers");

        if (!$headers->exists(HttpHeaders::HOST)) {
            throw HttpProblemException::badRequest($target, "Missing header: host");
        }

        $host = $headers->getHeader(HttpHeaders::HOST);
        if ($host === null) throw HttpProblemException::badRequest($target, "Missing header: host");

        // check the content limit
        $bodyLength = strlen($body);
        $contentLength = intval($headers->getHeader(HttpHeaders::CONTENT_LENGTH) ?? 0);
        if ($bodyLength > HttpConstants::MAX_BODY_SIZE || $bodyLength > $contentLength) {
            throw new HttpProblemException(HttpStatusCodes::CONTENT_TOO_LARGE, $target);
        }

        return HttpRequest::withRequestLine($requestLine, $httpUrl, $headers, $body);
    }

    /**
     * Parse the request line (the first line) of an HTTP Request
     * @param string $requestLine The request line to parse
     * @return HttpRequestLine The HTTP request line
     * @throws HttpProblemException if the request was invalid
     */
    public static function parseRequestLine(string $requestLine): HttpRequestLine
    {
        $parts = array_map("trim", explode(" ", $requestLine));
        if (sizeof($parts) != 3) throw new HttpProblemException(HttpStatusCodes::BAD_REQUEST,
            "/", // is unknown at this point
            "Malformed request line"
        );

        [$methodStr, $target, $versionStr] = $parts;
        if (strlen($target) < 1) throw new HttpProblemException(
            HttpStatusCodes::BAD_REQUEST,
            $target,
            "The Request Target cannot be empty.");

        $method = HttpMethod::tryFrom($methodStr);
        if ($method === null) throw new HttpProblemException(
            HttpStatusCodes::NOT_IMPLEMENTED,
            $target,
            "The HTTP Request Method '$methodStr' is unknown.'"
        );

        $httpVersion = HttpVersion::fromString($versionStr);
        if ($httpVersion === null) throw new HttpProblemException(
            HttpStatusCodes::HTTP_VERSION_NOT_SUPPORTED,
            $target,
            "HTTP Version '$versionStr' is malformed or not recognized."
        );

        return new HttpRequestLine($method, $target, $httpVersion);
    }

    public static function withRequestLine(HttpRequestLine $requestLine, HttpUrl $uri, HttpMessageHeaders $headers, string $body): HttpRequest
    {
        return new self($requestLine->getMethod(), $uri, $requestLine->getVersion(), $headers, $body);
    }

    /**
     * @return HttpMethod
     */
    public function getMethod(): HttpMethod
    {
        return $this->method;
    }

    public function getVersion(): HttpVersion
    {
        return $this->version;
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
     * @deprecated TODO Move to other class
     */
    public function setRoutePath(string $routePath): void
    {
        if (!$this->url instanceof PathUri) return;

        $this->routePath = $routePath;
        $this->pathParams = [];

        $path = $this->url->getPath()->getPath();
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
     * @deprecated TODO Move to other class
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
     * @deprecated TODO Move to other class
     */
    public function getSubPath(): string
    {
        if (!$this->url instanceof PathUri) return "";
        $path = $this->url->getPath();

        if (sizeof($path->getPath()) == 0) return $path->toString();

        // a/b/c => c
        $parts = substr_count($this->routePath, "/");

        $subPath = $path->toString();
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
     * Get all path params
     *
     * Path params are defined by :param in a Route path. (e.g. /my/path/:param, where :param is the path parameter
     * @return array<string, string>
     * @deprecated TODO Move to other class
     */
    public function getPathParams(): array
    {
        return $this->pathParams;
    }

    /**
     * Get a path param by its name
     * @param string $name the name of the path param
     * @return string|null null when the param does not exist.
     * @deprecated TODO Move to other class
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
     * @deprecated TODO why does this exist?
     */
    public function isCompleted(): bool
    {
        return $this->completed;
    }
}