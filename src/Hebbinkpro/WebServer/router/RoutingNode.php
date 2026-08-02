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
use Hebbinkpro\WebServer\route\Route;
use pmmp\thread\ThreadSafe;
use pmmp\thread\ThreadSafeArray;

class RoutingNode extends ThreadSafe
{

	/** @var ThreadSafeArray<string, RoutingNode> */
	protected ThreadSafeArray $leaves;

	/** @var ThreadSafeArray<string, Route> HttpMethod->value => Route */
	protected ThreadSafeArray $routes;

	public function __construct()
	{
		$this->leaves = new ThreadSafeArray();
		$this->routes = new ThreadSafeArray();
	}

	/**
	 * Get a route by its method
	 * @param UriPath $path the requested path
	 * @param HttpMethod $method the requested method
	 * @return Route|null the route or null when it does not exist
	 */
	public function getRoute(UriPath $path, HttpMethod $method): ?Route
	{
		// we got an exact match
		if ($path->getLength() === 0) {
			// first try to get one that matches the method
			$route = $this->routes[$method->value] ?? null;
			// otherwise check if there is an all method registered
			if ($route === null) $route = $this->routes[HttpMethod::ALL->value] ?? null;

			return $route;
		}

		return $this->findRoute($path, $method);
	}

	/**
	 * Find a route within the leaves
	 * @param UriPath $path
	 * @param HttpMethod $method
	 * @return Route|null
	 */
	protected function findRoute(UriPath $path, HttpMethod $method): ?Route
	{
		// find the route in one of the leave nodes
		foreach ($this->leaves as $leafPart => $leafNode) {
			if ($path->startsWith($leafPart)) {
				// try to find a route in the matching path, otherwise continue the search
				$route = $leafNode->getRoute($path->slice(1), $method);
				if ($route !== null) return $route;
			}
		}
		return null;
	}

	/**
	 * Add a route to the given path
	 * @param UriPath|string[] $path the path to the route
	 * @param Route $route the route to add
	 * @return void
	 */
	public function addRoute(UriPath|array $path, Route $route): void
	{

		// convert the URI path to an array
		if (!is_array($path)) $path = $path->asArray();

		// its a root path, add it to our own routes
		if (count($path) === 0) {
			$method = $route->getMethod();
			if (isset($this->routes[$method->value])) {
				throw new RouteExistsException($path, $method);
			}

			$this->routes[$method->value] = $route;
			return;
		}

		// create a new leave node with the name of the first path element
		$first = array_shift($path);

		if (!isset($this->leaves[$first])) {
			$this->leaves[$first] = new RoutingNode();
		}

		$this->leaves[$first]->addRoute($path, $route);
	}
}