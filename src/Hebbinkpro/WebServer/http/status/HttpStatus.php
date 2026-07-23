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

namespace Hebbinkpro\WebServer\http\status;

use pmmp\thread\ThreadSafe;

/**
 * HTTP Status used for an HTTP Response
 */
class HttpStatus extends ThreadSafe
{
    private int $code;
    private string $message;
    private ?string $uriReference;

    public function __construct(int $code, string $message, ?string $uriReference = null)
    {
        $this->code = $code;
        $this->message = $message;
        $this->uriReference = $uriReference;
    }

    /**
     * Get the status code
     * @return int
     */
    public function getCode(): int
    {
        return $this->code;
    }

    /**
     * Get the status message
     * @return string
     */
    public function getMessage(): string
    {
        return $this->message;
    }

    /**
     * Get the URI reference of the status code, if null it returns the MDN docs for standard codes
     * @return string
     */
    public function getUriReference(): string
    {
        return $this->uriReference ?? "https://developer.mozilla.org/en-US/docs/Web/HTTP/Reference/Status/$this->code";
    }

    /**
     * @return string
     */
    public function toString(): string
    {
        return $this->code . " " . $this->message;
    }

    /**
     * Get if the status is informational (100-199)
     * @return bool
     */
    public function isInformational(): bool
    {
        return $this->code >= 100 && $this->code < 200;
    }

    /**
     * Get if the status is successful (200-299)
     * @return bool
     */
    public function isSuccessful(): bool
    {
        return $this->code >= 200 && $this->code < 300;
    }

    /**
     * Get if the status is a redirection (300-399)
     * @return bool
     */
    public function isRedirection(): bool
    {
        return $this->code >= 300 && $this->code < 400;
    }

    /**
     * Get if the status is a client error (400-499)
     * @return bool
     */
    public function isClientError(): bool
    {
        return $this->code >= 400 && $this->code < 500;
    }

    /**
     * Get if the status is a server error (500-599)
     * @return bool
     */
    public function isServerError(): bool
    {
        return $this->code >= 500 && $this->code < 600;
    }

    /**
     * Get if the status code is not allowed to return a message body
     * @return bool true for all 1xx (Informational), 204 (No Content), and 304 (Not Modified) responses.
     */
    public function canNotHaveBody(): bool
    {
        return $this->isInformational()
            || $this->code === HttpStatusCodes::NO_CONTENT
            || $this->code === HttpStatusCodes::NOT_MODIFIED;
    }
}