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

namespace Hebbinkpro\WebServer\router;

use Closure;
use Hebbinkpro\WebServer\exception\FolderNotFoundException;
use Hebbinkpro\WebServer\exception\RouteExistsException;
use Hebbinkpro\WebServer\exception\RouteInUseException;
use Hebbinkpro\WebServer\http\HttpMethod;
use Hebbinkpro\WebServer\http\message\request\HttpRequest;
use Hebbinkpro\WebServer\http\message\response\HttpResponse;
use Hebbinkpro\WebServer\http\message\response\HttpResponseBuilder;
use Hebbinkpro\WebServer\http\message\response\HttpResponseFactory;
use Hebbinkpro\WebServer\http\server\HttpClientInfo;
use Hebbinkpro\WebServer\http\uri\UriPath;
use Hebbinkpro\WebServer\http\uri\url\HttpAsteriskUrl;
use Hebbinkpro\WebServer\http\uri\url\HttpAuthorityUrl;
use Hebbinkpro\WebServer\http\uri\url\HttpOriginUrl;
use Hebbinkpro\WebServer\route\ActionRoute;
use Hebbinkpro\WebServer\route\BaseRoute;
use Hebbinkpro\WebServer\route\FileRoute;
use Hebbinkpro\WebServer\route\RouterRoute;
use Hebbinkpro\WebServer\route\StaticRoute;
use Hebbinkpro\WebServer\router\middleware\ActionMiddleware;
use Hebbinkpro\WebServer\router\middleware\BaseMiddleware;
use pmmp\thread\ThreadSafe;

/**
 * A Router that handles requests by calling the Route corresponding to the request path
 */
class Router extends ThreadSafe implements RouterInterface
{
	private RoutingNode $routes;

    public function __construct()
    {
	    $this->routes = new RoutingNode();
    }

	/**
	 * @inheritDoc
	 */
	public function handleRequest(HttpClientInfo $client, HttpRequest $request, HttpResponseBuilder|null $response = null): HttpResponse
    {

	    $target = $request->getTarget();
	    if ($target instanceof HttpOriginUrl) {
		    return $this->handleOriginRequest($client, $request, $target, $response);
	    } else if ($target instanceof HttpAsteriskUrl) {
		    return $this->handleAsteriskRequest($client, $request, $target, $response);
	    } else if ($target instanceof HttpAuthorityUrl) {
		    return $this->handleAuthorityRequest($client, $request, $target, $response);
	    }

	    // unknown request target
	    return HttpResponseFactory::notImplemented()->build($client);
    }

	/**
	 * Handle all requests in the Origin form
	 * @param HttpClientInfo $client
	 * @param HttpRequest $request
	 * @param HttpOriginUrl $target
	 * @param HttpResponseBuilder|null $response
	 * @return HttpResponse
	 */
	protected function handleOriginRequest(HttpClientInfo $client, HttpRequest $request, HttpOriginUrl $target, HttpResponseBuilder|null $response = null): HttpResponse
	{
		$routingPath = [];
		$route = $this->routes->getRoute($target->getPath(), $request->getMethod(), $routingPath);

		if ($route === null) {
			return HttpResponseFactory::notFound()->build($client);
		}

		// add the route path in the request, used for path params and sub paths
		$request->getRouteInfo()->updateRouteInfo(new UriPath($routingPath), $target->getPath());

		// handle the request
		return $route->handleRequest($client, $request, $response);
	}

	/**
	 * Handle all requests in the asterisk form
	 * @param HttpClientInfo $client
	 * @param HttpRequest $request
	 * @param HttpAsteriskUrl $target
	 * @param HttpResponseBuilder|null $response
	 * @return HttpResponse
	 */
	protected function handleAsteriskRequest(HttpClientInfo $client, HttpRequest $request, HttpAsteriskUrl $target, HttpResponseBuilder|null $response = null): HttpResponse
	{
		return HttpResponseFactory::notImplemented()->build($client);
	}

	/**
	 * Handle all requests in the authority form
	 * @param HttpClientInfo $client
	 * @param HttpRequest $request
	 * @param HttpAuthorityUrl $target
	 * @param HttpResponseBuilder|null $response
	 * @return HttpResponse
	 */
	protected function handleAuthorityRequest(HttpClientInfo $client, HttpRequest $request, HttpAuthorityUrl $target, HttpResponseBuilder|null $response = null): HttpResponse
	{
		return HttpResponseFactory::notImplemented()->build($client);
	}

