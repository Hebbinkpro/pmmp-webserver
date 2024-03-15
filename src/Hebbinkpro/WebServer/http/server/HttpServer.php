<?php

namespace Hebbinkpro\WebServer\http\server;

use Exception;
use Hebbinkpro\WebServer\exception\SocketNotCreatedException;
use Hebbinkpro\WebServer\http\HttpConstants;
use Hebbinkpro\WebServer\http\message\HttpRequest;
use Hebbinkpro\WebServer\http\status\HttpStatusCodes;
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

    /**
     * @param HttpServerInfo $serverInfo
     * @param ThreadSafeClassLoader $classLoader
     */
    public function __construct(HttpServerInfo $serverInfo, ThreadSafeClassLoader $classLoader)
    {
        $this->serverInfo = $serverInfo;
        $this->setClassLoaders([$classLoader]);
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
        self::$socket = stream_socket_server($this->serverInfo->getAddress(), $eCode, $eMsg,
            STREAM_SERVER_BIND | STREAM_SERVER_LISTEN, $context);

        // if there is no socket created, throw the exception with the error.
        if (!self::$socket) {
            throw new SocketNotCreatedException($eMsg);
        }

        // disable blocking
        stream_set_blocking(self::$socket, false);

        // start the loop to listen for new clients until the thread is joined or the socket is invalid
        while (!$this->isJoined() && is_resource(self::$socket)) {
            $this->serveNewConnections();
            $this->serveExistingConnections();
        }

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
                // set server to blocking to handle the handshake
                stream_set_blocking(self::$socket, false);
            }

            $incomingSocket = stream_socket_accept(self::$socket, 0, $clientName);

            if (!is_resource($incomingSocket)) return;

            [$host, $port] = explode(":", $clientName);
            $client = new HttpClient($this->serverInfo, $host, intval($port), $incomingSocket);

            if ($this->isSecure) {
                // disable the server blocking again
                stream_set_blocking(self::$socket, false);

                // enable crypto, blocking should be enabled for this
                stream_set_blocking($incomingSocket, true);
                stream_socket_enable_crypto($incomingSocket, true, STREAM_CRYPTO_METHOD_SSLv23_SERVER);
            }

            // set the stream to not blocking
            stream_set_blocking($incomingSocket, false);

            // add the client to the cache
            self::$clients[$clientName] = $client;
        } catch (Exception $e) {
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
                $closed[] = $name;
                continue;
            }

            // check if there is some data
            $data = $client->read(HttpConstants::MAX_STREAM_READ_LENGTH);
            if ($data !== false && strlen($data) > 0) {

                // decode the request
                $req = HttpRequest::parse($data, $this->serverInfo);

                // invalid or incomplete request
                if (is_int($req) || !$this->completeRequest($client, $req)) {
                    $status = HttpStatusCodes::BAD_REQUEST;
                    if (is_int($req)) $status = $req;

                    $router->rejectRequest($client, $status);
                    $closed[] = $name;
                    continue;
                }


                try {
                    $this->serverInfo->getRouter()->handleRequest($client, $req);
                } catch (Exception $e) {
                    $router->rejectRequest($client, HttpStatusCodes::INTERNAL_SERVER_ERROR, $e->getMessage());
                }

                $client->flush();

                // if the connection header close is given, close the connection
                //if ($req->getHeaders()->getHeader(HttpRequestHeader::CONNECTION, "close") === "close") {
                //    $closed[] = $name;
                //    continue;
                //}

                // TODO: find a way to not have to close the connection and only close it when the CONNECTION header is set to close
                // close the connection because we can otherwise only write ONCE to the socket, this caused me a massive headache
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