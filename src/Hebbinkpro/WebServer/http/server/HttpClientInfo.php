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

namespace Hebbinkpro\WebServer\http\server;

use Hebbinkpro\WebServer\socket\SocketClientInfo;

/**
 * Class to store a read-only state of the HTTP client, without access to the socket.
 */
readonly class HttpClientInfo extends SocketClientInfo
{

    private bool $closed;
    /** @var int The time when the client was last active (unix time in seconds) */
    private int $lastActivity;

    private int $servedRequests;

    public function __construct(string $host, int $port, bool $closed, int $lastActivity, int $servedRequests)
    {
        parent::__construct($host, $port);
        $this->closed = $closed;
        $this->lastActivity = $lastActivity;
        $this->servedRequests = $servedRequests;
    }

    /**
     * @return bool
     */
    public function isClosed(): bool
    {
        return $this->closed;
    }

    /**
     * @return int
     */
    public function getLastActivity(): int
    {
        return $this->lastActivity;
    }

    /**
     * @return int
     */
    public function getServedRequests(): int
    {
        return $this->servedRequests;
    }

}