<?php

namespace Hebbinkpro\WebServer\route;

use Hebbinkpro\WebServer\http\HttpMethod;
use Hebbinkpro\WebServer\http\HttpRequest;
use Hebbinkpro\WebServer\http\HttpResponse;
use Hebbinkpro\WebServer\http\HttpUrl;
use Hebbinkpro\WebServer\http\status\HttpStatusCodes;
use Hebbinkpro\WebServer\libs\Laravel\SerializableClosure\Exceptions\PhpVersionNotSupportedException;
use Hebbinkpro\WebServer\WebClient;

/**
 * A Router that is called as Route which can handle requests for sub path's
 */
class RouterRoute extends Route
{
    private Router $router;

    /**
     * @param string $path
     * @param Router $router
     * @throws PhpVersionNotSupportedException
     */
    public function __construct(string $path, Router $router)
    {
        $this->router = $router;

        parent::__construct(HttpMethod::ALL, $path . "/*", null);
    }

    public function handleRequest(WebClient $client, HttpRequest $req): void
    {
        // TODO: figure out why I didn't use $this->router->handleRequest($client, $req)

        // get a route from the request
        $route = $this->router->getRouteByPath($req->getMethod(), HttpUrl::getSubPath($req->getURL()->getPath(), $this->getPath()));

        // no route is found
        if ($route === null) {
            $res = new HttpResponse($client);
            $res->setStatus(HttpStatusCodes::NOT_F0UND);
            $res->end();
            return;
        }

        $route->handleRequest($client, $req);
    }

    /**
     * @return Router
     */
    public function getRouter(): Router
    {
        return $this->router;
    }
}