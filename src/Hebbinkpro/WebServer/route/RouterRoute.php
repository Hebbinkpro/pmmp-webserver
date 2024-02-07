<?php

namespace Hebbinkpro\WebServer\route;

use Hebbinkpro\WebServer\http\HttpMethod;
use Hebbinkpro\WebServer\http\message\HttpRequest;
use Hebbinkpro\WebServer\http\server\HttpClient;
use Hebbinkpro\WebServer\libs\Laravel\SerializableClosure\Exceptions\PhpVersionNotSupportedException;
use Hebbinkpro\WebServer\router\Router;

/**
 * A Route that functions like a Router
 */
class RouterRoute extends Route
{
    private Router $router;

    /**
     * @param Router $router
     * @throws PhpVersionNotSupportedException
     */
    public function __construct(Router $router)
    {
        $this->router = $router;

        parent::__construct(HttpMethod::ALL, null);
    }

    public function handleRequest(HttpClient $client, HttpRequest $req): void
    {
        // let the router inside this route handle the request
        $this->router->handleRequest($client, $req);
    }

    /**
     * @return Router
     */
    public function getRouter(): Router
    {
        return $this->router;
    }
}