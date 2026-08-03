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

namespace Hebbinkpro\WebServer\router\middleware;

use Closure;
use Exception;
use Hebbinkpro\WebServer\http\message\request\HttpRequest;
use Hebbinkpro\WebServer\http\message\response\HttpResponseBuilder;
use Hebbinkpro\WebServer\libs\Laravel\SerializableClosure\SerializableClosure;
use Hebbinkpro\WebServer\utils\ThreadSafeUtils;
use pmmp\thread\ThreadSafeArray;

/**
 * A middleware that executes a user-defined closure function
 */
final class ActionMiddleware extends BaseMiddleware
{

	private string $action;
	private ThreadSafeArray $threadSafeParams;

	/**
	 * @param (Closure(HttpRequest $request, HttpResponseBuilder $response, mixed ...$params): MiddlewareResponse) $action the action to execute
	 * @param mixed ...$params additional (thread safe) parameters to use in the action
	 */
	public function __construct(Closure $action, mixed ...$params)
	{
		$serializable = new SerializableClosure($action);
		$this->action = serialize($serializable);

		// make the array thread safe
		$this->threadSafeParams = ThreadSafeUtils::makeThreadSafeArray($params);
	}

	public function validateRequest(HttpRequest $request, HttpResponseBuilder $response): MiddlewareResponse
	{
		/** @var SerializableClosure $action */
		$action = unserialize($this->action);

		// no action to handle the request
		if ($action === null) {
			return MiddlewareResponse::error("Action could not be unserialized");
		}

		try {
			// ensure that the values are unwrapped before passing them on to the closure
			$params = ThreadSafeUtils::unwrapThreadSafeArray($this->threadSafeParams);

			// execute the closure with the request, response and parameters
			$validation = call_user_func($action->getClosure(), $request, $response, ...$params);
		} catch (Exception $e) {
			return MiddlewareResponse::error("Error while validating request: " . $e->getMessage());
		}


		return $validation;
	}
}