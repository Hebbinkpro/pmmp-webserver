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

/*
 *
 *  __          __  _     _____
 *  \ \        / / | |   / ____|
 *   \ \  /\  / /__| |__| (___   ___ _ ____   _____ _ __
 *    \ \/  \/ / _ \ '_ \\___ \ / _ \ '__\ \ / / _ \ '__|
 *     \  /\  /  __/ |_) |___) |  __/ |   \ V /  __/ |
 *      \/  \/ \___|_.__/_____/ \___|_|    \_/ \___|_|
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Lesser General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * @author Hebbinkpro
 * @link https://github.com/Hebbinkpro/pmmp-webserver
 *
 *
 */

declare(strict_types=1);

namespace Hebbinkpro\WebServer\http\message\request;

use Hebbinkpro\WebServer\http\uri\UriPath;

/**
 * Route information for a request, containing the route path, and various other useful details
 */
class RequestRouteInfo
{
	private ?UriPath $routePath;
    /** @var array<string, string> */
    private array $pathParams;
	private ?UriPath $filePath;

    public function __construct()
    {
	    $this->routePath = null;
        $this->pathParams = [];
	    $this->filePath = null;
    }

	/**
	 * @param UriPath $routePath
	 * @param UriPath $requestPath
	 */
	public function updateRouteInfo(UriPath $routePath, UriPath $requestPath): void
    {
	    $this->routePath = $routePath;

	    $params = [];
	    $this->filePath = $requestPath->getFilePath($routePath, false, $params);

	    $this->pathParams = $params;
    }

    /**
     * Get the route path
     * @return UriPath|null
     */
	public function getRoutePath(): ?UriPath
    {
	    return $this->routePath;
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
     * Get the file path of the request
     *
     * A file path is the remaining part of the path, after the route path has been removed from the request path
     * @return UriPath|null
     */
	public function getFilePath(): ?UriPath
    {
	    return $this->filePath;
    }

}