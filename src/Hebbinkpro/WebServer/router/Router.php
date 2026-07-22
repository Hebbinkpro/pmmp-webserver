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
use Hebbinkpro\WebServer\http\HttpHeaders;
use Hebbinkpro\WebServer\http\HttpMethod;
use Hebbinkpro\WebServer\http\HttpProblem;
use Hebbinkpro\WebServer\http\message\request\HttpRequest;
use Hebbinkpro\WebServer\http\message\response\HttpResponse;
use Hebbinkpro\WebServer\http\message\response\HttpResponseBuilder;
use Hebbinkpro\WebServer\http\message\response\HttpResponseFactory;
use Hebbinkpro\WebServer\http\server\HttpClient;
use Hebbinkpro\WebServer\http\status\HttpStatus;
use Hebbinkpro\WebServer\http\status\HttpStatusCodes;
use Hebbinkpro\WebServer\http\uri\UriPath;
use Hebbinkpro\WebServer\route\FileRoute;
use Hebbinkpro\WebServer\route\Route;
use Hebbinkpro\WebServer\route\RouterRoute;
use Hebbinkpro\WebServer\route\StaticRoute;
use pmmp\thread\ThreadSafe;
use pmmp\thread\ThreadSafeArray;

/**
 * A Router that handles requests by calling the Route corresponding to the request path
 */
class Router extends ThreadSafe implements RouterInterface
{
    /**
     * TODO change this to a tree structure
     * @var ThreadSafeArray<string, Route|ThreadSafeArray<string, Route>>
     */
    private ThreadSafeArray $routes;

    public function __construct()
    {
        $this->routes = new ThreadSafeArray();
    }

    /**
     * Let the correct route handle an HTTP request
     * @param HttpClient $client the client
     * @param HttpRequest $request the request from the client
     * @return HttpResponse the response to send back to the client
     */
    public function handleRequest(HttpClient $client, HttpRequest $request): HttpResponse
    {
        // get the route that will handle the request
        $routePath = $this->getRoutePath($request);

        // no route was found
        if ($routePath === null) {
            // send a 404 not found message
            return HttpResponseFactory::notFound($client);
        }

        /** @var Route|ThreadSafeArray<string, Route> $routeEntry */
        $routeEntry = $this->routes[$routePath] ?? null;
        if ($routeEntry instanceof Route) {
            $route = $routeEntry;
        } else {
            /** @var Route|null $route */
            $route = $routeEntry[$request->getMethod()->name] ?? null;
        }

        if ($route === null) {
            // send a 404 not found message
            return HttpResponseFactory::notFound($client);
        }

        // add the route path in the request, used for path params and sub paths
        $request->getRouteInfo()->updateRouteInfo($route, UriPath::parse($routePath));

        // handle the request
        return $route->handleRequest($client, $request);
    }

    /**
     * Get the path to route to from the given request
     * @param HttpRequest $req
     * @return string|null
     */
    public function getRoutePath(HttpRequest $req): ?string
    {
        $reqPath = $req->getRouteInfo()->getSubPath();
        foreach ($this->routes as $routePath => $routes) {
            if (($routes instanceof Route || isset($routes[$req->getMethod()->name]))
                && $this->matchesRoutePath($reqPath, $routePath)) {
                return $routePath;
            }
        }

        return null;
    }

    /**
     * Checks if the request path matches the given route path
     * @param string $reqPath
     * @param string $routePath
     * @return bool if the path matches
     */
    public function matchesRoutePath(string $reqPath, string $routePath): bool
    {
        // any route
        if ($routePath === "*") return true;

        // get the route path as an array
        $splitReqPath = explode("/", $reqPath);
        $splitRoutePath = explode("/", $routePath);

        // the request path is smaller than the route path, which isn't possible
        if (count($splitReqPath) < count($splitRoutePath)) return false;

        // loop through all sub paths of the route
        foreach ($splitReqPath as $i => $reqSubPath) {
            $routeSubPath = $splitRoutePath[$i] ?? null;
            if ($routeSubPath === null) return false;

            if ($routeSubPath === "*") return true;

            if ($reqSubPath !== $routeSubPath && !str_starts_with($routeSubPath, ":")) return false;
        }

        // the given path is valid
        return true;
    }

    /**
     * Reject a request with a given status
     * @param HttpClient $client
     * @param int|HttpStatus $status
     * @param string $body
     * @return void
     */
    public function rejectRequest(HttpClient $client, int|HttpStatus $status = HttpStatusCodes::BAD_REQUEST, string $body = ""): void
    {
        // TODO move to the client as it has nothing to do with the Router
        $res = new HttpResponseBuilder();
        $res->setStatus($status);
        $res->getHeader()->setField(HttpHeaders::CONNECTION, "close");

        if (strlen($body) === 0) $body = $res->getStatus()->toString();
        $res->text($body);

        $res->build($client);
    }

