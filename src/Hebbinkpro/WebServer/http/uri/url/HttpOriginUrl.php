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

namespace Hebbinkpro\WebServer\http\uri\url;

use Hebbinkpro\WebServer\http\uri\HttpRequestForm;
use Hebbinkpro\WebServer\http\uri\PathUri;
use Hebbinkpro\WebServer\http\uri\UriFragment;
use Hebbinkpro\WebServer\http\uri\UriPath;
use Hebbinkpro\WebServer\http\uri\UriQuery;
use Hebbinkpro\WebServer\utils\UrlUtils;

class HttpOriginUrl implements HttpUrl, PathUri
{
    public function __construct(private UriPath $path, private UriQuery $query, private UriFragment $fragment)
    {
    }

    public static function parse(string $value): HttpOriginUrl
    {
        $urlParts = UrlUtils::parseUrlMatches(
            $value,
            ["path"],
            ["query", "fragment"]
        );

        $path = UriPath::parse($urlParts["path"] ?? "");
        $query = UriQuery::parse($urlParts["query"] ?? "");
        $fragment = UriFragment::parse($urlParts["fragment"] ?? "");

        return new HttpOriginUrl($path, $query, $fragment);
    }

    public function getRequestForm(): HttpRequestForm
    {
        return HttpRequestForm::ORIGIN;
    }

    public function getPath(): UriPath
    {
        return $this->path;
    }

    public function toString(): string
    {
        return $this->path->toString()
            . $this->query->toString()
            . $this->fragment->toString();
    }

    public function getQuery(): UriQuery
    {
        return $this->query;
    }

    public function getFragment(): UriFragment
    {
        return $this->fragment;
    }
}