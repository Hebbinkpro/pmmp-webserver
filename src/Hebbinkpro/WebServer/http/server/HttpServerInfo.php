<?php

namespace Hebbinkpro\WebServer\http\server;

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
     * @param int $port
     * @param Router|null $router
     * @param SslSettings|null $ssl
     */
    public function __construct(string $host, int $port, ?Router $router = null, ?SslSettings $ssl = null)
    {
        $this->host = $host;
        $this->port = $port;
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