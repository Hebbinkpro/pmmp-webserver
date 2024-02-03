<?php

namespace Hebbinkpro\WebServer\route;

use Hebbinkpro\WebServer\exception\FileNotFoundException;
use Hebbinkpro\WebServer\exception\FolderNotFoundException;
use Hebbinkpro\WebServer\http\HttpMethod;
use Hebbinkpro\WebServer\http\HttpRequest;
use Hebbinkpro\WebServer\http\HttpResponse;
use Hebbinkpro\WebServer\libs\Laravel\SerializableClosure\Exceptions\PhpVersionNotSupportedException;
use Hebbinkpro\WebServer\WebClient;
use pmmp\thread\ThreadSafe;
use pmmp\thread\ThreadSafeArray;

/**
 * A Router that handles requests by calling the Route corresponding to the request path
 */
class Router extends ThreadSafe
{
    /** @var Route[] */
    private ThreadSafeArray $routes;

    public function __construct()
    {
        $this->routes = new ThreadSafeArray();
    }

    /**
     * Handle an incoming client request
     * @param WebClient $client the client
     * @param HttpRequest $request the request from the client
     * @return void
     * @throws PhpVersionNotSupportedException
     */
    public function handleRequest(WebClient $client, HttpRequest $request): void
    {
        // get the route that will handle the request
        $route = $this->getRoute($request);

        // no route was found
        if ($route === null) {
            // send a 404 not found message
            HttpResponse::notFound($client)->end();
            return;
        }

        // set the route in the request, used for e.g. path params
        $request->setRoute($route);

        // handle the request
        $route->handleRequest($client, $request);
    }

    /**
     * Get a route corresponding to the request
     * @param HttpRequest $req
     * @return Route|null null when no valid Route has been found
     */
    public function getRoute(HttpRequest $req): ?Route
    {

        return $this->getRouteByPath($req->getMethod(), $req->getURL()->getPath());
    }

    /**
     * Get a route corresponding to the given url method and path
     * @param string $method the request method
     * @param array $path the request path
     * @return route|null
     */
    public function getRouteByPath(string $method, array $path): ?route
    {
        foreach ($this->routes as $route) {
            if ($route->equals($method, $path)) return $route;
        }

        return null;
    }

    /**
     * Add a GET route to the router
     * @param string $path
     * @param callable $action
     * @param mixed ...$params
     * @return void
     * @throws PhpVersionNotSupportedException
     */
    public function get(string $path, callable $action, mixed ...$params): void
    {
        $this->addRoute(new Route(HttpMethod::GET, $path, $action, ...$params));
    }

    /**
     * Add a Route to the router
     * @param Route $route
     * @return void
     */
    public function addRoute(Route $route): void
    {
        $this->routes[] = $route;
    }

    /**
     * Add a FileRoute to the router
     * @param string $path
     * @param string $file the path of the file
     * @param string|null $default default value used when the file does not exist
     * @return void
     * @throws FileNotFoundException if the file does not exist and the default value is null
     * @throws PhpVersionNotSupportedException
     */
    public function getFile(string $path, string $file, ?string $default = null): void
    {
        $this->addRoute(new FileRoute($path, $file, $default));
    }

    /**
     * Add a POST route to the router
     * @param string $path
     * @param callable $action
     * @param mixed $params
     * @return void
     * @throws PhpVersionNotSupportedException
     */
    public function post(string $path, callable $action, mixed ...$params): void
    {
        $this->addRoute(new Route(HttpMethod::POST, $path, $action, ...$params));
    }

    /**
     * Add a HEAD route to the router
     * @param string $path
     * @param callable $action
     * @param mixed $params
     * @return void
     * @throws PhpVersionNotSupportedException
     */
    public function head(string $path, callable $action, mixed ...$params): void
    {
        $this->addRoute(new Route(HttpMethod::HEAD, $path, $action, ...$params));
    }

    /**
     * Add a PUT route to the router
     * @param string $path
     * @param callable $action
     * @param mixed $params
     * @return void
     * @throws PhpVersionNotSupportedException
     */
    public function put(string $path, callable $action, mixed ...$params): void
    {
        $this->addRoute(new Route(HttpMethod::PUT, $path, $action, ...$params));
    }

    /**
     * Add a DELETE route to the router
     * @param string $path
     * @param callable $action
     * @param mixed $params
     * @return void
     * @throws PhpVersionNotSupportedException
     */
    public function delete(string $path, callable $action, mixed ...$params): void
    {
        $this->addRoute(new Route(HttpMethod::DELETE, $path, $action, ...$params));
    }

    /**
     * Add a * route to the router.
     *
     * This route will listen to any method using the given path.
     * @param string $path
     * @param callable $action
     * @param mixed $params
     * @return void
     * @throws PhpVersionNotSupportedException
     */
    public function use(string $path, callable $action, mixed ...$params): void
    {
        $this->addRoute(new Route(HttpMethod::ALL, $path, $action, ...$params));
    }

    /**
     * Add a RouterRoute to this router.
     * @param string $path
     * @param Router $router
     * @return void
     * @throws PhpVersionNotSupportedException
     */
    public function route(string $path, Router $router): void
    {
        $this->addRoute(new RouterRoute($path, $router));
    }

    /**
     * Create a GET route for the complete folder
     * @param string $path
     * @param string $folder
     * @return void
     * @throws PhpVersionNotSupportedException
     * @throws FolderNotFoundException when the given folder does not exist
     */
    public function getStatic(string $path, string $folder): void
    {
        $this->addRoute(new StaticRoute($path, $folder));

    }
}