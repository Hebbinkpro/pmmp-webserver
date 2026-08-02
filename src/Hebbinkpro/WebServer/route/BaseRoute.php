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

namespace Hebbinkpro\WebServer\route;

use Closure;
use Exception;
use Hebbinkpro\WebServer\exception\RouteInUseException;
use Hebbinkpro\WebServer\http\HttpMethod;
use Hebbinkpro\WebServer\http\message\request\HttpRequest;
use Hebbinkpro\WebServer\http\message\response\HttpResponse;
use Hebbinkpro\WebServer\http\message\response\HttpResponseBuilder;
use Hebbinkpro\WebServer\http\message\response\HttpResponseFactory;
use Hebbinkpro\WebServer\http\server\HttpClientInfo;
use Hebbinkpro\WebServer\http\server\HttpServer;
use Hebbinkpro\WebServer\http\uri\UriPath;
use Hebbinkpro\WebServer\libs\Laravel\SerializableClosure\SerializableClosure;
use Hebbinkpro\WebServer\utils\ThreadSafeUtils;
use pmmp\thread\ThreadSafe;
use pmmp\thread\ThreadSafeArray;

/**
 * A route that handles a client request for a specific path
 */
class BaseRoute extends ThreadSafe
{
    private HttpMethod $method;
    private ?string $action;
    private ThreadSafeArray $threadSafeParams;
    private ?UriPath $path;

    /**
     * @param HttpMethod $method the request method
     * @param (Closure(HttpRequest $req, HttpResponseBuilder $res, mixed ...$params): void)|null $action the action to execute
     * @param mixed ...$params additional (thread safe) parameters to use in the action
     */
    public function __construct(HttpMethod $method, ?Closure $action, mixed ...$params)
    {
        $this->method = $method;
        $this->action = null;

        if ($action !== null) {
            $serializable = new SerializableClosure($action);
            $this->action = serialize($serializable);
        }

        // make the array thread safe
        $this->threadSafeParams = ThreadSafeUtils::makeThreadSafeArray($params);

        $this->path = null;
    }

    /**
     * Handle the client request by executing the action
     * @param HttpClientInfo $client the client
     * @param HttpRequest $req the request of the client
     * @return HttpResponse the response to send back to the client
     */
    public function handleRequest(HttpClientInfo $client, HttpRequest $req): HttpResponse
    {
        if ($this->action === null) {
            return HttpResponseFactory::notImplemented()->build($client);
        }

        /** @var SerializableClosure|null $action */
        $action = unserialize($this->action);

        // no action to handle the request
        if ($action === false || $action === null) {
            return HttpResponseFactory::notImplemented()->build($client);
        }

        // response to be sent back to the client, and make sure HEAD requests send a response without content
        if ($req->getMethod() === HttpMethod::HEAD) $res = new HttpResponseBuilder(true);
        else $res = new HttpResponseBuilder();

        try {
            // ensure that the values are unwrapped before passing them on to the closure
            $params = ThreadSafeUtils::unwrapThreadSafeArray($this->threadSafeParams);

            // execute the closure with the request, response and parameters
            call_user_func($action->getClosure(), $req, $res, ...$params);
        } catch (Exception $e) {
            HttpServer::getInstance()->getLogger()->error("Error while handling request: " . $e->getMessage());
            return HttpResponseFactory::internalServerError()->build($client);
        }


        return $res->build($client);
    }

    /**
     * Get the HTTP method
     * @return HttpMethod
     */
    public function getMethod(): HttpMethod
    {
        return $this->method;
    }

    /**
     * @param UriPath $path
     * @throws RouteInUseException if the route is already bound to a path
     */
    public function setPath(UriPath $path): void
    {
        if ($this->path !== null) throw new RouteInUseException("Route provided for '" . $path->toString() . "' is already in use at '" . $this->path->toString() . "'");
        $this->path = $path;
    }

    /**
     * @return UriPath|null
     */
    public function getPath(): ?UriPath
    {
        return $this->path;
    }
}