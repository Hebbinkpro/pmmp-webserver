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

namespace Hebbinkpro\WebServer\http\uri\url;

use Hebbinkpro\WebServer\exception\HttpProblemException;
use Hebbinkpro\WebServer\http\HttpConstants;
use Hebbinkpro\WebServer\http\uri\AuthorityUri;
use Hebbinkpro\WebServer\http\uri\HttpRequestForm;
use Hebbinkpro\WebServer\http\uri\SchemeUri;
use Hebbinkpro\WebServer\http\uri\UriAuthority;
use Hebbinkpro\WebServer\http\uri\UriFragment;
use Hebbinkpro\WebServer\http\uri\UriPath;
use Hebbinkpro\WebServer\http\uri\UriQuery;
use Hebbinkpro\WebServer\utils\UrlUtils;

class HttpAbsoluteUrl extends HttpOriginUrl implements SchemeUri, AuthorityUri
{
    public function __construct(private string $scheme, private UriAuthority $authority, UriPath $path, UriQuery $query, UriFragment $fragment)
    {
        parent::__construct($path, $query, $fragment);
    }

    public static function parse(string $value): HttpAbsoluteUrl
    {
        /** @var array{scheme: string, host: string, path: string, user?: string, pass?: string, port?: int, query?: string, fragment?: string} $urlParts */
        $urlParts = UrlUtils::parseUrlMatches(
            $value,
            ["scheme", "host", "path"],
            ["user", "pass", "port", "query", "fragment"]
        );

        // validate scheme, should be HTTP or HTTPS
        $scheme = strtolower($urlParts["scheme"]);
        if ($scheme !== HttpConstants::HTTP_SCHEME && $scheme !== HttpConstants::HTTPS_SCHEME) {
            throw HttpProblemException::badRequest();
        }

        $path = UriPath::parse($urlParts["path"]);
        $query = UriQuery::parse($urlParts["query"] ?? "");
        $fragment = UriFragment::parse($urlParts["fragment"] ?? "");

        $host = $urlParts["host"];
        $port = $urlParts["port"] ?? ($scheme === HttpConstants::HTTP_SCHEME ? HttpConstants::DEFAULT_HTTP_PORT : HttpConstants::DEFAULT_HTTPS_PORT);
        $user = $urlParts["user"] ?? null;
        $pass = $urlParts["pass"] ?? null;
        $authority = new UriAuthority($host, intval($port), $user, $pass);

        return new HttpAbsoluteUrl($scheme, $authority, $path, $query, $fragment);
    }

    public function getRequestForm(): HttpRequestForm
    {
        return HttpRequestForm::ABSOLUTE;
    }

    public function getScheme(): string
    {
        return $this->scheme;
    }

    public function getAuthority(): UriAuthority
    {
        return $this->authority;
    }

    public function toString(): string
    {
        return $this->scheme
            . $this->authority->toString()
            . parent::toString();
    }

    /**
     * Converts the Absolute URL to an Origin URL
     * @return HttpOriginUrl
     */
    public function asOriginUrl(): HttpOriginUrl
    {
        return new HttpOriginUrl($this->getPath(), $this->getQuery(), $this->getFragment());
    }
}