    /**
     * Reject a request because of a problem
     * @param HttpClient $client
     * @param HttpProblem $problem
     * @return void
     */
    public function rejectRequestWithProblem(HttpClient $client, HttpProblem $problem): void
    {
        // TODO move to the client as it has nothing to do with the Router
        $res = $problem->createResponse();
        $res->getHeader()->setField(HttpHeaders::CONNECTION, "close");
        $res->build($client);
    }

    /**
     * @throws RouteExistsException|RouteInUseException
     */
    public function get(string $path, Closure $action, mixed ...$params): void
    {
        $this->addRoute($path, new Route(HttpMethod::GET, $action, ...$params));
    }

    /**
     * Assign the route to the path
     * @param string $path
     * @param Route $route
     * @return void
     * @throws RouteExistsException if the routing path already exists
     * @throws RouteInUseException if the given route is already added to a routing path
     */
    public function addRoute(string $path, Route $route): void
    {
        $segments = trim($path, "/");

        if (isset($this->routes[$segments])) {
            // it's a route for all methods
            if (!$this->routes[$segments] instanceof ThreadSafeArray) throw new RouteExistsException($segments, HttpMethod::ALL);

            // there exists already a route for this method, or if an any route is added
            if (isset($this->routes[$segments][$route->getMethod()->name]) || $route->getMethod() === HttpMethod::ALL) {
                throw new RouteExistsException($segments, $route->getMethod());
            }
        }

        if ($route->getMethod() === HttpMethod::ALL) $this->routes[$segments] = $route;
        else {
            if (!isset($this->routes[$segments])) $this->routes[$segments] = new ThreadSafeArray();
            /** @phpstan-ignore-next-line */
            $this->routes[$segments][$route->getMethod()->name] = $route;
        }

        $uriPath = UriPath::parse($path);
        $route->setPath($uriPath);
    }

    /**
     * Add a FileRoute to the router
     * @param string $path
     * @param string $file the path of the file
     * @param string|null $contentType the content type of the file
     * @param string|null $default default value used when the file does not exist
     * @param string|null $defaultContentType the content type of the default value
     * @return void
     */
    public function getFile(string $path, string $file, ?string $contentType = null, ?string $default = null, ?string $defaultContentType = null): void
    {
        $this->addRoute($path, new FileRoute($file, default: $default));
    }

    /**
     * @throws RouteExistsException|RouteInUseException
     */
    public function post(string $path, Closure $action, mixed ...$params): void
    {
        $this->addRoute($path, new Route(HttpMethod::POST, $action, ...$params));
    }

    /**
     * @throws RouteExistsException|RouteInUseException
     */
    public function head(string $path, Closure $action, mixed ...$params): void
    {
        $this->addRoute($path, new Route(HttpMethod::HEAD, $action, ...$params));
    }

    /**
     * @throws RouteExistsException|RouteInUseException
     */
    public function put(string $path, Closure $action, mixed ...$params): void
    {
        $this->addRoute($path, new Route(HttpMethod::PUT, $action, ...$params));
    }

    /**
     * @throws RouteExistsException|RouteInUseException
     */
    public function delete(string $path, Closure $action, mixed ...$params): void
    {
        $this->addRoute($path, new Route(HttpMethod::DELETE, $action, ...$params));
    }

    /**
     * @throws RouteExistsException|RouteInUseException
     */
    public function all(string $path, Closure $action, mixed ...$params): void
    {
        $this->addRoute($path, new Route(HttpMethod::ALL, $action, ...$params));
    }

    /**
     * Add a RouterRoute to the router.
     *
     * @param string $path
     * @param Router $router
     * @return void
     * @throws RouteExistsException|RouteInUseException
     */
    public function route(string $path, Router $router): void
    {
        $this->addAnyRoute($path, new RouterRoute($router));
    }

    /**
     * Add a route to the router that accepts every path staring with the given path
     * @param string $path the path that should match
     * @param Route $route the route that handles the request
     * @return void
     * @throws RouteExistsException|RouteInUseException
     */
    public function addAnyRoute(string $path, Route $route): void
    {
        // make sure the static route ends with a *
        if (str_ends_with($path, "/")) $path .= "*";
        else if (!str_ends_with($path, "/*")) $path .= "/*";

        $this->addRoute($path, $route);
    }

    /**
     * Create a GET route for a static folder
     * @param string $path
     * @param string $folder
     * @return void
     * @throws FolderNotFoundException|RouteExistsException|RouteInUseException
     */
    public function getStatic(string $path, string $folder): void
    {
        $this->addAnyRoute($path, new StaticRoute($folder));

    }

    public function getRoutes(): ThreadSafeArray
    {
        return $this->routes;
    }
}