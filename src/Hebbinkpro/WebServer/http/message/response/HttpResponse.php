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

namespace Hebbinkpro\WebServer\http\message\response;

use Hebbinkpro\WebServer\http\HttpVersion;
use Hebbinkpro\WebServer\http\message\header\HttpHeader;
use Hebbinkpro\WebServer\http\message\HttpBody;
use Hebbinkpro\WebServer\http\server\HttpClientInfo;
use Hebbinkpro\WebServer\http\status\HttpStatus;

readonly class HttpResponse implements HttpResponseMessage
{
    private HttpClientInfo $client;
    private HttpStatus $status;
	private HttpVersion $version;
    private HttpHeader $headers;

    private ?HttpBody $body;

	/**
	 * @param HttpClientInfo $client
	 * @param HttpStatus $status
	 * @param HttpVersion $version
	 * @param HttpHeader $headers
	 * @param HttpBody|null $body
	 */
	public function __construct(HttpClientInfo $client, HttpStatus $status, HttpVersion $version, HttpHeader $headers, ?HttpBody $body)
    {
        $this->client = $client;
        $this->status = $status;
	    $this->version = $version;
        $this->headers = $headers;
        $this->body = $body;
    }

    /**
     * @return HttpClientInfo
     */
    public function getClient(): HttpClientInfo
    {
        return $this->client;
    }

    /**
     * @return HttpStatus
     */
    public function getStatus(): HttpStatus
    {
        return $this->status;
    }

    /**
     * @return HttpVersion
     */
    public function getVersion(): HttpVersion
    {
	    return $this->version;
    }

    /**
     * @return HttpHeader
     */
    public function getHeader(): HttpHeader
    {
        return $this->headers;
    }

    /**
     * @return HttpBody|null
     */
    public function getBody(): ?HttpBody
    {
        return $this->body;
    }
}