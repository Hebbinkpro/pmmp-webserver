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

use LogLevel;
use pocketmine\thread\log\ThreadSafeLogger;

class SimpleThreadSafeLogger extends ThreadSafeLogger
{
    public function emergency($message): void
    {
        $this->log(LogLevel::EMERGENCY, $message);
    }

    public function alert($message): void
    {
        $this->log(LogLevel::ALERT, $message);
    }

    public function critical($message): void
    {
        $this->log(LogLevel::CRITICAL, $message);
    }

    public function error($message): void
    {
        $this->log(LogLevel::ERROR, $message);
    }

    public function warning($message): void
    {
        $this->log(LogLevel::WARNING, $message);
    }

    public function notice($message): void
    {
        $this->log(LogLevel::NOTICE, $message);
    }

    public function info($message): void
    {
        $this->log(LogLevel::INFO, $message);
    }

    public function debug($message): void
    {
        $this->log(LogLevel::DEBUG, $message);
    }

    public function log($level, $message): void
    {
        $this->sendSynchronized("[" . strtoupper($level) . "] " . $message . PHP_EOL);
    }

    public function logException(\Throwable $e, $trace = null): void
    {
        $this->critical($e->getMessage());
        $this->sendSynchronized($e->getTraceAsString());
    }

    protected function sendSynchronized($message): void
    {
        $this->synchronized(function () use ($message): void {
            // send a synchronized message
            echo $message;
        });
    }
}