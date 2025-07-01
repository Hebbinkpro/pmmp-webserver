<?php
/*
 * MIT License
 *
 * Copyright (c) 2025 Hebbinkpro
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

use Closure;
use Hebbinkpro\WebServer\http\message\HttpRequest;
use Hebbinkpro\WebServer\http\message\HttpResponse;
use Hebbinkpro\WebServer\http\server\HttpClient;
use Hebbinkpro\WebServer\http\status\HttpStatus;
use Hebbinkpro\WebServer\http\status\HttpStatusCodes;

/**
 * Interface for a basic HTTP request router
 */
interface RouterInterface
{
    /**
     * Handle an incoming client request
     * @param HttpClient $client the client
     * @param HttpRequest $request the request from the client
     * @return void
     */
    public function handleRequest(HttpClient $client, HttpRequest $request): void;


    /**
     * Reject an incoming client request
     * @param HttpClient $client the client
     * @param int|HttpStatus $status the status code indicating the error
     * @return void
     */
    public function rejectRequest(HttpClient $client, int|HttpStatus $status = HttpStatusCodes::BAD_REQUEST): void;

    /**
     * Add a GET route to the router
     * @param string $path
     * @param Closure(HttpRequest $req, HttpResponse $res, mixed ...$params): void $action
     * @param mixed ...$params
     * @return void
     */
    public function get(string $path, Closure $action, mixed ...$params): void;

    /**
     * Add a POST route to the router
     * @param string $path
     * @param Closure(HttpRequest $req, HttpResponse $res, mixed ...$params): void $action
     * @param mixed $params
     * @return void
     */
    public function post(string $path, Closure $action, mixed ...$params): void;

    /**
     * Add a HEAD route to the router
     * @param string $path
     * @param Closure(HttpRequest $req, HttpResponse $res, mixed ...$params): void $action
     * @param mixed $params
     * @return void
     */
    public function head(string $path, Closure $action, mixed ...$params): void;

    /**
     * Add a PUT route to the router
     * @param string $path
     * @param Closure(HttpRequest $req, HttpResponse $res, mixed ...$params): void $action
     * @param mixed $params
     * @return void
     */
    public function put(string $path, Closure $action, mixed ...$params): void;

    /**
     * Add a DELETE route to the router
     * @param string $path
     * @param Closure(HttpRequest $req, HttpResponse $res, mixed ...$params): void $action
     * @param mixed $params
     * @return void
     */
    public function delete(string $path, Closure $action, mixed ...$params): void;

    /**
     * Add a * route to the router.
     *
     * This route will listen to any method using the given path.
     * @param string $path
     * @param Closure(HttpRequest $req, HttpResponse $res, mixed ...$params): void $action
     * @param mixed $params
     * @return void
     */
    public function all(string $path, Closure $action, mixed ...$params): void;
}