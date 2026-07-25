<?php
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

	    $params = [];
	    $routePath = $route->getPath() ?? new UriPath();
	    $this->subPath = $requestPath->getSubPath($routePath, false, $params);

	    $this->pathParams = $params;
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