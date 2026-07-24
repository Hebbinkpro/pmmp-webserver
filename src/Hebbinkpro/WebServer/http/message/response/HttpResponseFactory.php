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

use Hebbinkpro\WebServer\http\status\HttpStatus;
use Hebbinkpro\WebServer\http\status\HttpStatusCodes;
use Hebbinkpro\WebServer\http\status\HttpStatusRegistry;

/**
 * Factory to create some generic frequently used HTTP Responses
 */
final class HttpResponseFactory
{
    /**
     * Generate a generic status response
     * @param HttpStatus|int $status
     * @param bool $headOnly
     * @return HttpResponseBuilder
     */
    public static function statusResponse(HttpStatus|int $status, bool $headOnly = false): HttpResponseBuilder
    {
        $status = HttpStatusRegistry::getInstance()->parseOrDefault($status);

        return (new HttpResponseBuilder($headOnly))
            ->setStatus($status)
            ->text($status->getMessage());
    }

    /**
     * Create a "200 OK" response
     * @return HttpResponseBuilder
     */
    public static function ok(): HttpResponseBuilder
    {
        return self::statusResponse(HttpStatusCodes::OK);
    }

    /**
     * Create a "204 No Content" response
     *
     * This sets `HttpResponseBuilder::$headOnly` to true
     * @return HttpResponseBuilder
     */
    public static function noContent(): HttpResponseBuilder
    {
        return self::statusResponse(HttpStatusCodes::NO_CONTENT, true);
    }

    /**
     * Create a "400 Bad Request" response
     * @return HttpResponseBuilder
     */
    public static function badRequest(): HttpResponseBuilder
    {
        return self::statusResponse(HttpStatusCodes::BAD_REQUEST);
    }

    /**
     * Create a "404 Not Found" response
     * @return HttpResponseBuilder
     */
    public static function notFound(): HttpResponseBuilder
    {
        return self::statusResponse(HttpStatusCodes::NOT_FOUND);
    }

    /**
     * Create a "500 Internal Server Error" response
     * @return HttpResponseBuilder
     */
    public static function internalServerError(): HttpResponseBuilder
    {
        return self::statusResponse(HttpStatusCodes::INTERNAL_SERVER_ERROR);
    }

    /**
     * Create a "501 Not Implemented" response
     * @return HttpResponseBuilder
     */
    public static function notImplemented(): HttpResponseBuilder
    {
        return self::statusResponse(HttpStatusCodes::NOT_IMPLEMENTED);
    }


}