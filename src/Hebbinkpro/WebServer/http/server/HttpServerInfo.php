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

namespace Hebbinkpro\WebServer\http\server;

use Hebbinkpro\WebServer\http\HttpConstants;
use Hebbinkpro\WebServer\router\Router;
use pmmp\thread\ThreadSafe;

/**
 * Thread safe information for the HTTP Server and its clients
 */
class HttpServerInfo extends ThreadSafe
{
    private string $host;
    private int $port;
    private Router $router;
    private ?SslSettings $ssl;

    /**
     * @param string $host
     * @param int $port If a negative port is given, the default HTTP port (or HTTPS port when SSL is given) will be used
     * @param Router|null $router
     * @param SslSettings|null $ssl
     */
    public function __construct(string $host, int $port = -1, ?Router $router = null, ?SslSettings $ssl = null)
    {
        $this->host = $host;
        $this->port = $port >= 0 ? $port :
            ($ssl === null ? HttpConstants::DEFAULT_HTTP_PORT : HttpConstants::DEFAULT_HTTPS_PORT);
        $this->router = $router ?? new Router();
        $this->ssl = $ssl;
    }

    /**
     * Get the host the HTTP server is running on
     * @return string
     */
    public function getHost(): string
    {
        return $this->host;
    }

    /**
     * Get the port the HTTP server is running on
     * @return int
     */
    public function getPort(): int
    {
        return $this->port;
    }


    /**
     * Get the router used for routing HTTP requests
     * @return Router
     */
    public function getRouter(): Router
    {
        return $this->router;
    }

    /**
     * Check if the SSL cert and pk are available
     */
    public function isSslEnabled(): bool
    {
        return $this->ssl !== null;
    }

    /**
     * @return SslSettings|null
     */
    public function getSsl(): ?SslSettings
    {
        return $this->ssl;
    }

    /**
     * @param SslSettings|null $ssl
     */
    public function setSsl(?SslSettings $ssl): void
    {
        $this->ssl = $ssl;
    }

    /**
     * Get the address (scheme://host:port)
     * @return string
     */
    public function getAddress(): string
    {
        return $this->getScheme() . "://" . $this->host . ":" . $this->port;
    }

    /**
     * Get the address scheme used for the address
     * @return string
     */
    public function getScheme(): string
    {
        return "tcp";
    }
}