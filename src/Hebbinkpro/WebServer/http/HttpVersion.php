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

namespace Hebbinkpro\WebServer\http;

use Hebbinkpro\WebServer\exception\HttpProblemException;
use Hebbinkpro\WebServer\http\status\HttpStatusCodes;
use Hebbinkpro\WebServer\utils\RegexUtils;

/**
 * HTTP Version to identify the HTTP request version of the client and to use in the response of the server.
 */
readonly class HttpVersion
{

    /**
     * @param int<0,max> $major major HTTP version
     * @param int<0,max> $minor minor HTTP version
     */
    public function __construct(private int $major, private int $minor)
    {
    }

    /**
     * Decode an http version
     * @param string $version
     * @return HttpVersion
     * @throws HttpProblemException if the version string was malformed
     */
    public static function parse(string $version): HttpVersion
    {
        // invalid http version
        if (!RegexUtils::has_preg_match("/^" . HttpParsingRules::HTTP_VERSION . "$/", $version)) {
            throw new HttpProblemException(HttpStatusCodes::BAD_REQUEST, "Malformed HTTP Version");
        }

        // remove the HTTP/ prefix
        $httpVersion = substr($version, 5);

        // get major and minor versions, since it passed the preg_match, we are sure that they are digits in [0-9]
        [$major, $minor] = explode(".", $httpVersion);

	    $major = intval($major);
	    $minor = intval($minor);
	    if ($major < 0 || $minor < 0) {
		    throw new HttpProblemException(HttpStatusCodes::BAD_REQUEST, "Invalid HTTP Version");
	    }

	    return new HttpVersion($major, $minor);
    }

    /**
     * @return int<0,max>
     */
    public function getMajorVersion(): int
    {
        return $this->major;
    }

    /**
     * @return int<0,max>
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

	/**
	 * Compare two HTTP versions
	 * @param HttpVersion $version
	 * @return bool true if both the major and minor versions are the same
	 */
	public function equals(HttpVersion $version): bool
	{
		return $this->major === $version->major && $this->minor === $version->minor;
	}
}