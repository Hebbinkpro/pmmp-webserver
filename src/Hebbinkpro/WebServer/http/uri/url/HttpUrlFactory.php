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

namespace Hebbinkpro\WebServer\http\uri\url;

use Hebbinkpro\WebServer\exception\HttpProblemException;
use Hebbinkpro\WebServer\http\uri\PathUri;
use Hebbinkpro\WebServer\utils\UrlUtils;

class HttpUrlFactory
{
    /**
     * Parse a request target to a URL
     * @param string $target
     * @return HttpUrl
     */
    public static function parseRequestTarget(string $target): HttpUrl
    {
        if ($target === "*") return new HttpAsteriskUrl();

        // some very simple check to determine which type of URL we should parse
        $targetParts = UrlUtils::parseUrl($target);
        if (array_key_exists("scheme", $targetParts)) return HttpAbsoluteUrl::parse($target);
        if (array_key_exists("path", $targetParts)) return HttpOriginUrl::parse($target);
        if (array_key_exists("host", $targetParts)) return HttpAuthorityUrl::parse($target);

        throw HttpProblemException::badRequest();
    }

    /**
     * Convert any path URI to an origin URL
     * @param PathUri $uri
     * @return HttpOriginUrl
     */
    public static function pathUriAsOrigin(PathUri $uri): HttpOriginUrl
    {
        return new HttpOriginUrl($uri->getPath(), $uri->getQuery(), $uri->getFragment());
    }
}