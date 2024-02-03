<?php

namespace Hebbinkpro\WebServer;

use Hebbinkpro\WebServer\exception\WebServerAlreadyStartedException;
use Hebbinkpro\WebServer\http\response\HttpResponseStatus;
use Hebbinkpro\WebServer\route\Router;
use pmmp\thread\ThreadSafe;
use pocketmine\plugin\PluginBase;
use pocketmine\thread\ThreadSafeClassLoader;

class WebServer extends ThreadSafe
{
    public const PREFIX = "[WebServer]";

    private static ThreadSafeClassLoader $classLoader;
    private static PluginBase $plugin;

    private string $address;
    private int $port;

    private Router $router;

    private ?HttpServer $httpServer = null;

    /**
     * Construct a new WebServer
     * @param string $address
     * @param int $port
     * @param Router|null $router
     */
    public function __construct(string $address = "0.0.0.0", int $port = 3000, Router $router = null)
    {
        $this->address = $address;
        $this->port = $port;

        // register all status codes
        HttpResponseStatus::registerAll();

        if ($router == null) $this->router = new Router();
        else $this->router = $router;

    }

    /**
     * Register the plugin and classloader
     * @param PluginBase $plugin
     * @return void
     */
    public static function register(PluginBase $plugin): void
    {
        // store the plugin instance
        self::$plugin = $plugin;

        // get the PMMP classloader to use in the threads
        self::$classLoader = $plugin->getServer()->getLoader();
        // register the dependency to the classloader
        self::$classLoader->addPath("Laravel\\SerializableClosure", __DIR__ . "\\libs\\Laravel\\SerializableClosure");
        // register this virion to the classloader
        self::$classLoader->addPath("Hebbinkpro\\WebServer", __DIR__);
    }

    /**
     * @return string
     */
    public function getAddress(): string
    {
        return $this->address;
    }

    /**
     * @return int
     */
    public function getPort(): int
    {
        return $this->port;
    }

    /**
     * @return Router
     */
    public function getRouter(): Router
    {
        return $this->router;
    }

    /**
     * Start the web server
     * @return void
     * @throws WebServerAlreadyStartedException
     */
    public function start(): void
    {
        if ($this->httpServer !== null) throw new WebServerAlreadyStartedException();

        $this->httpServer = new HttpServer($this, self::$classLoader);
        $this->httpServer->start();

        self::$plugin->getLogger()->notice(self::PREFIX . " The web server is running at: http://$this->address:$this->port/");
    }

    /**
     * Get if the web server is started
     * @return bool
     */
    public function isStarted(): bool
    {
        return $this->httpServer !== null;
    }

    /**
     * Stop the web server
     * @return void
     */
    public function close(): void
    {
        if ($this->httpServer === null) {
            self::$plugin->getLogger()->warning(self::PREFIX . " Could not stop the web server, it is not running.");
            return;
        }

        self::$plugin->getLogger()->info(self::PREFIX . " Stopping the web server...");
        // join the http server thread
        $this->httpServer->join();
        self::$plugin->getLogger()->notice(self::PREFIX . " The web server has been stopped.");

    }
}