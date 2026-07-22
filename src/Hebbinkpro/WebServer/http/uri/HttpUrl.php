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

namespace Hebbinkpro\WebServer\http\uri;

use Hebbinkpro\WebServer\exception\HttpProblemException;
use Hebbinkpro\WebServer\http\HttpConstants;
use Hebbinkpro\WebServer\http\server\HttpServerInfo;
use Hebbinkpro\WebServer\http\status\HttpStatusCodes;

/**
 * An HTTP Uri which contains all url data
 * @deprecated Moving to http/uri/url for improved parsing
 */
class HttpUrl implements UriElement
{

    /**
     * @param string $scheme
     * @param UriAuthority|null $authority
     * @param UriPath $path
     * @param UriQuery $query
     * @param UriFragment $fragment
     * @param HttpRequestForm $requestForm
     */
    public function __construct(private string $scheme, private ?UriAuthority $authority, private UriPath $path, private UriQuery $query, private UriFragment $fragment, private HttpRequestForm $requestForm)
    {
    }

    /**
     * Parse a request target to a URL
     * @param HttpServerInfo $serverInfo
     * @param string $host
     * @param string $target
     * @return HttpUrl|null
     */
    public static function parseRequestTarget(HttpServerInfo $serverInfo, string $host, string $target): ?HttpUrl
    {
        $address = $serverInfo->getAddress($host);

        // astrix-form
        if ($target === "*") return self::parse($address, HttpRequestForm::ASTERISK);

        // origin-form
        if (str_starts_with($target, "/")) {
            return self::parse($address . $target, HttpRequestForm::ORIGIN);
        }

        // absolute-form
        $scheme = parse_url($target, PHP_URL_SCHEME);
        if ($scheme === $serverInfo->getScheme()) {
            return self::parse($target, HttpRequestForm::ABSOLUTE);
        }

        // authority-form, only for CONNECT which is not supported
        if ($target === $host) {
            return self::parse($address, HttpRequestForm::AUTHORITY);
        }

        return null;
    }

    /**
     * Parse the url
     * @param string $value
     * @param HttpRequestForm $requestForm
     * @return HttpUrl
     */
    public static function parse(string $value, HttpRequestForm $requestForm = HttpRequestForm::ORIGIN): HttpUrl
    {
        $urlParts = parse_url($value);
        if ($urlParts === false || !is_array($urlParts)) throw new HttpProblemException(
            HttpStatusCodes::BAD_REQUEST,
            "about:blank"
        );

        // get all data from the url
        $scheme = strtolower($urlParts["scheme"] ?? HttpConstants::HTTP_SCHEME);

        $path = UriPath::parse($urlParts["path"] ?? "");
        $query = UriQuery::parse($urlParts["query"] ?? "");
        $fragment = UriFragment::parse($urlParts["fragment"] ?? "");

        $authority = null;
        if (isset($urlParts["host"])) {
            $host = $urlParts["host"];
            $port = $urlParts["port"] ?? ($scheme === HttpConstants::HTTP_SCHEME ? HttpConstants::DEFAULT_HTTP_PORT : HttpConstants::DEFAULT_HTTPS_PORT);
            $user = $urlParts["user"] ?? null;
            $pass = $urlParts["pass"] ?? null;
            $authority = new UriAuthority($host, intval($port), $user, $pass);
        }


        return new HttpUrl($scheme, $authority, $path, $query, $fragment, $requestForm);
    }

    /**
     * Get the HTTP scheme (http or https)
     * @return string
     */
    public function getScheme(): string
    {
        return $this->scheme;
    }

    /**
     * Get a string representation of this url
     * @return string
     */
    public function toString(): string
    {
        return $this->scheme . ":"
            . $this->authority?->toString()
            . $this->getOriginUrl();
    }

    public function getOriginUrl(): string
    {
        return $this->path->toString()
            . $this->query->toString()
            . $this->fragment->toString();
    }

    /**
     * @return UriPath
     */
    public function getPath(): UriPath
    {
        return $this->path;
    }

    /**
     * @return UriAuthority|null
     */
    public function getAuthority(): ?UriAuthority
    {
        return $this->authority;
    }

    /**
     * Get the hostname
     * @return string
     */
    public function getHost(): string
    {
        return $this->authority?->getHost() ?? "localhost";
    }

    /**
     * Get the fragment
     * @return UriFragment
     */
    public function getFragment(): UriFragment
    {
        return $this->fragment;
    }

    /**
     * Get a query by its name
     * @param string $name
     * @return string|null
     * @deprecated since v0.6.0, use <code>getQuery()->getValue($name)</code> instead
     */
    public function getQueryParam(string $name): ?string
    {
        return $this->getQuery()->getValue($name);
    }

    /**
     * Get the query
     * @return UriQuery
     */
    public function getQuery(): UriQuery
    {
        return $this->query;
    }

    /**
     * @return int
     */
    public function getPort(): int
    {
        return $this->authority?->getPort() ?? 80;
    }

    /**
     * @return HttpRequestForm
     */
    public function getRequestForm(): HttpRequestForm
    {
        return $this->requestForm;
    }
}