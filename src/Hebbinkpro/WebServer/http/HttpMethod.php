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
 * All known HTTP Request Methods according to the [Mozilla web docs](https://developer.mozilla.org/en-US/docs/Web/HTTP/Methods).
 */
enum HttpMethod: string
{
    /** Not part of the HTTP method spec, but is used as part of the routing process in Router */
    case ALL = "*";

    /** Transfer a current representation of the target resource. */
    case GET = "GET";
    /** Same as GET, but do not transfer the response content. */
    case HEAD = "HEAD";
    /** Perform resource-specific processing on the request content. */
    case POST = "POST";
    /** Replace all current representations of the target resource with the request content. */
    case PUT = "PUT";
    /** Remove all current representations of the target resource. */
    case DELETE = "DELETE";
    /** Establish a tunnel to the server identified by the target resource. */
    case CONNECT = "CONNECT";
    /** Describe the communication options for the target resource. */
    case OPTIONS = "OPTIONS";
    /** Perform a message loop-back test along the path to the target resource. */
    case TRACE = "TRACE";
    /** Make partial changes to an existing resource. */
    case PATCH = "PATCH";

    /**
     * Check if the given method equals to the given method.
     *
     * If the given method is ALL, then true is returned.
     * @param HttpMethod $method
     * @return bool
     */
    public function equals(HttpMethod $method): bool
    {
        return $this === $method || $method === HttpMethod::ALL;
    }
}