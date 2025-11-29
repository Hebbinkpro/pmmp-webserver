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

use Hebbinkpro\WebServer\http\message\HttpResponse;
use Hebbinkpro\WebServer\http\server\HttpClient;
use Hebbinkpro\WebServer\http\status\HttpStatus;
use Hebbinkpro\WebServer\http\status\HttpStatusRegistry;

/**
 * A class to create RFC 7807 compliant Problem Details when HTTP requests do something unexpected.
 */
class HttpProblem
{
    public function __construct(private HttpStatus|int $statusCode, private ?string $instance, private ?string $detail)
    {
    }

    /**
     * @return HttpStatus|int
     */
    public function getStatusCode(): HttpStatus|int
    {
        return $this->statusCode;
    }

    /**
     * @return string|null
     */
    public function getDetail(): ?string
    {
        return $this->detail;
    }

    /**
     * Create an RFC7807 compliant HTTP problem+json response
     * @param HttpClient $client the client to which the response should be sent
     * @return HttpResponse
     */
    public function createResponse(HttpClient $client): HttpResponse
    {
        $status = HttpStatusRegistry::getInstance()->parseOrDefault($this->statusCode, "Custom Error");

        $response = new HttpResponse($client, $status);
        $response->json([
            "type" => $status->getUriReference(),
            "title" => $status->getMessage(),
            "status" => $status->getCode(),
            "detail" => $this->detail ?? $status->getMessage(),
            "instance" => $this->instance,
        ], HttpContentType::APPLICATION_PROBLEM_JSON);

        return $response;
    }

    /**
     * @return string
     */
    public function getInstance(): string
    {
        return $this->instance ?? "about:blank";
    }
}