<?php

namespace Hebbinkpro\WebServer\route;

use Exception;
use Hebbinkpro\WebServer\http\HttpMethod;
use Hebbinkpro\WebServer\http\HttpRequest;
use Hebbinkpro\WebServer\http\HttpResponse;
use Hebbinkpro\WebServer\WebClient;
use Hebbinkpro\WebServer\WebServer;

class Router
{
    private WebServer $server;
    /** @var Route[] */
    private array $routes;

    public function __construct(WebServer $server)
    {
        $this->server = $server;
        $this->routes = [];
    }

    /**
     * Handle an incoming request
     * @param WebClient $client
     * @param HttpRequest $request
     * @return void
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
     * Get a route from a request
     * @param HttpRequest $req
     * @return Route|null
     */
    public function getRoute(HttpRequest $req): ?Route
    {

        return $this->getRouteByPath($req->getMethod(), $req->getURL()->getPath());
    }


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
     * @return void
     */
    public function get(string $path, callable $action): void
    {
        $this->addRoute(new Route(HttpMethod::GET, $path, $action));
    }

    /**
     * Add a route to the router
     * @param Route $route
     * @return void
     */
    public function addRoute(Route $route): void
    {
        $this->routes[] = $route;
    }

    /**
     * Add a POST route to the router
     * @param string $path
     * @param callable $action
     * @return void
     */
    public function post(string $path, callable $action): void
    {
        $this->addRoute(new Route(HttpMethod::POST, $path, $action));
    }

    /**
     * Add a HEAD route to the router
     * @param string $path
     * @param callable $action
     * @return void
     */
    public function head(string $path, callable $action): void
    {
        $this->addRoute(new Route(HttpMethod::HEAD, $path, $action));
    }

    /**
     * Add a PUT route to the router
     * @param string $path
     * @param callable $action
     * @return void
     */
    public function put(string $path, callable $action): void
    {
        $this->addRoute(new Route(HttpMethod::PUT, $path, $action));
    }

    /**
     * Add a DELETE route to the router
     * @param string $path
     * @param callable $action
     * @return void
     */
    public function delete(string $path, callable $action): void
    {
        $this->addRoute(new Route(HttpMethod::DELETE, $path, $action));
    }

    /**
     * Add a route to the router that listens to all methods
     * @param string $path
     * @param callable $action
     * @return void
     */
    public function use(string $path, callable $action): void
    {
        $this->addRoute(new Route(HttpMethod::ALL, $path, $action));
    }

    /**
     * Add a child router to this router.
     * @param string $path
     * @param Router $router
     * @return void
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
     */
    public function getStatic(string $path, string $folder): void
    {
        try {
            $this->addRoute(new StaticRoute($path, $folder));
        } catch (Exception $e) {
            // log the error
            $this->server->getPlugin()->getLogger()->warning("Could not create static route: '$path'. Folder '$folder' does not exist");
        }
    }
}