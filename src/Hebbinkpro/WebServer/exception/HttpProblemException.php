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

namespace Hebbinkpro\WebServer\exception;

use Hebbinkpro\WebServer\http\HttpProblem;
use Hebbinkpro\WebServer\http\status\HttpStatus;
use Hebbinkpro\WebServer\http\status\HttpStatusCodes;

class HttpProblemException extends HttpException
{
    public function __construct(HttpStatus|int $statusCode, string $instance, ?string $detail = null)
    {
        parent::__construct(new HttpProblem($statusCode, $instance, $detail));
    }

    /**
     * 400 bad request exception without any details and an about:blank instance
     * @param string|null $instance instance to use, if not present, a blank instance is used
     * @param string|null $detail detail to use, if not present, no detail is used
     * @return HttpProblemException
     */
    public static function badRequest(?string $instance = null, ?string $detail = null): HttpProblemException
    {
	    $instance = $instance ?? "about:blank";
	    return new HttpProblemException(HttpStatusCodes::BAD_REQUEST, $instance, $detail);
    }

	/**
	 * 500 internal server error exception without any details and an about:blank instance
	 * @param string|null $instance instance to use, if not present, a blank instance is used
	 * @param string|null $detail detail to use, if not present, no detail is used
	 * @return HttpProblemException
	 */
	public static function internalServerError(?string $instance = null, ?string $detail = null): HttpProblemException
	{
		$instance = $instance ?? "about:blank";
		return new HttpProblemException(HttpStatusCodes::INTERNAL_SERVER_ERROR, $instance, $detail);
	}

	/**
	 * Http Problem with a blank instance
	 * @param HttpStatus|int $statusCode the HTTP status code
	 * @param string|null $detail detail to use, if not present, no detail is used
	 * @return HttpProblemException
	 */
	public static function blankInstance(HttpStatus|int $statusCode, ?string $detail = null): HttpProblemException
    {
	    return new HttpProblemException($statusCode, "about:blank", $detail);
    }
}