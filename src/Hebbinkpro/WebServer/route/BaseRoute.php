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

namespace Hebbinkpro\WebServer\route;

use Hebbinkpro\WebServer\exception\RouteInUseException;
use Hebbinkpro\WebServer\http\HttpMethod;
use Hebbinkpro\WebServer\http\uri\UriPath;
use pmmp\thread\ThreadSafe;

/**
 * A route that handles a client request for a specific path
 */
abstract class BaseRoute extends ThreadSafe implements Route
{
    private HttpMethod $method;
    private ?UriPath $path;

	/**
	 * @param HttpMethod $method the request method
	 */
	public function __construct(HttpMethod $method)
    {
        $this->method = $method;
        $this->path = null;
    }

    /**
     * Get the HTTP method
     * @return HttpMethod
     */
    public function getMethod(): HttpMethod
    {
        return $this->method;
    }

    /**
     * @param UriPath $path
     * @throws RouteInUseException if the route is already bound to a path
     */
    public function setPath(UriPath $path): void
    {
        if ($this->path !== null) throw new RouteInUseException("Route provided for '" . $path->toString() . "' is already in use at '" . $this->path->toString() . "'");
        $this->path = $path;
    }

    /**
     * @return UriPath|null
     */
    public function getPath(): ?UriPath
    {
        return $this->path;
    }
}