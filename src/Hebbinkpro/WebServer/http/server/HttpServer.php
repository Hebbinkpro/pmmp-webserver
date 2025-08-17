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

use Exception;
use Hebbinkpro\WebServer\exception\SocketNotCreatedException;
use Hebbinkpro\WebServer\http\status\HttpStatusCodes;
use LogicException;
use pocketmine\thread\log\ThreadSafeLogger;
use pocketmine\thread\Thread;
use pocketmine\thread\ThreadSafeClassLoader;

class HttpServer extends Thread
{
    /** @var int Time in milliseconds to wait before serving sockets */
    public const SOCKET_SERVE_TIMEOUT = 100000;

    private static ?self $instance = null;


    /** @var resource */
    private static mixed $socket;

    /**
     * @var array<string, HttpClient>
     */
    private static array $clients = [];

    private HttpServerInfo $serverInfo;

    private bool $isSecure = false;

    private ThreadSafeLogger $logger;


    /**
     * @param HttpServerInfo $serverInfo
     * @param ThreadSafeClassLoader $classLoader
     * @param ThreadSafeLogger $logger
     */
    public function __construct(HttpServerInfo $serverInfo, ThreadSafeClassLoader $classLoader, ThreadSafeLogger $logger)
    {
//        if (self::$instance !== null) {
//            throw new LogicException("Only one HttpServer instance can exist at once");
//        }
//        self::$instance = $this;

        $this->serverInfo = $serverInfo;
        $this->setClassLoaders([$classLoader]);
        $this->logger = $logger;
    }

    public static function getInstance(): HttpServer
    {
        if (self::$instance === null) {
            throw new LogicException("Cannot access the HttpServer instance outside the HttpServer thread!");
        }

        return self::$instance;
    }

    /**
     * @return HttpServerInfo
     */
    public function getServerInfo(): HttpServerInfo
    {
        return $this->serverInfo;
    }

    /**
     * @return ThreadSafeLogger
     */
    public function getLogger(): ThreadSafeLogger
    {
        return $this->logger;
    }

    /**
     * @throws SocketNotCreatedException
     */
    protected function onRun(): void
    {
        // ensure that the instance is set
        self::$instance = $this;

        // create a stream context
        $context = stream_context_create();

        if (($ssl = $this->serverInfo->getSsl()) !== null) {
            stream_context_set_option($context, $ssl->getContextOptions());
            $this->isSecure = true;
        }

        // create the socket
        $socket = stream_socket_server(
            $this->serverInfo->getSocketBindAddress(),
            $eCode,
            $eMsg,
            STREAM_SERVER_BIND | STREAM_SERVER_LISTEN,
            $context
        );

        // if there is no socket created, throw the exception with the error.
        if (!is_resource($socket)) {
            throw new SocketNotCreatedException($eMsg);
        }

        self::$socket = $socket;

        // disable blocking
        stream_set_blocking(self::$socket, false);

        $this->logger->notice("The WebServer is now running!");

        // start the loop to listen for new clients until the thread is joined or the socket is invalid
        while (!$this->isJoined() && is_resource(self::$socket)) {

            $this->serveNewConnections();
            $this->serveExistingConnections();

            // wait a bit to reduce cpu load and to wait for new data
            usleep(self::SOCKET_SERVE_TIMEOUT);
        }

        $this->logger->notice("Shutting down the WebServer...");

        // close all connections
        $this->close();
    }

    /**
     * Serve all incoming connections
     * @return void
     */
    private function serveNewConnections(): void
    {
        $client = null;
        try {
            $incoming = stream_socket_accept(self::$socket, 0, $clientName);
            if (!is_resource($incoming)) return;

            [$host, $port] = explode(":", $clientName);
            $client = new HttpClient($host, intval($port), $incoming);
            $this->logger->debug("Got new connection from: $clientName");

            if ($this->isSecure) {
                // enable crypto, blocking should be enabled for this
                stream_set_blocking($incoming, true);
                $ok = stream_socket_enable_crypto($incoming, true, STREAM_CRYPTO_METHOD_TLS_SERVER);
                if ($ok !== true) {
                    $this->logger->warning("TLS handshake failed for $clientName");
                    fclose($incoming);
                    return;
                }
            }

            // set the stream to not blocking
            stream_set_blocking($incoming, false);

            // add the client to the cache
            self::$clients[$clientName] = $client;
        } catch (Exception $e) {
            // only log if client broke something, otherwise this results in a constant socket read error
            if ($client !== null) {
                $this->logger->debug("Error accepting connection: " . $e->getMessage());

                // close the client if it was created
                $client->close();
            }

        }
    }

    /**
     * Close the server socket
     * @return void
     */
    private function close(): void
    {
        // close all clients
        foreach (self::$clients as $client) {
            $client->close();
        }
        self::$clients = [];

        stream_socket_shutdown(self::$socket, STREAM_SHUT_RDWR);
        fclose(self::$socket);
    }

    /**
     * Serve all active clients
     * @return void
     */
    private function serveExistingConnections(): void
    {
        // list of clients that need to be closed after all connections have been served
        $closed = [];

        // timeout keeping
        $now = time();
        $timeout = $this->serverInfo->getKeepAliveTimeout();
        $hasTimeout = $timeout > 0;

        // go through all clients
        foreach (self::$clients as $name => $client) {

            // check if the socket is still open
            if (!$client->isAvailable()) {
                $closed[] = $name;
                continue;
            }

            // connection timeout
            if ($hasTimeout && $now - $client->getLastActivity() > $timeout) {
                $closed[] = $name;
                continue;
            }

            // catch all exceptions to close the client
            try {
                // handle the data
                $client->serve();

                // close connection
                if ($client->isClosed()) $closed[] = $name;

            } catch (Exception $e) {
                $this->logger->error("Got an error while handling $name. " . $e->getMessage());
                $this->serverInfo->getRouter()->rejectRequest($client, HttpStatusCodes::INTERNAL_SERVER_ERROR);
                $closed[] = $name;
            }
        }

        // close and remove all clients that are marked as closed
        foreach ($closed as $name) {
            // make sure everything is sent to the client
            self::$clients[$name]->flush();

            // close the connection and remove the client
            self::$clients[$name]->close();
            unset(self::$clients[$name]);

            $this->logger->debug("Closed connection with $name");
        }
    }
}