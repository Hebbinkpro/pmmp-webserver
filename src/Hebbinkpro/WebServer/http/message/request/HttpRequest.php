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

use Hebbinkpro\WebServer\http\HttpMethod;
use Hebbinkpro\WebServer\http\HttpVersion;
use Hebbinkpro\WebServer\http\message\header\HttpHeader;
use Hebbinkpro\WebServer\http\message\HttpBody;
use Hebbinkpro\WebServer\http\server\HttpClient;
use Hebbinkpro\WebServer\http\uri\url\HttpUrl;

readonly class HttpRequest implements Request
{
    private HttpClient $client;
    private HttpMethod $method;
    private HttpUrl $target;
    private HttpVersion $httpVersion;
    private HttpHeader $header;
    private ?HttpBody $body;
    private RequestRouteInfo $routeInfo;

    public function __construct(HttpClient $client, HttpMethod $method, HttpUrl $target, HttpVersion $httpVersion, HttpHeader $header, ?HttpBody $body)
    {
        $this->client = $client;
        $this->method = $method;
        $this->target = $target;
        $this->httpVersion = $httpVersion;
        $this->header = $header;
        $this->body = $body;
        $this->routeInfo = new RequestRouteInfo();
    }

    /**
     * @return HttpClient
     */
    public function getClient(): HttpClient
    {
        return $this->client;
    }

    /**
     * @return HttpMethod
     */
    public function getMethod(): HttpMethod
    {
        return $this->method;
    }

    /**
     * @return HttpUrl
     */
    public function getTarget(): HttpUrl
    {
        return $this->target;
    }

    /**
     * @return HttpVersion
     */
    public function getHttpVersion(): HttpVersion
    {
        return $this->httpVersion;
    }

    /**
     * @return HttpHeader
     */
    public function getHeader(): HttpHeader
    {
        return $this->header;
    }

    /**
     * @return HttpBody|null
     */
    public function getBody(): ?HttpBody
    {
        return $this->body;
    }

    /**
     * @return RequestRouteInfo
     */
    public function getRouteInfo(): RequestRouteInfo
    {
        return $this->routeInfo;
    }
}