    /**
     * @throws RouteExistsException|RouteInUseException
     */
	public function get(UriPath|string $path, Closure $action, mixed ...$params): void
    {
	    $this->addRoute($path, new ActionRoute(HttpMethod::GET, $action, ...$params));
    }

    /**
     * Assign the route to the path
     * @param UriPath|string $path the path to access the route
     * @param BaseRoute $route the route that should be executed when requested
     * @return void
     * @throws RouteExistsException if the routing path already exists
     * @throws RouteInUseException if the given route is already added to a routing path
     */
	public function addRoute(UriPath|string $path, BaseRoute $route): void
    {
	    if (is_string($path)) $path = UriPath::parse($path);
	    $this->routes->addRoute($path, $route);
    }

	/**
	 * Assign the middleware to the path
	 * @param UriPath|string $path the path to access the middleware
	 * @param BaseMiddleware $middleware the middleware that should be executed when requested
	 * @return void
	 * @throws RouteExistsException if the routing path already exists
	 * @throws RouteInUseException if the given route is already added to a routing path
	 */
	public function addMiddleware(UriPath|string $path, BaseMiddleware $middleware): void
	{
		if (is_string($path)) $path = UriPath::parse($path);
		$this->routes->addMiddleware($path, $middleware);
	}

    /**
     * Add a FileRoute to the router
     * @param UriPath|string $path
     * @param string $file the path of the file
     * @param string|null $contentType the content type of the file
     * @param string|null $default default value used when the file does not exist
     * @param string|null $defaultContentType the content type of the default value
     * @return void
     */
	public function getFile(UriPath|string $path, string $file, ?string $contentType = null, ?string $default = null, ?string $defaultContentType = null): void
    {
	    $this->addRoute($path, new FileRoute($file, $contentType, $default, $defaultContentType));
    }

    /**
     * Handle a POST request
     * @throws RouteExistsException|RouteInUseException
     */
	public function post(UriPath|string $path, Closure $action, mixed ...$params): void
    {
	    $this->addRoute($path, new ActionRoute(HttpMethod::POST, $action, ...$params));
    }

    /**
     * Handle a HEAD request
     * @throws RouteExistsException|RouteInUseException
     */
	public function head(UriPath|string $path, Closure $action, mixed ...$params): void
    {
	    $this->addRoute($path, new ActionRoute(HttpMethod::HEAD, $action, ...$params));
    }

    /**
     * Handle a PUT request
     * @throws RouteExistsException|RouteInUseException
     */
	public function put(UriPath|string $path, Closure $action, mixed ...$params): void
    {
	    $this->addRoute($path, new ActionRoute(HttpMethod::PUT, $action, ...$params));
    }

    /**
     * Handle a DELETE request
     * @throws RouteExistsException|RouteInUseException
     */
	public function delete(UriPath|string $path, Closure $action, mixed ...$params): void
    {
	    $this->addRoute($path, new ActionRoute(HttpMethod::DELETE, $action, ...$params));
    }

    /**
     * Handle a request for all methods
     * @throws RouteExistsException|RouteInUseException
     */
	public function all(UriPath|string $path, Closure $action, mixed ...$params): void
    {
	    $this->addRoute($path, new ActionRoute(HttpMethod::ALL, $action, ...$params));
    }

    /**
     * Add a RouterRoute to the router.
     *
     * @param UriPath|string $path
     * @param Router $router
     * @return void
     * @throws RouteExistsException|RouteInUseException
     */
	public function route(UriPath|string $path, Router $router): void
    {
	    $this->addFilePathRoute($path, new RouterRoute($router));
    }

    /**
     * Add a route to the router that accepts every path staring with the given path
     * @param UriPath|string $path the path that should match
     * @param BaseRoute $route the route that handles the request
     * @return void
     * @throws RouteExistsException|RouteInUseException
     */
	public function addFilePathRoute(UriPath|string $path, BaseRoute $route): void
    {
	    if (is_string($path)) $path = UriPath::parse($path);

	    if (!$path->hasFilePathWildcard()) {
		    $path = $path->append("*");
	    }

        $this->addRoute($path, $route);
    }

    /**
     * Create a GET route for a static folder
     * @param UriPath|string $path
     * @param string $folder
     * @return void
     * @throws FolderNotFoundException|RouteExistsException|RouteInUseException
     */
	public function getStatic(UriPath|string $path, string $folder): void
    {
	    $this->addFilePathRoute($path, new StaticRoute($folder));

    }

	public function getRoutes(): RoutingNode
    {
        return $this->routes;
    }

	public function use(string $path, Closure $action, ...$params): void
	{
		$this->addMiddleware($path, new ActionMiddleware($action, ...$params));
	}
}