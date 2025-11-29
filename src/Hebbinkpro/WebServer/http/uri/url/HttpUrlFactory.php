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
use Hebbinkpro\WebServer\http\uri\PathUri;

class HttpUrlFactory
{
    /**
     * Parse a request target to a URL
     * @param string $target
     * @return HttpUrl
     */
    public static function parseRequestTarget(string $target): HttpUrl
    {
        if ($target === "*") {
            return new HttpAsteriskUrl();
        } elseif (str_starts_with($target, "/")) {
            return HttpOriginUrl::parse($target);
        } elseif (filter_var($target, FILTER_VALIDATE_URL, FILTER_FLAG_PATH_REQUIRED) !== false) {
            return HttpAbsoluteUrl::parse($target);
        } elseif (preg_match("/^([a-zA-Z0-9.-]+|\[[a-fA-F0-9:]+]):\d+$/", $target) !== false) {
            return HttpAuthorityUrl::parse($target);
        }

        throw HttpProblemException::badRequest();
    }

    /**
     * Convert any path URI to an origin URL
     * @param PathUri $uri
     * @return HttpOriginUrl
     */
    public static function pathUriAsOriginUrl(PathUri $uri): HttpOriginUrl
    {
        return new HttpOriginUrl($uri->getPath(), $uri->getQuery(), $uri->getFragment());
    }
}