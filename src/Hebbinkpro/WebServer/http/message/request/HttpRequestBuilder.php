<?php
/*
 * MIT License
 *
 * Copyright (c) 2026 Hebbinkpro
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

namespace Hebbinkpro\WebServer\http\message\request;

use Hebbinkpro\WebServer\exception\HttpProblemException;
use Hebbinkpro\WebServer\http\HttpConstants;
use Hebbinkpro\WebServer\http\HttpMethod;
use Hebbinkpro\WebServer\http\HttpVersion;
use Hebbinkpro\WebServer\http\message\header\HttpHeaderBuilder;
use Hebbinkpro\WebServer\http\server\HttpClient;
use Hebbinkpro\WebServer\http\status\HttpStatusCodes;
use Hebbinkpro\WebServer\http\uri\url\HttpUrl;

class HttpRequestBuilder implements Request
{
    private HttpMethod $method;
    private HttpUrl $target;
    private HttpVersion $httpVersion;
    private HttpHeaderBuilder $header;
    private mixed $body;

    public function __construct()
    {
        $this->body = null;
    }

    /**
     * @return HttpMethod
     */
    public function getMethod(): HttpMethod
    {
        return $this->method;
    }

    /**
     * @param HttpMethod $method
     * @return HttpRequestBuilder
     */
    public function setMethod(HttpMethod $method): self
    {
        $this->method = $method;
        return $this;
    }

    /**
     * @return HttpUrl
     */
    public function getTarget(): HttpUrl
    {
        return $this->target;
    }

    /**
     * @param HttpUrl $target
     * @return HttpRequestBuilder
     */
    public function setTarget(HttpUrl $target): self
    {
        $this->target = $target;
        return $this;
    }

    /**
     * @return HttpVersion
     */
    public function getHttpVersion(): HttpVersion
    {
        return $this->httpVersion;
    }

    /**
     * @param HttpVersion $httpVersion
     * @return HttpRequestBuilder
     * @throws HttpProblemException if the HTTP version is not supported
     */
    public function setHttpVersion(HttpVersion $httpVersion): self
    {
        $this->httpVersion = $httpVersion;

        if ($this->httpVersion->getMajorVersion() != HttpConstants::HTTP_VERSION_MAJOR
            || $this->httpVersion->getMinorVersion() != HttpConstants::HTTP_VERSION_MINOR) {

            throw new HttpProblemException(
                HttpStatusCodes::HTTP_VERSION_NOT_SUPPORTED,
                "/",
                "Unsupported HTTP Version"
            );
        }


        return $this;
    }

    /**
     * @return HttpHeaderBuilder
     */
    public function getHeader(): HttpHeaderBuilder
    {
        return $this->header;
    }

    /**
     * @param HttpHeaderBuilder $header
     * @return HttpRequestBuilder
     */
    public function setHeader(HttpHeaderBuilder $header): self
    {
        $this->header = $header;
        return $this;
    }

    public function build(HttpClient $client): HttpRequest
    {
        $header = $this->header->build();
        return new HttpRequest($client, $this->method, $this->target, $this->httpVersion, $header, $this->body);
    }

    /**
     * @return mixed
     */
    public function getBody(): mixed
    {
        return $this->body;
    }

    /**
     * @param mixed $body
     * @return HttpRequestBuilder
     */
    public function setBody(mixed $body): self
    {
        $this->body = $body;
        return $this;
    }

}