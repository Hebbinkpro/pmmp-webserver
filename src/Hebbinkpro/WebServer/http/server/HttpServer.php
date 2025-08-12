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
use Hebbinkpro\WebServer\http\HttpConstants;
use Hebbinkpro\WebServer\http\HttpHeaders;
use Hebbinkpro\WebServer\http\message\HttpRequest;
use Hebbinkpro\WebServer\http\message\parser\HttpRequestParser;
use Hebbinkpro\WebServer\http\status\HttpStatusCodes;
use pocketmine\thread\log\ThreadSafeLogger;
use pocketmine\thread\Thread;
use pocketmine\thread\ThreadSafeClassLoader;

class HttpServer extends Thread
{
    /**
     * @var resource
     */
    private static mixed $socket = null;

    /**
     * @var HttpClient[]
     */
    private static array $clients = [];

    private HttpServerInfo $serverInfo;
    private bool $isSecure = false;

    private ThreadSafeLogger $logger;

    /**
     * @param HttpServerInfo $serverInfo
     * @param ThreadSafeClassLoader $classLoader
     */
    public function __construct(HttpServerInfo $serverInfo, ThreadSafeClassLoader $classLoader, ThreadSafeLogger $logger)
    {
        $this->serverInfo = $serverInfo;
        $this->setClassLoaders([$classLoader]);
        $this->logger = $logger;
    }

    /**
     * @throws SocketNotCreatedException
     */
    protected function onRun(): void
    {
        $this->register();

        // create a stream context
        $context = stream_context_create();

        if (($ssl = $this->serverInfo->getSsl()) !== null) {
            stream_context_set_option($context, $ssl->getContextOptions());
            $this->isSecure = true;
        }

        // create the socket
        $socket = stream_socket_server($this->serverInfo->getAddress(), $eCode, $eMsg,
            STREAM_SERVER_BIND | STREAM_SERVER_LISTEN, $context);

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
        }

        $this->logger->notice("Shutting down the WebServer...");

        // close all connections
        $this->close();
    }

    /**
     * Register all the class loaders and http status codes
     * @return void
     */
    private function register(): void
    {
        // register all class loaders
        $this->registerClassLoaders();
    }

    /**
     * Serve all incoming connections
     * @return void
     */
    private function serveNewConnections(): void
    {
        try {

            if ($this->isSecure) {
                // set server to blocking to handle the handshake # TODO but its not set to blocking?
                stream_set_blocking(self::$socket, false);
            }

            $incomingSocket = stream_socket_accept(self::$socket, 0, $clientName);

            if (!is_resource($incomingSocket)) return;

            [$host, $port] = explode(":", $clientName);
            $client = new HttpClient($host, intval($port), $incomingSocket);

            $this->logger->info("Got new connection from: $clientName");

            if ($this->isSecure) {
                // disable the server blocking again
                stream_set_blocking(self::$socket, false);

                // enable crypto, blocking should be enabled for this # TODO is the previous comment because this handles the handshake?
                stream_set_blocking($incomingSocket, true);
                stream_socket_enable_crypto($incomingSocket, true, STREAM_CRYPTO_METHOD_SSLv23_SERVER);
            }

            // set the stream to not blocking
            stream_set_blocking($incomingSocket, false);

            // add the client to the cache
            self::$clients[$clientName] = $client;
        } catch (Exception) {
            // pass
        } finally {
            if ($this->isSecure) {
                // make sure socket is not blocking anymore
                stream_set_blocking(self::$socket, false);
            }
        }
    }

    /**
     * Serve all active clients
     * @return void
     */
    private function serveExistingConnections(): void
    {
        $closed = [];

        // go through all clients
        $router = $this->serverInfo->getRouter();
        foreach (self::$clients as $name => $client) {

            // check if the socket is still open
            if (!$client->isAvailable()) {
                $this->logger->info("Connection with $name is closed.");
                $closed[] = $name;
                continue;
            }

            // catch all exceptions to close the client
            try {
                // read all available data from the client and store it in the buffer
                if ($client->read(HttpConstants::MAX_STREAM_READ_LENGTH)) {
                    $this->logger->info("Got data from $name");

                    // create a new parser if it does not yet exist
                    if (($parser = $client->getRequestParser()) === null) {
                        $parser = new HttpRequestParser($this->serverInfo, $this->logger);
                        $client->setRequestParser($parser);
                    }

                    // append the client buffer to the parser
                    $remaining = $parser->appendData($client->readBuffer());

                    // something went wrong while parsing
                    if ($parser->isInvalid()) {
                        $this->logger->warning("Got invalid request from $name. Status Code: " . $parser->getErrorStatusCode());
                        $router->rejectRequest($client, $parser->getErrorStatusCode());
                        $closed[] = $name;
                        continue;
                    }

                    // request is not complete
                    if (!$parser->isComplete()) continue;

                    // write remaining data back to the client buffer
                    $client->writeBuffer($remaining ?? "");

                    // build the HTTP Request from the parsed result
                    $req = $parser->build();

                    // remove the request parser from the client, since its finished
                    $client->setRequestParser(null);

                    // if connection close is given, close the connection
                    if ($req->getHeaders()->getHeader(HttpHeaders::CONNECTION, "keep-alive") === "close") {
                        // mark client as closed
                        $client->setClosed();
                        $closed[] = $name;
                    }

                    // handle the request
                    $this->serverInfo->getRouter()->handleRequest($client, $req);

                    $client->flush();
                }
            } catch (Exception $e) {
                // try to get the status code from the exception
                if ($e instanceof HttpServerException) {
                    $status = $e->getStatusCode();
                } else {
                    $status = HttpStatusCodes::INTERNAL_SERVER_ERROR;
                }

                $this->logger->error("Got an error while handling $name. " . $e->getMessage());
                $router->rejectRequest($client, $status);
                $closed[] = $name;
            }
        }

        // close and remove all clients that are marked as closed
        foreach ($closed as $name) {
            // make sure everything is sent to the client
            self::$clients[$name]->flush();

            self::$clients[$name]->close();
            unset(self::$clients[$name]);
        }
    }

    /**
     * Complete an incomplete request from a client
     * @param HttpClient $client
     * @param HttpRequest $req
     * @return bool true if the request is completed, false when the request could not be completed
     */
    private function completeRequest(HttpClient $client, HttpRequest $req): bool
    {
        // loop until we have completed the body or there is no more data
        while (!$req->isCompleted()) {
            // the client is not available, or there is no (valid) data to complete the request
            if (!$client->isAvailable() ||
                ($data = $client->read(HttpConstants::MAX_STREAM_READ_LENGTH)) === false || strlen($data) == 0) {
                return false;
            }

            // add the new data to our request
            $req->appendData($data);
        }

        return true;
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

        stream_socket_shutdown(self::$socket, STREAM_SHUT_RDWR);
    }
}