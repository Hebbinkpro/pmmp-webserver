<?php
/*
 * MIT License
 *
 * Copyright (c) 2026 Hebbinkpro
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

use Hebbinkpro\WebServer\exception\HttpProblemException;
use Hebbinkpro\WebServer\http\message\request\HttpRequest;
use Hebbinkpro\WebServer\http\message\response\HttpResponse;
use Hebbinkpro\WebServer\http\message\response\HttpResponseBuilder;
use Hebbinkpro\WebServer\http\server\HttpClientInfo;
use Hebbinkpro\WebServer\router\middleware\Middleware;
use Hebbinkpro\WebServer\router\middleware\MiddlewareResponseType;

/**
 * Implementation of middleware handling
 */
final class MiddlewareRoute implements Route
{
	private Middleware $middleware;
	private Route $next;

	public function __construct(Middleware $middleware, Route $next)
	{
		$this->middleware = $middleware;
		$this->next = $next;
	}

	/**
	 * @inheritDoc
	 * @throws HttpProblemException if the middleware reqquest validation returned an error
	 */
	public function handleRequest(HttpClientInfo $client, HttpRequest $req, ?HttpResponseBuilder $res = null): HttpResponse
	{
		if ($res === null) $res = HttpResponseBuilder::fromRequest($req);
		$validation = $this->middleware->validateRequest($req, $res);

		return match ($validation->getType()) {
			MiddlewareResponseType::NEXT => $this->next->handleRequest($client, $req, $res),
			MiddlewareResponseType::STOP => $res->build($client),
			MiddlewareResponseType::ERROR => throw HttpProblemException::badRequest(detail: $validation->getReason()),
		};
	}

	/**
	 * @return Middleware
	 */
	public function getMiddleware(): Middleware
	{
		return $this->middleware;
	}

	/**
	 * @return Route
	 */
	public function getNext(): Route
	{
		return $this->next;
	}
}