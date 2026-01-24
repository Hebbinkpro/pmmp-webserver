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

namespace Hebbinkpro\WebServer\http;

use Hebbinkpro\WebServer\exception\HttpProblemException;
use Hebbinkpro\WebServer\http\server\HttpServer;
use Hebbinkpro\WebServer\http\status\HttpStatusCodes;

class HttpRequestLine
{
    public function __construct(private HttpMethod $method, private string $uriTarget, private HttpVersion $version)
    {
    }

    /**
     * @return HttpMethod
     */
    public function getMethod(): HttpMethod
    {
        return $this->method;
    }

    /**
     * @return string
     */
    public function getUriTarget(): string
    {
        return $this->uriTarget;
    }

    /**
     * @return HttpVersion
     */
    public function getVersion(): HttpVersion
    {
        return $this->version;
    }

    /**
     * Parse the request line (the first line) of an HTTP Request
     * @param string $requestLine The request line to parse
     * @return HttpRequestLine The HTTP request line
     * @throws HttpProblemException if the request was invalid
     */
    public static function parse(string $requestLine): self
    {

        // check if the request line contains exactly 2 spaces
        $count = substr_count($requestLine, " ");
        if ($count != 2) {
            throw new HttpProblemException(HttpStatusCodes::BAD_REQUEST,
                "/", // is unknown at this point
                "Malformed request line"
            );
        }

        // get the different parts
        [$methodStr, $target, $versionStr] = explode(" ", $requestLine, 3);;

        // first, validate the HTTP version
        $httpVersion = HttpVersion::parse($versionStr);
        if ($httpVersion->getMajorVersion() != HttpConstants::HTTP_VERSION_MAJOR
            || $httpVersion->getMinorVersion() != HttpConstants::HTTP_VERSION_MINOR) {

            throw new HttpProblemException(
                HttpStatusCodes::HTTP_VERSION_NOT_SUPPORTED,
                "/",
                "HTTP Version Not Supported"
            );
        }

        // ensure it is a valid token
        if (!@preg_match("/^" . HttpParsingRules::TOKEN . "$/", $methodStr)) throw new HttpProblemException(
            HttpStatusCodes::BAD_REQUEST,
            $target,
            "Malformed Request Method"
        );

        // validate the method, also gainst the servers supported methods
        $method = HttpMethod::tryFrom(strtoupper($methodStr));
        $supportedMethods = HttpServer::getInstance()->getServerInfo()->getSupportedMethods();
        if ($method === null || !in_array($method, $supportedMethods)) throw new HttpProblemException(
            HttpStatusCodes::NOT_IMPLEMENTED,
            $target,
            "Not Implemented"
        );

        // allow all visible ascii characters, proper parsing will be done later
        if (!@preg_match("/^" . HttpParsingRules::VCHAR . "+$/", $target)) {
            throw new HttpProblemException(
                HttpStatusCodes::BAD_REQUEST,
                $target,
                "Invalid Request Target");
        }

        return new self($method, $target, $httpVersion);
    }

}