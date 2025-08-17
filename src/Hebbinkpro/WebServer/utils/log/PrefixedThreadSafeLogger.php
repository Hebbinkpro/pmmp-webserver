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

namespace Hebbinkpro\WebServer\utils\log;

use pocketmine\thread\log\ThreadSafeLogger;
use Throwable;


class PrefixedThreadSafeLogger extends SimpleThreadSafeLogger
{
    private ThreadSafeLogger $delegate;
    private string $prefix;

    public function __construct(ThreadSafeLogger $delegate, string $prefix)
    {
        $this->delegate = $delegate;
        $this->prefix = $prefix;
    }

    /**
     * @inheritDoc
     */
    public function log($level, $message): void
    {
        $this->delegate->log($level, "[$this->prefix] $message");
    }

    /**
     * @inheritDoc
     */
    public function logException(Throwable $e, $trace = null): void
    {
        $this->delegate->logException($e, $trace);
    }

    /**
     * @return string
     */
    public function getPrefix(): string
    {
        return $this->prefix;
    }

    /**
     * @param string $prefix
     */
    public function setPrefix(string $prefix): void
    {
        $this->prefix = $prefix;
    }
}