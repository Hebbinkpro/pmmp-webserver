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

namespace Hebbinkpro\WebServer\router\middleware;

class MiddlewareResponse
{

	public function __construct(private MiddlewareResponseType $type, private string $reason = "")
	{

	}

	/**
	 * Create a NEXT middleware response to continue the request handling
	 * @return MiddlewareResponse
	 */
	public static function next(): MiddlewareResponse
	{
		return new MiddlewareResponse(MiddlewareResponseType::NEXT);
	}

	/**
	 * Create a STOP middleware response to stop the request handling
	 * @return MiddlewareResponse
	 */
	public static function stop(): MiddlewareResponse
	{
		return new MiddlewareResponse(MiddlewareResponseType::STOP);
	}

	/**
	 * Create a ERROR middleware response to stop the request handling with an error message
	 * @param string $reason the reason of the error
	 * @return MiddlewareResponse
	 */
	public static function error(string $reason): MiddlewareResponse
	{
		return new MiddlewareResponse(MiddlewareResponseType::ERROR, $reason);
	}

	/**
	 * @return MiddlewareResponseType
	 */
	public function getType(): MiddlewareResponseType
	{
		return $this->type;
	}

	/**
	 * @return string
	 */
	public function getReason(): string
	{
		return $this->reason;
	}

}
