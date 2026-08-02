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

use Closure;
use Exception;
use Hebbinkpro\WebServer\http\HttpMethod;
use Hebbinkpro\WebServer\http\message\request\HttpRequest;
use Hebbinkpro\WebServer\http\message\response\HttpResponse;
use Hebbinkpro\WebServer\http\message\response\HttpResponseBuilder;
use Hebbinkpro\WebServer\http\message\response\HttpResponseFactory;
use Hebbinkpro\WebServer\http\server\HttpClientInfo;
use Hebbinkpro\WebServer\http\server\HttpServer;
use Hebbinkpro\WebServer\libs\Laravel\SerializableClosure\SerializableClosure;
use Hebbinkpro\WebServer\utils\ThreadSafeUtils;
use pmmp\thread\ThreadSafeArray;

class ActionRoute extends BaseRoute
{

	private ?string $action;
	private ThreadSafeArray $threadSafeParams;

	/**
	 * @param HttpMethod $method the request method
	 * @param (Closure(HttpRequest $req, HttpResponseBuilder $res, mixed ...$params): void)|null $action the action to execute
	 * @param mixed ...$params additional (thread safe) parameters to use in the action
	 */
	public function __construct(HttpMethod $method, ?Closure $action, mixed ...$params)
	{
		parent::__construct($method);

		$this->action = null;

		if ($action !== null) {
			$serializable = new SerializableClosure($action);
			$this->action = serialize($serializable);
		}

		// make the array thread safe
		$this->threadSafeParams = ThreadSafeUtils::makeThreadSafeArray($params);
	}

	/**
	 * Handle the client request by executing a given action
	 * @param HttpClientInfo $client
	 * @param HttpRequest $req
	 * @param HttpResponseBuilder|null $res
	 * @return HttpResponse the response to send back to the client
	 */
	public function handleRequest(HttpClientInfo $client, HttpRequest $req, ?HttpResponseBuilder $res = null): HttpResponse
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

		if ($res === null) $res = HttpResponseBuilder::fromRequest($req);

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


}