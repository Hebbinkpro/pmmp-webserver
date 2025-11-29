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

namespace Hebbinkpro\WebServer\utils;

use Hebbinkpro\WebServer\exception\HttpProblemException;

final class UrlUtils
{
    /**
     * Parse a URL and ensures that the returned array only contains required and optional parts.
     * @param string $url the URL to parse
     * @param string[] $required list of required URL parts
     * @param string[] $optional list of optional URL parts
     * @param bool $strict if true, URLs may not contain other parts then listed in required and optional
     * @return array{scheme?: string, host?: string, port?: int, user?: string, pass?: string, query?: string, path?: string, fragment?: string}
     * @throws HttpProblemException if the url is invalid
     */
    public static function parseUrlMatches(string $url, array $required = [], array $optional = [], bool $strict = false): array
    {
        $urlParts = self::parseUrl($url);

        $requiredKeys = array_flip($required);
        $combinedKeys = array_merge($requiredKeys, array_flip($optional));

        // if strict, throw error when there are other keys present in the urlParts then listed in required and optional
        if ($strict && sizeof(array_diff_key($urlParts, $combinedKeys)) > 0) {
            throw HttpProblemException::badRequest();
        }

        $matches = array_intersect_key($urlParts, $combinedKeys);
        if (sizeof(array_diff($matches, $requiredKeys)) > 0) {
            throw HttpProblemException::badRequest();
        }

        return $matches;
    }

    /**
     * Wrapper around PHPs parse_url($url) function that throws an exception when parsing was unsuccessful
     * @param string $url
     * @return array{scheme?: string, host?: string, port?: int, user?: string, pass?: string, query?: string, path?: string, fragment?: string}
     * @throws HttpProblemException if the url is invalid
     */
    public static function parseUrl(string $url): array
    {
        $urlParts = @parse_url($url);
        if (!is_array($urlParts)) {
            throw HttpProblemException::badRequest();
        }

        return $urlParts;
    }
}