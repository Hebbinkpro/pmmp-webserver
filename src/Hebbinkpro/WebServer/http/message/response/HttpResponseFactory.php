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

declare(strict_types=1);

namespace Hebbinkpro\WebServer\http\message\response;

use Hebbinkpro\WebServer\http\server\HttpClient;
use Hebbinkpro\WebServer\http\status\HttpStatus;
use Hebbinkpro\WebServer\http\status\HttpStatusCodes;
use Hebbinkpro\WebServer\http\status\HttpStatusRegistry;

/**
 * Factory to create some generic frequently used HTTP Responses
 */
final class HttpResponseFactory
{
    public static function notFound(HttpClient $client): HttpResponse
    {
        return self::statusResponse($client, HttpStatusCodes::NOT_FOUND);
    }

    /**
     * Generate a generic status response
     * @param HttpClient $client
     * @param HttpStatus|int $status
     * @return HttpResponse
     */
    public static function statusResponse(HttpClient $client, HttpStatus|int $status): HttpResponse
    {
        $status = HttpStatusRegistry::getInstance()->parseOrDefault($status);

        return (new HttpResponseBuilder())
            ->setStatus($status)
            ->text($status->getMessage())
            ->build($client);
    }

    public static function internalServerError(HttpClient $client): HttpResponse
    {
        return self::statusResponse($client, HttpStatusCodes::INTERNAL_SERVER_ERROR);
    }

    public static function badRequest(HttpClient $client): HttpResponse
    {
        return self::statusResponse($client, HttpStatusCodes::BAD_REQUEST);
    }

    public static function notImplemented(HttpClient $client): HttpResponse
    {
        return self::statusResponse($client, HttpStatusCodes::NOT_IMPLEMENTED);
    }


}