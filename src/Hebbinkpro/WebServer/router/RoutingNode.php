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

namespace Hebbinkpro\WebServer\router;

use Hebbinkpro\WebServer\exception\RouteExistsException;
use Hebbinkpro\WebServer\http\HttpMethod;
use Hebbinkpro\WebServer\http\uri\UriPath;
use Hebbinkpro\WebServer\route\BaseRoute;
use Hebbinkpro\WebServer\route\MiddlewareRoute;
use Hebbinkpro\WebServer\route\Route;
use Hebbinkpro\WebServer\router\middleware\BaseMiddleware;
use Hebbinkpro\WebServer\router\middleware\Middleware;
use InvalidArgumentException;
use pmmp\thread\ThreadSafe;
use pmmp\thread\ThreadSafeArray;

class RoutingNode extends ThreadSafe
{

	/** @var ThreadSafeArray<string, RoutingNode> */
	protected ThreadSafeArray $leaves;

	/** @var ThreadSafeArray<string, BaseRoute> HttpMethod->value => Route */
	protected ThreadSafeArray $routes;
	/** @var ThreadSafeArray<BaseMiddleware> */
	protected ThreadSafeArray $middleware;

	public function __construct()
	{
		$this->leaves = new ThreadSafeArray();
		$this->routes = new ThreadSafeArray();
		$this->middleware = new ThreadSafeArray();
	}

	/**
	 * Get a route by its method
	 * @param UriPath $path the requested path
	 * @param HttpMethod $method the requested method
	 * @param string[] $routingPath [optional] after getRoute has finished, this contains the entire path to the returned route
	 * @return Route|null the route or null when it does not exist
	 */
	public function getRoute(UriPath $path, HttpMethod $method, array &$routingPath = []): ?Route
	{
		// we got an exact match
		if ($path->getLength() === 0) {
			// first try to get one that matches the method
			$route = $this->routes[$method->value] ?? null;
			// otherwise check if there is an all method registered
			if ($route === null) $route = $this->routes[HttpMethod::ALL->value] ?? null;
		} else {
			$route = $this->findRoute($path, $method, $routingPath);
		}

		// return the route by wrapping it in middleware routes
		if (!$route instanceof Route) return null;
		return $this->wrapMiddleware($route);
	}

	/**
	 * Find a route within the leaves
	 * @param UriPath $path
	 * @param HttpMethod $method
	 * @param string[] $routingPath array to store the routing path
	 * @return Route|null
	 */
	protected function findRoute(UriPath $path, HttpMethod $method, array &$routingPath): ?Route
	{
		// find the route in one of the leave nodes
		foreach ($this->leaves as $leafPart => $leafNode) {
			if ($path->startsWith($leafPart)) {
				// try to find a route in the matching path, otherwise continue the search
				$routingPath[] = $leafPart;
				$route = $leafNode->getRoute($path->slice(1), $method, $routingPath);
				if ($route !== null) return $route;
				array_pop($routingPath);
			}
		}
		return null;
	}

	/**
	 * Wrap a route in middleware routes
	 * @param Route $route
	 * @return Route
	 */
	protected function wrapMiddleware(Route $route): Route
	{
		$count = $this->middleware->count();
		if ($count === 0) return $route;

		// wrap in reverse order, such that the first middleware will be aplied first
		for ($i = ($count - 1); $i >= 0; $i--) {
			$middleware = $this->middleware->offsetGet($i);
			if (!$middleware instanceof Middleware) continue;
			$route = new MiddlewareRoute($middleware, $route);
		}

		return $route;

	}

	/**
	 * Add a route to the given path
	 * @param UriPath|array<int,string> $path the path to the route
	 * @param BaseRoute $route the route to add
	 * @return void
	 */
	public function addRoute(UriPath|array $path, BaseRoute $route): void
	{
		// convert the URI path to an array
		if (!is_array($path)) $path = $path->asArray();

		// its a root path, add it to our own routes
		if (count($path) === 0) {
			$method = $route->getMethod();
			if (isset($this->routes[$method->value])) {
				throw new RouteExistsException(new UriPath($path), $method);
			}

			$this->routes[$method->value] = $route;
			return;
		}

		$this->getOrCreateLeaf($path)->addRoute($path, $route);
	}

	/**
	 * Add middleware to the given path
	 * @param UriPath|array<int,string> $path the path to the route
	 * @param BaseMiddleware $middleware the middleware to add
	 * @return void
	 */
	public function addMiddleware(UriPath|array $path, BaseMiddleware $middleware): void
	{
		// convert the URI path to an array
		if (!is_array($path)) $path = $path->asArray();

		// its a root path, add to our list of middleware
		if (count($path) === 0) {
			$this->middleware[] = $middleware;
			return;
		}

		$this->getOrCreateLeaf($path)->addMiddleware($path, $middleware);
	}

	/**
	 * Get a leaf node by the first element in the path.
	 * Creates a new leaf node when the leaf does not yet exist.
	 * @param string[] $path non-empty array
	 * @return RoutingNode the leaf node
	 */
	protected function getOrCreateLeaf(array &$path): RoutingNode
	{
		if (count($path) === 0) throw new InvalidArgumentException("Path cannot be empty");
		$first = array_shift($path);

		if (!isset($this->leaves[$first]) || !$this->leaves[$first] instanceof RoutingNode) {
			$this->leaves[$first] = new RoutingNode();
		}

		return $this->leaves[$first];
	}

}