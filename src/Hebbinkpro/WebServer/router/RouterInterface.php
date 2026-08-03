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
use Hebbinkpro\WebServer\http\message\request\HttpRequest;
use Hebbinkpro\WebServer\http\message\response\HttpResponse;
use Hebbinkpro\WebServer\http\message\response\HttpResponseBuilder;
use Hebbinkpro\WebServer\http\server\HttpClientInfo;
use Hebbinkpro\WebServer\router\middleware\MiddlewareResponse;

/**
 * Interface for a basic HTTP request router
 */
interface RouterInterface
{
    /**
     * Handle an incoming client request
     * @param HttpClientInfo $client the client
     * @param HttpRequest $request the request from the client
     * @param HttpResponseBuilder|null $response [optional] a partial response builder that can be completed
     * @return HttpResponse the response to send back to the client
     */
	public function handleRequest(HttpClientInfo $client, HttpRequest $request, ?HttpResponseBuilder $response = null): HttpResponse;

    /**
     * Add a GET route to the router
     * @param string $path
     * @param Closure(HttpRequest $req, HttpResponseBuilder $res, mixed ...$params): void $action
     * @param mixed ...$params
     * @return void
     */
    public function get(string $path, Closure $action, mixed ...$params): void;

    /**
     * Add a POST route to the router
     * @param string $path
     * @param Closure(HttpRequest $req, HttpResponseBuilder $res, mixed ...$params): void $action
     * @param mixed $params
     * @return void
     */
    public function post(string $path, Closure $action, mixed ...$params): void;

    /**
     * Add a HEAD route to the router
     * @param string $path
     * @param Closure(HttpRequest $req, HttpResponseBuilder $res, mixed ...$params): void $action
     * @param mixed $params
     * @return void
     */
    public function head(string $path, Closure $action, mixed ...$params): void;

    /**
     * Add a PUT route to the router
     * @param string $path
     * @param Closure(HttpRequest $req, HttpResponseBuilder $res, mixed ...$params): void $action
     * @param mixed $params
     * @return void
     */
    public function put(string $path, Closure $action, mixed ...$params): void;

    /**
     * Add a DELETE route to the router
     * @param string $path
     * @param Closure(HttpRequest $req, HttpResponseBuilder $res, mixed ...$params): void $action
     * @param mixed $params
     * @return void
     */
    public function delete(string $path, Closure $action, mixed ...$params): void;

    /**
     * Add a * route to the router.
     *
     * This route will listen to any method using the given path.
     * @param string $path
     * @param Closure(HttpRequest $req, HttpResponseBuilder $res, mixed ...$params): void $action
     * @param mixed $params
     * @return void
     */
    public function all(string $path, Closure $action, mixed ...$params): void;

	/**
	 * Add a middleware to a route path
	 *
	 * Middleware will be executed on any request target that contains the path to the middleware
	 * @param string $path
	 * @param Closure(HttpRequest $request, HttpResponseBuilder $response, mixed ...$params): MiddlewareResponse $action
	 * @param mixed ...$params
	 * @return void
	 */
	public function use(string $path, Closure $action, mixed ...$params): void;
}