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

namespace Hebbinkpro\WebServer\http;

/**
 * HTTP Version to identify the HTTP request version of the client and to use in the response of the server.
 */
class HttpVersion
{
    public const DEFAULT_MAJOR = 1;
    public const DEFAULT_MINOR = 1;

    /**
     * @param int $major major HTTP version
     * @param int $minor minor HTTP version
     */
    public function __construct(private readonly int $major, private readonly int $minor)
    {
    }

    /**
     * Decode an http version
     * @param string $version
     * @return HttpVersion|null
     */
    public static function fromString(string $version): ?HttpVersion
    {
        if ($version === "undefined") return self::getDefault();

        // invalid http request
        if (!str_starts_with($version, "HTTP/")) return null;

        // get major and minor versions
        [$major, $minor] = explode(".", substr($version, 5));

        // check if the integers are valid
        if (!ctype_digit($major) || !ctype_digit($minor)) return null;


        return new HttpVersion(intval($major), intval($minor));
    }

    /**
     * Get the default HTTP version
     * @return HttpVersion HTTP/1.1
     */
    public static function getDefault(): HttpVersion
    {
        return new HttpVersion(self::DEFAULT_MAJOR, self::DEFAULT_MINOR);
    }

    /**
     * @return int
     */
    public function getMajorVersion(): int
    {
        return $this->major;
    }

    /**
     * @return int
     */
    public function getMinorVersion(): int
    {
        return $this->minor;
    }

    /**
     * Get the encoded HTTP version
     * @return string HTTP/major.minor
     */
    public function toString(): string
    {
        return "HTTP/" . $this->major . "." . $this->minor;
    }
}