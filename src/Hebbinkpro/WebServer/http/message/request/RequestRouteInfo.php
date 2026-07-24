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

namespace Hebbinkpro\WebServer\http\message\request;

use Hebbinkpro\WebServer\http\uri\UriPath;
use Hebbinkpro\WebServer\route\Route;

/**
 * Route information for a request, containing the route path, and various other useful details
 */
class RequestRouteInfo
{
    private ?Route $route;
    /** @var array<string, string> */
    private array $pathParams;
    private ?UriPath $subPath;

    public function __construct()
    {
        $this->route = null;
        $this->pathParams = [];
        $this->subPath = null;
    }

    /**
     * @param Route $route
     * @param UriPath $requestPath
     */
    public function updateRouteInfo(Route $route, UriPath $requestPath): void
    {
        $this->route = $route;
        $this->pathParams = [];
        $this->subPath = $requestPath->getSubPath($route->getPath(), $this->pathParams);
    }

    /**
     * @return Route|null
     */
    public function getRoute(): ?Route
    {
        return $this->route;
    }

    /**
     * Get the value of a parameter from the path parameters
     * @param string $name the name of the parameter
     * @return string|null the value or null if it does not exist
     */
    public function getParameter(string $name): ?string
    {
        return $this->pathParams[$name] ?? null;
    }

    /**
     * Get the subpath of the request
     *
     * A subpath is the remaining part of the path, after the route path has been removed from the request path
     * @return UriPath|null
     */
    public function getSubPath(): ?UriPath
    {
        return $this->subPath;
    }

}